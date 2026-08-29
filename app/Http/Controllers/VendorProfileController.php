<?php

namespace App\Http\Controllers;

use App\Enums\VendorStatus;
use App\Models\Trip;
use App\Models\TripPrice;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class VendorProfileController extends Controller
{
    /**
     * Halaman publik profil mitra. Tanpa auth, sama seperti kategori dan detail
     * trip.
     *
     * Mitra yang belum disetujui — atau yang sudah ditolak/disuspensi —
     * dijawab 404, bukan halaman kosong. Halaman kosong memberi tahu penebak
     * URL bahwa slug itu ada dan sedang menunggu; 404 tidak memberi tahu apa
     * pun. Ini penjaga yang sama dengan kategori nonaktif di CategoryController.
     */
    public function show(Vendor $vendor): View
    {
        abort_unless($vendor->status === VendorStatus::Approved, 404);

        $trips = Trip::query()
            ->select('trips.*')
            ->published()
            // vendor_id menyimpan users.id, bukan vendors.id.
            ->where('vendor_id', $vendor->user_id)
            ->withMin(['schedules as tanggal_terdekat' => fn ($query) => $query->upcoming()], 'start_date')
            ->addSelect(['harga_mulai' => $this->hargaMulaiSubquery()])
            ->withCount(['reviews' => fn (Builder $query) => $query->published()])
            ->withAvg(['reviews' => fn (Builder $query) => $query->published()], 'rating')
            ->with([
                'category',
                'schedules' => fn ($query) => $query->upcoming()->orderBy('start_date'),
                'schedules.prices',
            ])
            ->orderBy('tanggal_terdekat')
            ->paginate(12);

        return view('pages.vendor-profile', compact('vendor', 'trips'));
    }

    /**
     * Harga termurah per trip lewat subquery — sama dengan yang dipakai
     * CategoryController, supaya kartu trip di dua halaman membaca angka yang
     * dihitung dengan cara yang sama.
     *
     * @return Builder<TripPrice>
     */
    private function hargaMulaiSubquery(): Builder
    {
        return TripPrice::query()
            ->selectRaw('min(trip_prices.price)')
            ->join('trip_schedules', 'trip_schedules.id', '=', 'trip_prices.trip_schedule_id')
            ->whereColumn('trip_schedules.trip_id', 'trips.id')
            ->whereDate('trip_schedules.start_date', '>=', today());
    }
}
