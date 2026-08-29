<?php

namespace App\Models;

use App\Enums\AdminScope;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'admin_scope',
        'phone',
        'avatar',
        'provider',
        'provider_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'provider_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'admin_scope' => AdminScope::class,
            // Rahasia TOTP tersimpan polos membuat dump database setara kunci
            // ke seluruh akun staf — aturan enkripsi yang sama dengan NIK.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** Verifikasi dua langkah sudah aktif dan terbukti bisa dipakai. */
    public function twoFactorAktif(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Apakah admin ini boleh mengurus bidang tertentu di panel admin.
     *
     * Tiga hal yang ditegakkan sekaligus, dan urutannya penting:
     * non-admin selalu ditolak (scope tidak pernah memberi akses ke yang
     * rolenya bukan admin), admin tanpa scope berakses penuh (keadaan pemilik
     * project), dan admin ber-scope hanya cocok dengan scope-nya sendiri.
     */
    public function bolehMengurus(AdminScope $scope): bool
    {
        if ($this->role !== UserRole::Admin) {
            return false;
        }

        return $this->admin_scope === null || $this->admin_scope === $scope;
    }

    /**
     * Gerbang panel Filament.
     *
     * Tanpa ini Filament mengizinkan SETIAP user terautentikasi membuka panel —
     * artinya customer biasa bisa mengetik /admin dan sampai ke layar verifikasi
     * pembayaran. Pencocokan dilakukan per panel supaya vendor tidak bisa masuk
     * panel admin dan sebaliknya.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->role === UserRole::Admin,
            'vendor' => $this->role === UserRole::Vendor,
            default => false,
        };
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    /**
     * Profil usaha mitra.
     *
     * `trips.vendor_id` menyimpan `users.id`, jadi jalan dari trip ke profil
     * usahanya selalu lewat sini — bukan lewat `vendors.id`.
     *
     * @return HasOne<Vendor, $this>
     */
    public function vendorProfile(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
