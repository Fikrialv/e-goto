<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'trip_option_id',
        'qty',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'unit_price' => 'integer',
        ];
    }

    /** @return BelongsTo<TripOption, $this> */
    public function option(): BelongsTo
    {
        return $this->belongsTo(TripOption::class, 'trip_option_id');
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
