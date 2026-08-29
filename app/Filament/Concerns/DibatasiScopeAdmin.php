<?php

namespace App\Filament\Concerns;

use App\Enums\AdminScope;

/**
 * Membatasi satu Resource/Page panel admin ke satu bidang tugas.
 *
 * Dipasang lewat `canAccess()` milik Filament, bukan lewat Policy, karena tiga
 * model di sini (Trip, Review, Booking) juga dipakai panel vendor: Policy
 * berlaku global, jadi mempersempitnya untuk admin akan ikut mempersempitnya
 * untuk mitra. `canAccess()` menempel ke Resource, dan Resource-nya memang
 * hanya ada di satu panel.
 *
 * Filament memakai method yang sama untuk dua hal sekaligus — menyembunyikan
 * item navigasi DAN menolak akses langsung ke URL-nya dengan 403 — jadi tidak
 * ada layar yang cuma disembunyikan tapi tetap bisa dibuka lewat alamat.
 */
trait DibatasiScopeAdmin
{
    abstract public static function scopeAdmin(): AdminScope;

    public static function canAccess(): bool
    {
        return auth()->user()?->bolehMengurus(static::scopeAdmin()) ?? false;
    }
}
