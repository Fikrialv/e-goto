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

    /** @return HasMany<RefundRequest, $this> */
    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    /** @return HasOne<RefundRequest, $this> */
    public function latestRefundRequest(): HasOne
    {
        return $this->hasOne(RefundRequest::class)->latestOfMany();
    }

    /**
     * Apakah booking ini masih boleh diajukan refund.
     *
     * Dua syarat, dan keduanya harus dijaga di server: uangnya memang sudah
     * masuk (booking terkonfirmasi atau trip sudah selesai — tidak ada yang
     * bisa dikembalikan dari booking yang belum dibayar), dan belum ada
     * pengajuan yang masih berjalan (pengajuan ganda membuat satu booking
     * berpotensi diproses dua kali oleh dua admin).
     */
    public function bolehAjukanRefund(): bool
    {
        if (! in_array($this->status, [BookingStatus::Confirmed, BookingStatus::Completed], true)) {
            return false;
        }

        return ! $this->refundRequests()->berjalan()->exists();
    }

    /** @return HasOne<Review, $this> */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
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
