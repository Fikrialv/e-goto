<?php

namespace App\Http\Controllers;

use App\Actions\CreateBooking;
use App\Enums\TripStatus;
use App\Http\Requests\StoreBookingRequest;
use App\Models\TripSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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
     * Jadwal yang sudah lewat atau penuh ditolak 404 supaya URL tebakan tidak
     * membuka alur pemesanan yang seharusnya tertutup.
     */
    public function create(Request $request, TripSchedule $schedule): View
    {
        $this->pastikanBisaDipesan($schedule);

        $profil = $request->user()->customerProfile;

        return view('pages.booking-create', compact('schedule', 'profil'));
    }

    /**
     * Semua yang riskan (harga, kuota, nominal unik, enkripsi identitas) ada di
     * CreateBooking — controller cuma menyerahkan input yang sudah tervalidasi.
     */
    public function store(StoreBookingRequest $request, TripSchedule $schedule, CreateBooking $createBooking): RedirectResponse
    {
        $this->pastikanBisaDipesan($schedule);

        $booking = $createBooking->handle(
            user: $request->user(),
            schedule: $schedule,
            participants: $request->participants(),
            notes: $request->validated('notes'),
        );

        return redirect()->route('payments.show', $booking);
    }

    /**
     * Dipakai baik saat membuka form maupun saat menyimpan. Pengecekan di
     * `store` bukan pengulangan mubazir: halaman bisa saja dibuka satu jam lalu,
     * saat jadwal masih tersedia.
     */
    private function pastikanBisaDipesan(TripSchedule $schedule): void
    {
        $schedule->load(['trip.category', 'prices']);

        abort_unless($schedule->trip->status === TripStatus::Published, 404);
        abort_if($schedule->start_date->isBefore(today()), 404);
        abort_if($schedule->isSoldOut(), 404);
    }
}
