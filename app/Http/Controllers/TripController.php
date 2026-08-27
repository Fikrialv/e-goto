<?php

namespace App\Http\Controllers;

use App\Enums\TripStatus;
use App\Models\Trip;
use Illuminate\Contracts\View\View;

class TripController extends Controller
{
    /**
     * Detail trip — publik, tanpa auth. Tombol booking baru menuntut login di
     * D3 (PLAN.md §5.5); di sini CTA hanya menaruh tujuan, bukan gerbang.
     *
     * Trip yang belum `published` (draft mitra, ditolak, diarsipkan) dijawab
     * 404, bukan halaman kosong — draft mitra tidak boleh bisa diintip lewat
     * slug yang ditebak.
     */
    public function show(Trip $trip): View
    {
        abort_unless($trip->status === TripStatus::Published, 404);

        // Closure eager-load menerima Relation (bukan Eloquent\Builder), jadi
        // sengaja tanpa type hint di sini.
        $trip->load([
            'category',
            'images',
            'schedules' => fn ($query) => $query->upcoming()->orderBy('start_date'),
            'schedules.prices' => fn ($query) => $query->orderBy('min_pax'),
            'options' => fn ($query) => $query->where('is_active', true),
        ]);

        $relatedTrips = Trip::query()
            ->published()
            ->whereBelongsTo($trip->category)
            ->whereKeyNot($trip->getKey())
            ->with([
                'category',
                'schedules' => fn ($query) => $query->upcoming()->orderBy('start_date'),
                'schedules.prices',
            ])
            ->take(3)
            ->get();

        return view('pages.trip-detail', compact('trip', 'relatedTrips'));
    }
}
