<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;

/**
 * Rating & komentar peserta (D11).
 *
 * Hanya booking yang benar-benar selesai yang bisa dinilai, dan satu booking
 * cuma sekali — review tanpa transaksi di belakangnya adalah pintu masuk
 * paling gampang untuk rating palsu.
 */
class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->status === BookingStatus::Completed, 403);
        abort_if($booking->review()->exists(), 403);

        $booking->review()->create([
            'trip_id' => $booking->schedule->trip_id,
            'user_id' => $request->user()->id,
            'rating' => $request->validated('rating'),
            'comment' => $request->validated('comment'),
            'status' => ReviewStatus::Published,
        ]);

        return redirect()
            ->route('bookings.index')
            ->with('status', 'Terima kasih, penilaian Anda sudah tersimpan.');
    }
}
