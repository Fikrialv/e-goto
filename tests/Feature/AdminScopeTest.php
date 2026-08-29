<?php

use App\Enums\AdminScope;
use App\Enums\UserRole;
use App\Filament\Pages\ReminderKeberangkatan;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\ReviewResource;
use App\Filament\Resources\TripResource;
use App\Filament\Resources\VendorApplicationResource;
use App\Filament\Resources\VoucherResource;
use App\Filament\Widgets\PendingPaymentsWidget;
use App\Models\User;

/**
 * Pemecahan permission admin (GUIDE.md "Pemecahan permission admin").
 *
 * Yang dijaga bukan sekadar "menu hilang". Menu yang disembunyikan tapi URL-nya
 * tetap bisa dibuka bukan pembatasan, itu penyamaran — jadi tiap layar diuji
 * lewat request langsung ke alamatnya, bukan lewat navigasi.
 */
function adminBerScope(?AdminScope $scope): User
{
    return User::factory()->create([
        'role' => UserRole::Admin,
        'admin_scope' => $scope,
    ]);
}

it('memberi akses penuh ke admin tanpa scope', function () {
    $this->actingAs(adminBerScope(null));

    expect(PaymentResource::canAccess())->toBeTrue()
        ->and(TripResource::canAccess())->toBeTrue()
        ->and(VoucherResource::canAccess())->toBeTrue()
        ->and(ReminderKeberangkatan::canAccess())->toBeTrue();
});

it('menutup layar trip, kategori, dan voucher dari verifikator pembayaran', function () {
    $this->actingAs(adminBerScope(AdminScope::PaymentCs));

    expect(TripResource::canAccess())->toBeFalse()
        ->and(CategoryResource::canAccess())->toBeFalse()
        ->and(VoucherResource::canAccess())->toBeFalse();
});

it('membuka antrean pembayaran, pengingat, pengajuan mitra, dan ulasan untuk verifikator', function () {
    $this->actingAs(adminBerScope(AdminScope::PaymentCs));

    expect(PaymentResource::canAccess())->toBeTrue()
        ->and(ReminderKeberangkatan::canAccess())->toBeTrue()
        ->and(VendorApplicationResource::canAccess())->toBeTrue()
        ->and(ReviewResource::canAccess())->toBeTrue();
});

it('menutup antrean pembayaran dari manajer trip', function () {
    $this->actingAs(adminBerScope(AdminScope::TripManager));

    expect(PaymentResource::canAccess())->toBeFalse()
        ->and(ReminderKeberangkatan::canAccess())->toBeFalse()
        ->and(VendorApplicationResource::canAccess())->toBeFalse()
        ->and(TripResource::canAccess())->toBeTrue()
        ->and(CategoryResource::canAccess())->toBeTrue()
        ->and(VoucherResource::canAccess())->toBeTrue();
});

it('menolak URL antrean pembayaran dengan 403, bukan cuma menyembunyikan menunya', function () {
    $this->actingAs(adminBerScope(AdminScope::TripManager))
        ->get(PaymentResource::getUrl('index'))
        ->assertForbidden();
});

it('menolak URL daftar trip dari verifikator pembayaran', function () {
    $this->actingAs(adminBerScope(AdminScope::PaymentCs))
        ->get(TripResource::getUrl('index'))
        ->assertForbidden();
});

it('menyembunyikan widget antrean pembayaran dari manajer trip', function () {
    // Widget ini menautkan ke layar yang akan menolak mereka dengan 403 —
    // memajang angkanya cuma memberi tautan buntu.
    $this->actingAs(adminBerScope(AdminScope::TripManager));
    expect(PendingPaymentsWidget::canView())->toBeFalse();

    $this->actingAs(adminBerScope(AdminScope::PaymentCs));
    expect(PendingPaymentsWidget::canView())->toBeTrue();
});

it('tidak memberi akses apa pun ke non-admin lewat scope', function () {
    // Scope hanya mempersempit, tidak pernah memberi. Vendor dengan scope
    // terpasang tetap bukan admin.
    $vendor = User::factory()->create([
        'role' => UserRole::Vendor,
        'admin_scope' => AdminScope::PaymentCs,
    ]);

    expect($vendor->bolehMengurus(AdminScope::PaymentCs))->toBeFalse();
});

it('tidak mengubah akses mitra ke panel vendor', function () {
    // TripResource panel admin dibatasi lewat canAccess(), bukan lewat Policy —
    // Policy berlaku global dan akan ikut mempersempit panel vendor.
    $vendor = User::factory()->vendor()->create();

    $this->actingAs($vendor)
        ->get(App\Filament\Vendor\Resources\TripResource::getUrl('index', panel: 'vendor'))
        ->assertOk();
});

it('menetapkan scope lewat artisan dan menolak scope karangan', function () {
    $admin = adminBerScope(null);

    $this->artisan('admin:scope', ['email' => $admin->email, 'scope' => 'payment_cs'])
        ->assertSuccessful();

    expect($admin->fresh()->admin_scope)->toBe(AdminScope::PaymentCs);

    $this->artisan('admin:scope', ['email' => $admin->email, 'scope' => 'dewa'])
        ->assertFailed();

    // Tanpa argumen scope = dikembalikan ke akses penuh.
    $this->artisan('admin:scope', ['email' => $admin->email])->assertSuccessful();

    expect($admin->fresh()->admin_scope)->toBeNull();
});

it('menolak memasang scope ke user yang bukan admin', function () {
    $customer = User::factory()->customer()->create();

    $this->artisan('admin:scope', ['email' => $customer->email, 'scope' => 'payment_cs'])
        ->assertFailed();

    expect($customer->fresh()->admin_scope)->toBeNull();
});
