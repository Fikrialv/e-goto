<?php

use App\Actions\ApproveVendorApplication;
use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorApplicationResource\Pages\ListVendorApplications;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Onboarding mitra (D8).
 *
 * Tiga hal yang dijaga: halaman pengajuan benar-benar terbuka untuk tamu,
 * dokumen pengajuan tidak bisa diambil siapa pun yang menebak URL, dan approve
 * menghasilkan akun yang benar-benar bisa masuk panel mitra — bukan sekadar
 * baris berstatus "approved".
 */
beforeEach(function () {
    Storage::fake('local');
});

function pengajuanMitra(array $ubah = []): VendorApplication
{
    return VendorApplication::factory()->create($ubah);
}

it('membuka halaman jadi mitra untuk tamu tanpa redirect login', function () {
    $this->get(route('partners.show'))
        ->assertOk()
        ->assertSee('Ajukan diri');
});

it('menyimpan pengajuan mitra beserta dokumennya', function () {
    $this->post(route('partners.store'), [
        'business_name' => 'Rimba Jaya Adventure',
        'contact_name' => 'Bagas Prakoso',
        'contact_email' => 'bagas@rimbajaya.test',
        'contact_phone' => '08123456789',
        'experience' => 'Rutin bawa open trip Semeru sejak 2019.',
        'documents' => [UploadedFile::fake()->create('akta.pdf', 200, 'application/pdf')],
    ])->assertRedirect(route('partners.show'));

    $pengajuan = VendorApplication::firstOrFail();

    expect($pengajuan->business_name)->toBe('Rimba Jaya Adventure')
        ->and($pengajuan->status)->toBe(VendorStatus::Pending)
        ->and($pengajuan->documents)->toHaveCount(1)
        ->and($pengajuan->documents[0]['nama'])->toBe('akta.pdf');

    // Disk non-publik: berkasnya tidak boleh mendarat di public/.
    Storage::disk('local')->assertExists($pengajuan->documents[0]['path']);
});

it('menolak pengajuan tanpa data wajib', function () {
    $this->post(route('partners.store'), ['business_name' => 'Tanpa Kontak'])
        ->assertSessionHasErrors(['contact_name', 'contact_email', 'contact_phone']);

    expect(VendorApplication::count())->toBe(0);
});

it('menolak dokumen berjenis berbahaya', function () {
    $this->post(route('partners.store'), [
        'business_name' => 'Coba Coba',
        'contact_name' => 'Anon',
        'contact_email' => 'anon@contoh.test',
        'contact_phone' => '08123456789',
        'documents' => [UploadedFile::fake()->create('backdoor.php', 10, 'application/x-httpd-php')],
    ])->assertSessionHasErrors('documents.0');

    expect(VendorApplication::count())->toBe(0);
});

it('menutup dokumen pengajuan dari tamu dan customer', function () {
    $pengajuan = pengajuanMitra([
        'documents' => [['nama' => 'akta.pdf', 'path' => 'pengajuan-mitra/akta.pdf']],
    ]);

    $this->get(route('admin.partners.document', [$pengajuan, 0]))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->customer()->create())
        ->get(route('admin.partners.document', [$pengajuan, 0]))
        ->assertForbidden();
});

it('membuat akun vendor dan profil mitra saat pengajuan disetujui', function () {
    $pengajuan = pengajuanMitra(['contact_email' => 'mitra@contoh.test', 'business_name' => 'Langit Biru Trip']);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $hasil = app(ApproveVendorApplication::class)->handle($pengajuan, $admin);

    $vendor = Vendor::firstOrFail();
    $user = User::where('email', 'mitra@contoh.test')->firstOrFail();

    expect($user->role)->toBe(UserRole::Vendor)
        ->and($vendor->user_id)->toBe($user->id)
        ->and($vendor->slug)->toBe('langit-biru-trip')
        ->and($vendor->status)->toBe(VendorStatus::Approved)
        ->and($hasil['password'])->not->toBeNull()
        ->and($pengajuan->fresh()->status)->toBe(VendorStatus::Approved)
        ->and($pengajuan->fresh()->reviewed_by)->toBe($admin->id)
        ->and($pengajuan->fresh()->vendor_id)->toBe($vendor->id);

    // Akun hasil approve harus benar-benar bisa masuk panel mitra.
    expect($user->canAccessPanel(filament()->getPanel('vendor')))->toBeTrue();
});

it('menaikkan peran akun yang sudah ada alih-alih menggandakan email', function () {
    $customer = User::factory()->customer()->create(['email' => 'lama@contoh.test']);
    $pengajuan = pengajuanMitra(['contact_email' => 'lama@contoh.test']);

    $hasil = app(ApproveVendorApplication::class)->handle($pengajuan, User::factory()->create(['role' => UserRole::Admin]));

    expect(User::where('email', 'lama@contoh.test')->count())->toBe(1)
        ->and($customer->fresh()->role)->toBe(UserRole::Vendor)
        ->and($hasil['password'])->toBeNull();
});

it('menolak pengajuan lewat panel admin dengan alasan wajib', function () {
    $pengajuan = pengajuanMitra();

    Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->test(ListVendorApplications::class)
        ->callTableAction('tolak', $pengajuan, data: ['admin_note' => 'Dokumen usaha belum lengkap.'])
        ->assertHasNoTableActionErrors();

    expect($pengajuan->fresh()->status)->toBe(VendorStatus::Rejected)
        ->and($pengajuan->fresh()->admin_note)->toBe('Dokumen usaha belum lengkap.');
});

it('menolak penolakan tanpa alasan', function () {
    $pengajuan = pengajuanMitra();

    Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->test(ListVendorApplications::class)
        ->callTableAction('tolak', $pengajuan, data: ['admin_note' => ''])
        ->assertHasTableActionErrors(['admin_note']);

    expect($pengajuan->fresh()->status)->toBe(VendorStatus::Pending);
});

it('menyimpan jadwal ngobrol dari panel admin', function () {
    $pengajuan = pengajuanMitra();
    $waktu = now()->addDays(3)->startOfHour();

    Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->test(ListVendorApplications::class)
        ->callTableAction('jadwalkan', $pengajuan, data: [
            'meeting_at' => $waktu->toDateTimeString(),
            'admin_note' => 'Zoom, link menyusul.',
        ])
        ->assertHasNoTableActionErrors();

    expect($pengajuan->fresh()->meeting_at->toDateTimeString())->toBe($waktu->toDateTimeString())
        ->and($pengajuan->fresh()->status)->toBe(VendorStatus::Pending);
});
