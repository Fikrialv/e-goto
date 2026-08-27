<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'name',
        'description',
        'extra_price',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'extra_price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Trip, $this> */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
