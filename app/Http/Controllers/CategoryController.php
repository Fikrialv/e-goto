<?php

namespace App\Http\Controllers;

use App\Http\Requests\TripFilterRequest;
use App\Models\Category;
use App\Models\Trip;
use App\Models\TripPrice;
use App\Models\TripSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CategoryController extends Controller
{
    /**
     * Daftar trip satu kategori + filter. Publik, tanpa auth.
     *
     * Kategori nonaktif (mis. Internasional yang masih ditutup) diperlakukan
     * seperti tidak ada — 404, bukan halaman kosong — supaya URL tebakan tidak
     * membocorkan kategori yang belum dibuka.
     */
    public function show(TripFilterRequest $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $filters = $request->validated();

        $trips = Trip::query()
            ->select('trips.*')
            ->published()
            ->whereBelongsTo($category)
            // Level fisik menempel di trip, jadi disaring di sini — bukan ikut
            // filter jadwal di bawah yang memang soal tanggal & harga.
            ->when(
                $filters['level'] ?? null,
                fn (Builder $query, string $level) => $query->where('difficulty_level', $level)
            )
            ->whereHas('schedules', fn (Builder $query) => $this->applyScheduleFilters($query, $filters))
            ->withMin(['schedules as tanggal_terdekat' => fn ($query) => $query->upcoming()], 'start_date')
            ->addSelect(['harga_mulai' => $this->startingPriceSubquery()])
            ->withCount(['reviews' => fn (Builder $query) => $query->published()])
            ->withAvg(['reviews' => fn (Builder $query) => $query->published()], 'rating')
            // Closure eager-load menerima Relation, bukan Eloquent\Builder —
            // karena itu tanpa type hint di blok `with` ini.
            ->with([
                'category',
                // Dua query tetap untuk seluruh halaman, bukan dua per kartu —
                // penjaganya PerformaTest.
                'vendor.vendorProfile',
                'schedules' => fn ($query) => $query->upcoming()->orderBy('start_date'),
                'schedules.prices',
            ])
            ->orderBy(...$this->sortColumn($filters['urut'] ?? 'terdekat'))
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->active()->orderBy('sort_order')->get();

        return view('pages.category', compact('category', 'categories', 'trips', 'filters'));
    }

    /**
     * Filter tanggal & harga dipasang pada jadwal, bukan pada trip: satu trip
     * bisa punya banyak jadwal, dan yang dicari pengunjung adalah "trip yang
     * punya minimal satu jadwal cocok".
     *
     * @param  Builder<TripSchedule>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyScheduleFilters(Builder $query, array $filters): void
    {
        $query->upcoming()->whereColumn('booked_count', '<', 'quota');

        if (! empty($filters['tanggal_mulai'])) {
            $query->whereDate('start_date', '>=', $filters['tanggal_mulai']);
        }

        if (! empty($filters['tanggal_akhir'])) {
            $query->whereDate('start_date', '<=', $filters['tanggal_akhir']);
        }

        $hargaMin = $filters['harga_min'] ?? null;
        $hargaMax = $filters['harga_max'] ?? null;

        if ($hargaMin !== null || $hargaMax !== null) {
            $query->whereHas('prices', function (Builder $priceQuery) use ($hargaMin, $hargaMax) {
                if ($hargaMin !== null) {
                    $priceQuery->where('price', '>=', $hargaMin);
                }

                if ($hargaMax !== null) {
                    $priceQuery->where('price', '<=', $hargaMax);
                }
            });
        }
    }

    /**
     * Harga termurah per trip sebagai subquery — dipakai untuk mengurutkan
     * "termurah/termahal" tanpa menarik semua harga ke PHP dulu.
     *
     * @return Builder<TripPrice>
     */
    private function startingPriceSubquery(): Builder
    {
        return TripPrice::query()
            ->selectRaw('min(trip_prices.price)')
            ->join('trip_schedules', 'trip_schedules.id', '=', 'trip_prices.trip_schedule_id')
            ->whereColumn('trip_schedules.trip_id', 'trips.id')
            ->whereDate('trip_schedules.start_date', '>=', today());
    }

    /**
     * @return array{string, string}
     */
    private function sortColumn(string $urut): array
    {
        return match ($urut) {
            'termurah' => ['harga_mulai', 'asc'],
            'termahal' => ['harga_mulai', 'desc'],
            default => ['tanggal_terdekat', 'asc'],
        };
    }
}
