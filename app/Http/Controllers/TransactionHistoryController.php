<?php

namespace App\Http\Controllers;

use App\Actions\ProcessRefundRequest;
use App\Enums\RefundType;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Riwayat Transaksi — daftar uang yang berpindah.
 *
 * Sengaja terpisah dari "Booking Saya". Booking Saya menjawab "trip saya apa
 * saja dan statusnya bagaimana"; halaman ini menjawab "uang saya ke mana saja".
 * Dua pertanyaan berbeda: yang pertama dibuka sebelum berangkat, yang kedua
 * dibuka saat ada yang perlu dicocokkan atau disengketakan.
 */
class TransactionHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $pembayaran = Payment::query()
            ->whereHas('booking', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['booking.schedule.trip'])
            ->latest()
            ->paginate(15, pageName: 'bayar');

        $refund = RefundRequest::query()
            ->whereHas('booking', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['booking.schedule.trip'])
            ->latest()
            ->paginate(15, pageName: 'refund');

        /*
         * Booking yang masih boleh diajukan refund. Dihitung di sini, bukan di
         * view: kelayakan adalah aturan bisnis, dan aturan yang hidup di Blade
         * tidak bisa diuji tanpa merender HTML.
         */
        $bisaAjukan = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with('schedule.trip')
            ->get()
            ->filter(fn (Booking $booking) => $booking->bolehAjukanRefund())
            ->values();

        return view('pages.riwayat-transaksi', compact('pembayaran', 'refund', 'bisaAjukan'));
    }

    /**
     * Pengajuan refund oleh customer.
     *
     * Kepemilikan diperiksa lewat `where('user_id', ...)` di query, bukan lewat
     * perbandingan setelah booking ditemukan — booking milik orang lain tidak
     * pernah ditemukan, jadi tidak ada jalan membedakan "bukan punyamu" dari
     * "tidak ada" lewat waktu respons.
     */
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $data = $request->validate([
            // Metode karangan ditolak di sini, bukan di form: daftar opsi di
            // HTML tidak menolak apa pun yang dikirim dengan POST buatan.
            'type' => ['required', Rule::enum(RefundType::class)],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        app(ProcessRefundRequest::class)->ajukan(
            $booking,
            RefundType::from($data['type']),
            $data['customer_note'] ?? null,
        );

        return redirect()
            ->route('transactions.index')
            ->with('status', 'Pengajuan terkirim. Admin memeriksanya dan mengabari lewat halaman ini.');
    }
}
