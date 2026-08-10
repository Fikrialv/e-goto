<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Halaman bayar: QRIS, kode booking, dan nominal unik.
     *
     * Kepemilikan diperiksa di sini — kode booking memang sulit ditebak, tapi
     * kode itu ditulis di catatan transfer dan dikirim lewat WhatsApp, jadi
     * memperlakukannya sebagai rahasia adalah kesalahan.
     */
    public function show(Request $request, Booking $booking, PaymentGateway $gateway): View
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $booking->load(['schedule.trip', 'latestPayment']);

        return view('pages.payment', [
            'booking' => $booking,
            'instruksi' => $gateway->createCharge($booking),
        ]);
    }
}
