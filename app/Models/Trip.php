<?php

namespace App\Models;

use App\Enums\TripDifficulty;
use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Builder;
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
        'difficulty_level',
        'published_at',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TripStatus::class,
            'difficulty_level' => TripDifficulty::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Hanya trip yang sudah dipublikasikan boleh muncul di sisi publik.
     * Dipakai di semua query halaman publik supaya draft milik mitra tidak
     * pernah bocor lewat URL tebakan.
     *
     * @param  Builder<Trip>  $query
     * @return Builder<Trip>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', TripStatus::Published);
    }

    /**
     * Jadwal terdekat dari relasi `schedules` yang sudah ter-eager-load.
     * Sengaja tidak query ulang — dipanggil di dalam loop kartu trip.
     */
    public function nextSchedule(): ?TripSchedule
    {
        return $this->schedules
            ->filter(fn (TripSchedule $schedule) => $schedule->start_date->isToday() || $schedule->start_date->isFuture())
            ->sortBy('start_date')
            ->first();
    }

    /**
     * Harga termurah lintas jadwal mendatang, untuk label "mulai dari".
     * Null kalau trip belum punya jadwal/harga aktif.
     */
    public function startingPrice(): ?int
    {
        $price = $this->schedules
            ->flatMap(fn (TripSchedule $schedule) => $schedule->prices)
            ->min('price');

        return $price !== null ? (int) $price : null;
    }

    /** @return BelongsTo<User, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
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

    /** @return HasMany<TripOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(TripOption::class)->orderBy('sort_order');
    }

    /** @return HasMany<TripSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(TripSchedule::class);
    }
}
