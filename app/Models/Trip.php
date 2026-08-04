<?php

namespace App\Models;

use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'title',
        'slug',
        'description',
        'itinerary',
        'includes',
        'excludes',
        'meeting_point',
        'cover_image',
        'status',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TripStatus::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<TripImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(TripImage::class)->orderBy('sort_order');
    }

    /** @return HasMany<TripSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(TripSchedule::class);
    }
}
