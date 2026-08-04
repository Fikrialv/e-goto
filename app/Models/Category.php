<?php

namespace App\Models;

use App\Enums\IdType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'id_requirement',
        'is_active',
        'sort_order',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'id_requirement' => IdType::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Trip, $this> */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
