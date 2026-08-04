<?php

namespace App\Models;

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
