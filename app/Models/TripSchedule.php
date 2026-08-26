<?php

namespace App\Models;

use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'start_date',
        'end_date',
        'quota',
        'booked_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => TripStatus::class,
        ];
    }

    /**
     * Sisa kursi. Nilai ini hanya aman dibaca untuk tampilan — saat benar-benar
     * mengunci kuota (D4), baris jadwal wajib di-lockForUpdate dulu di dalam
     * transaksi, karena angka ini bisa berubah antara dibaca dan dipakai.
     */
    public function remainingQuota(): int
    {
        return max(0, $this->quota - $this->booked_count);
    }

    /**
     * Jadwal yang masih bisa dipesan: tanggal berangkat belum lewat.
     * Jadwal hari ini tetap masuk — cut-off jam keberangkatan urusan D4.
     *
     * @param  Builder<TripSchedule>  $query
     * @return Builder<TripSchedule>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('start_date', '>=', today());
    }

    public function isSoldOut(): bool
    {
        return $this->remainingQuota() === 0;
    }

    /** @return BelongsTo<Trip, $this> */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /** @return HasMany<TripPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(TripPrice::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
