<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * E-tiket peserta. Hanya untuk booking yang sudah terkonfirmasi — sebelum
     * itu tiketnya memang belum terbit.
     */
    public function show(Request $request, Booking $booking): View
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless(
            in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Completed], true),
            404,
        );

        $booking->load(['schedule.trip.category', 'tickets.participant']);

        return view('pages.e-ticket', compact('booking'));
    }
}
