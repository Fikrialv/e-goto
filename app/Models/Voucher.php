<?php

namespace App\Models;

use App\Enums\VoucherScope;
use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_spend',
        'quota',
        'used_count',
        'valid_from',
        'valid_until',
        'scope',
        'scope_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => VoucherType::class,
            'scope' => VoucherScope::class,
            'value' => 'integer',
            'min_spend' => 'integer',
            'quota' => 'integer',
            'used_count' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Potongan untuk satu subtotal. Dibulatkan ke bawah supaya potongan persen
     * tidak pernah melebihi apa yang tertulis, dan dicap di subtotal — voucher
     * tidak boleh membuat total jadi negatif.
     */
    public function potonganUntuk(int $subtotal): int
    {
        $potongan = match ($this->type) {
            VoucherType::Percent => (int) floor($subtotal * $this->value / 100),
            VoucherType::Fixed => $this->value,
        };

        return (int) min($potongan, $subtotal);
    }

    /** @return HasMany<VoucherUsage, $this> */
    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }
}
