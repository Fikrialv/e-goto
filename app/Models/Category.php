<?php

namespace App\Models;

use App\Enums\IdType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * Ikon Lucide yang boleh dipilih admin, dipakai di grid kategori homepage.
     * Daftar tertutup supaya nilai di database selalu punya berkas SVG-nya —
     * nama karangan akan melempar SvgNotFound di halaman publik.
     *
     * @var array<string, string>
     */
    public const ICON_OPTIONS = [
        'mountain' => 'Gunung',
        'waves' => 'Laut / pantai',
        'building-2' => 'Kota',
        'tent' => 'Kemah',
        'map' => 'Domestik',
        'globe' => 'Internasional',
        'compass' => 'Umum / aktivitas',
        'footprints' => 'Jalan kaki',
        'camera' => 'Wisata foto',
    ];

    protected $fillable = [
        'name',
        'slug',
        'id_requirement',
        'is_active',
        'sort_order',
        'icon',
        'gear_checklist',
    ];

    protected function casts(): array
    {
        return [
            'id_requirement' => IdType::class,
            'is_active' => 'boolean',
            'gear_checklist' => 'array',
        ];
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return HasMany<Trip, $this> */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
