<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'logo',
        'description',
        'phone',
        'address',
        'status',
        'approved_at',
        'commission_percent',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Trip milik mitra ini.
     *
     * `trips.vendor_id` menyimpan **`users.id`** (akun panel mitra), bukan
     * `vendors.id` — itulah nilai yang diisi `Vendor\Resources\TripResource`
     * dari sesi yang sedang masuk. Relasi ini karena itu dijembatani lewat
     * `user_id`, bukan `hasMany(Trip::class)` bawaan yang akan mencocokkan
     * `vendors.id` dan diam-diam mengembalikan trip yang salah.
     *
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'vendor_id', 'user_id');
    }
}
