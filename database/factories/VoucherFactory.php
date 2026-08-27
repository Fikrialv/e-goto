<?php

namespace Database\Factories;

use App\Enums\VoucherScope;
use App\Enums\VoucherType;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('PROMO##??')),
            'type' => VoucherType::Percent,
            'value' => 10,
            'min_spend' => null,
            'quota' => null,
            'used_count' => 0,
            'valid_from' => null,
            'valid_until' => null,
            'scope' => VoucherScope::Global,
            'scope_id' => null,
            'is_active' => true,
        ];
    }

    public function fixed(int $rupiah): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => VoucherType::Fixed,
            'value' => $rupiah,
        ]);
    }
}
