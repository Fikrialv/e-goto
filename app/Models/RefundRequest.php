<?php

namespace App\Models;

use App\Enums\RefundStatus;
use App\Enums\RefundType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu pengajuan refund atas satu booking.
 *
 * Mitra TIDAK terlibat sama sekali di alur ini — uang masuk ke rekening E-GOTO,
 * jadi yang mengembalikannya juga E-GOTO. Karena itu tidak ada Resource untuk
 * model ini di panel vendor, dan tidak boleh ditambahkan.
 */
class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'status',
        'customer_note',
        'admin_note',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => RefundType::class,
            'status' => RefundStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<User, $this> */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Pengajuan yang masih menunggu keputusan atau masih menunggu uangnya
     * dikirim. Dipakai untuk mencegah pengajuan ganda dan untuk antrean admin.
     *
     * @param  Builder<self>  $query
     */
    public function scopeBerjalan(Builder $query): void
    {
        $query->whereIn('status', [RefundStatus::Diajukan, RefundStatus::Disetujui]);
    }
}
