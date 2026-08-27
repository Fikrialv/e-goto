<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'trip_schedule_id',
        'pax_count',
        'subtotal',
        'discount_amount',
        'unique_code',
        'total_amount',
        'status',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'unique_code' => 'integer',
            'total_amount' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<TripSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TripSchedule::class, 'trip_schedule_id');
    }

    /** @return HasMany<BookingParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(BookingParticipant::class);
    }

    /** @return HasOne<Payment, $this> */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<BookingOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(BookingOption::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
