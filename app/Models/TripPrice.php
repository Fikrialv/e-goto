<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_schedule_id',
        'label',
        'price',
        'min_pax',
        'max_pax',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }

    /** @return BelongsTo<TripSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TripSchedule::class, 'trip_schedule_id');
    }
}
