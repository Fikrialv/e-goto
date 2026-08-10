<?php

namespace App\Http\Controllers;

use App\Actions\StorePaymentProof;
use App\Contracts\MessagingService;
use App\Contracts\PaymentGateway;
use App\Http\Requests\StorePaymentProofRequest;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    /**
     * Halaman bayar: QRIS, kode booking, nominal unik, dan unggah bukti.
     *
     * Kepemilikan diperiksa di sini — kode booking memang sulit ditebak, tapi
     * kode itu ditulis di catatan transfer dan dikirim lewat WhatsApp, jadi
     * memperlakukannya sebagai rahasia adalah kesalahan.
     */
    public function show(Request $request, Booking $booking, PaymentGateway $gateway, MessagingService $messaging): View
    {
        $this->pastikanPemilik($request, $booking);

        $booking->load(['schedule.trip', 'latestPayment']);

        return view('pages.payment', [
            'booking' => $booking,
            'instruksi' => $gateway->createCharge($booking),
            'linkWhatsApp' => $messaging->notifyAdminNewPayment($booking),
        ]);
    }

    public function store(StorePaymentProofRequest $request, Booking $booking, StorePaymentProof $storeProof): RedirectResponse
    {
        $this->pastikanPemilik($request, $booking);

        $storeProof->handle(
            booking: $booking,
            proof: $request->file('proof'),
            amountDeclared: $request->integer('amount_declared') ?: null,
        );

        return redirect()
            ->route('payments.show', $booking)
            ->with('status', 'Bukti pembayaran terkirim. Admin akan memeriksanya, biasanya kurang dari 1x24 jam.');
    }

    /**
     * Bukti bayar disimpan di disk non-publik. Berkasnya memuat nama dan nomor
     * rekening pengirim, jadi hanya boleh keluar lewat route ini — yang
     * memeriksa siapa yang meminta lebih dulu.
     */
    public function proof(Request $request, Booking $booking): StreamedResponse
    {
        $this->pastikanPemilik($request, $booking);

        $payment = $booking->latestPayment;

        abort_if($payment?->proof_path === null, 404);

        $disk = Storage::disk(config('booking.proof_disk'));

        abort_unless($disk->exists($payment->proof_path), 404);

        return $disk->response($payment->proof_path);
    }

    /**
     * Berkas bukti untuk layar verifikasi admin. Dipisah dari `proof()` karena
     * penjaganya beda: yang satu memeriksa kepemilikan booking, yang ini peran
     * admin (middleware `role:admin` di route).
     */
    public function adminProof(Payment $payment): StreamedResponse
    {
        abort_if($payment->proof_path === null, 404);

        $disk = Storage::disk(config('booking.proof_disk'));

        abort_unless($disk->exists($payment->proof_path), 404);

        return $disk->response($payment->proof_path);
    }

    private function pastikanPemilik(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
    }
}
