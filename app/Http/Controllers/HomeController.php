<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Trip;
use App\Models\TripSchedule;
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
                    'schedules' => fn ($query) => $query->upcoming()->orderBy('start_date'),
                    'schedules.prices',
                ])
                ->latest('published_at')
                ->take(6)
                ->get();
        });

        $upcomingSchedules = Cache::remember('home.upcoming_schedules', now()->addMinutes(5), function () {
            return TripSchedule::query()
                ->upcoming()
                ->whereColumn('booked_count', '<', 'quota')
                ->whereHas('trip', fn ($query) => $query->published())
                ->with(['trip.category', 'prices'])
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

        return view('pages.home', compact('featuredTrips', 'upcomingSchedules', 'categories'));
    }
}
