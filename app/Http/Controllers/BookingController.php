<?php

namespace App\Http\Controllers;

use App\Enums\TripStatus;
use App\Models\TripSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * "Booking Saya". Masih kerangka — daftarnya baru terisi setelah alur
     * pemesanan dibangun di D4.
     */
    public function index(Request $request): View
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['schedule.trip.category'])
            ->latest()
            ->paginate(10);

        return view('pages.booking-saya', compact('bookings'));
    }

    /**
     * Gerbang login berakhir di sini: inilah URL yang disimpan sebagai
     * `url.intended` saat tamu menekan tombol booking.
     *
     * Isi halaman masih ringkasan jadwal — form peserta, penguncian kuota
     * (`lockForUpdate`), dan nominal unik dikerjakan di D4. Jadwal yang sudah
     * lewat atau penuh ditolak 404 supaya URL tebakan tidak membuka alur
     * pemesanan yang seharusnya tertutup.
     */
    public function create(TripSchedule $schedule): View
    {
        $schedule->load(['trip.category', 'prices']);

        abort_unless($schedule->trip->status === TripStatus::Published, 404);
        abort_if($schedule->start_date->isBefore(today()), 404);
        abort_if($schedule->isSoldOut(), 404);

        return view('pages.booking-create', compact('schedule'));
    }
}
