<?php

use App\Enums\UserRole;
use App\Models\User;

/**
 * Gerbang panel Filament (User::canAccessPanel).
 *
 * Filament secara bawaan mengizinkan setiap user terautentikasi membuka panel.
 * Test ini yang memastikan gerbang kita benar-benar memblokir — kalau
 * canAccessPanel hilang atau salah, test di bawah langsung merah.
 */
it('mengizinkan admin membuka panel admin', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});

it('menolak customer membuka panel admin', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)
        ->get('/admin')
        ->assertForbidden();
});

it('menolak vendor membuka panel admin', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);

    $this->actingAs($vendor)
        ->get('/admin')
        ->assertForbidden();
});

it('menolak admin membuka panel vendor', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get('/vendor')
        ->assertForbidden();
});

it('mengarahkan guest ke halaman login, bukan ke dashboard', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});
