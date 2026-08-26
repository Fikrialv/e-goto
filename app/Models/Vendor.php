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

    /** @return HasMany<Trip, $this> */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
