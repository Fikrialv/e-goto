<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\VendorStatus;
use App\Models\BookingParticipant;
use App\Models\Category;
use App\Models\Trip;
use App\Models\TripSchedule;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Homepage — publik penuh, tanpa auth (PLAN.md §5.5).
     *
     * Tiga blok datanya jarang berubah dibanding frekuensi kunjungan, jadi
     * di-cache pendek (5 menit) supaya homepage tidak memukul database tiap
     * pengunjung. TTL sengaja pendek: trip yang baru terbit tetap terasa
     * langsung muncul tanpa perlu invalidasi manual.
     */
    public function index(): View
    {
        $featuredTrips = Cache::remember('home.featured_trips', now()->addMinutes(5), function () {
            return Trip::query()
                ->published()
                ->where('is_featured', true)
                ->with([
                    'category',
                    // Nama mitra di kartu trip. Dua query tetap untuk seluruh
                    // halaman, bukan dua per kartu.
                    'vendor.vendorProfile',
                    'schedules' => fn ($query) => $query->upcoming()->orderBy('start_date'),
                    'schedules.prices',
                ])
                // Agregat rating ikut ditarik sebagai subquery supaya kartu bisa
                // memajang bintang tanpa satu query tambahan per kartu.
                ->withCount(['reviews' => fn ($query) => $query->published()])
                ->withAvg(['reviews' => fn ($query) => $query->published()], 'rating')
                ->latest('published_at')
                // Lima adalah batas slide hero (docs/DESIGN_SYSTEM.md); daftar
                // ini juga yang mengisi grid "Trip populer" di bawahnya.
                ->take(5)
                ->get();
        });

        $upcomingSchedules = Cache::remember('home.upcoming_schedules', now()->addMinutes(5), function () {
            return TripSchedule::query()
                ->upcoming()
                ->whereColumn('booked_count', '<', 'quota')
                ->whereHas('trip', fn ($query) => $query->published())
                ->with(['trip.category', 'trip.vendor.vendorProfile', 'prices'])
                ->orderBy('start_date')
                ->take(6)
                ->get();
        });

        $categories = Cache::remember('home.categories', now()->addMinutes(5), function () {
            return Category::query()
                ->active()
                ->withCount(['trips' => fn ($query) => $query->published()])
                ->orderBy('sort_order')
                ->get();
        });

        /*
         * Angka di baris statistik. Semuanya hasil hitung nyata — kalau salah
         * satunya masih nol, blade menyembunyikan seksinya daripada memajang
         * angka yang tidak berarti (docs/DESIGN_SYSTEM.md).
         *
         * Cache 15 menit: tiga COUNT ini tidak berubah secepat daftar trip,
         * dan homepage adalah halaman yang paling sering dibuka.
         */
        $stats = Cache::remember('home.stats', now()->addMinutes(15), function () {
            return [
                'tripTerlaksana' => TripSchedule::query()
                    ->whereDate('start_date', '<', now()->toDateString())
                    ->whereHas('bookings', fn ($query) => $query->whereIn('status', [
                        BookingStatus::Confirmed,
                        BookingStatus::Completed,
                    ]))
                    ->count(),
                'mitraAktif' => Vendor::query()
                    ->where('status', VendorStatus::Approved)
                    ->count(),
                'pesertaTerlayani' => BookingParticipant::query()
                    ->whereHas('booking', fn ($query) => $query->whereIn('status', [
                        BookingStatus::Confirmed,
                        BookingStatus::Completed,
                    ]))
                    ->count(),
            ];
        });

        return view('pages.home', compact('featuredTrips', 'upcomingSchedules', 'categories', 'stats'));
    }
}
