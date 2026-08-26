<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'experience',
        'documents',
        'status',
        'meeting_at',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'vendor_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'documents' => 'array',
            'meeting_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
