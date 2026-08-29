<?php

use App\Enums\TripStatus;
use App\Enums\VendorStatus;
use App\Filament\Vendor\Pages\ProfilUsaha;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;
use Livewire\Livewire;

/**
 * Halaman publik profil mitra `/mitra/{slug}` dan layar penyuntingnya di panel
 * vendor (2026-08-29).
 */
function mitraDisetujui(array $atribut = []): Vendor
{
    return Vendor::factory()->create($atribut);
}

function tripMitra(Vendor $vendor, TripStatus $status = TripStatus::Published): Trip
{
    $trip = tripPublished();

    $trip->update([
        'vendor_id' => $vendor->user_id,
        'status' => $status,
    ]);

    return $trip->refresh();
}

it('menampilkan profil mitra beserta trip aktifnya tanpa login', function () {
    $vendor = mitraDisetujui(['business_name' => 'Rimba Sejati Adventure', 'description' => 'Bawa rombongan sejak 2015.']);
    $trip = tripMitra($vendor);

    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertSee('Rimba Sejati Adventure')
        ->assertSee('Bawa rombongan sejak 2015.')
        ->assertSee($trip->title);
});

it('memasang badge E-GOTO x nama mitra di kepala halaman', function () {
    $vendor = mitraDisetujui(['business_name' => 'Bahari Nusantara']);

    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertSee('E-GOTO', escape: false)
        ->assertSee('Bahari Nusantara');
});

it('menyembunyikan trip yang belum tayang dari halaman mitra', function () {
    $vendor = mitraDisetujui();
    $draft = tripMitra($vendor, TripStatus::Draft);
    $tayang = tripMitra($vendor);

    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertSee($tayang->title)
        ->assertDontSee($draft->title);
});

it('menjawab 404 untuk mitra yang belum disetujui', function () {
    // Halaman kosong memberi tahu penebak URL bahwa slug itu ada dan sedang
    // menunggu; 404 tidak memberi tahu apa pun.
    foreach ([VendorStatus::Pending, VendorStatus::Rejected, VendorStatus::Suspended] as $status) {
        $vendor = mitraDisetujui(['status' => $status]);

        $this->get(route('vendors.show', $vendor))->assertNotFound();
    }
});

it('menampilkan empty state, bukan halaman kosong, saat mitra belum punya trip', function () {
    $vendor = mitraDisetujui();

    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertSee('Belum ada trip yang dibuka');
});

it('menautkan nama mitra dari kartu trip di halaman kategori', function () {
    $vendor = mitraDisetujui(['business_name' => 'Kelana Darat']);
    $trip = tripMitra($vendor);

    $this->get(route('categories.show', $trip->category))
        ->assertOk()
        ->assertSee('Kelana Darat')
        ->assertSee(route('vendors.show', $vendor), escape: false);
});

it('menautkan nama mitra dari halaman detail trip', function () {
    $vendor = mitraDisetujui(['business_name' => 'Puncak Timur']);
    $trip = tripMitra($vendor);

    $this->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('Puncak Timur')
        ->assertSee(route('vendors.show', $vendor), escape: false);
});

it('tidak menambah query per kartu saat nama mitra ikut ditampilkan', function () {
    $vendor = mitraDisetujui();

    foreach (range(1, 5) as $i) {
        tripMitra($vendor);
    }

    $kategori = Trip::where('vendor_id', $vendor->user_id)->firstOrFail()->category;

    // Relasi mitra di-eager load: dua query tetap untuk seluruh halaman, bukan
    // dua per kartu.
    $jumlah = hitungQuery(function () use ($kategori) {
        $this->get(route('categories.show', $kategori))->assertOk();
    });

    expect($jumlah)->toBeLessThan(20);
});

it('menyimpan perubahan profil dari panel mitra', function () {
    $vendor = mitraDisetujui(['business_name' => 'Nama Lama']);

    Livewire::actingAs($vendor->user)
        ->test(ProfilUsaha::class)
        ->fillForm([
            'business_name' => 'Nama Baru',
            'description' => 'Spesialis trip pendakian akhir pekan.',
            'address' => 'Malang',
        ])
        ->call('simpan')
        ->assertHasNoFormErrors();

    expect($vendor->fresh()->business_name)->toBe('Nama Baru')
        ->and($vendor->fresh()->address)->toBe('Malang');
});

it('tidak mengizinkan mitra mengubah slug dan status usahanya sendiri', function () {
    $vendor = mitraDisetujui(['status' => VendorStatus::Approved, 'slug' => 'slug-asli']);

    Livewire::actingAs($vendor->user)
        ->test(ProfilUsaha::class)
        // Dikirim langsung ke Livewire, melewati form. Slug sudah jadi alamat
        // publik, dan status adalah keputusan admin.
        ->fillForm(['business_name' => 'Tetap Sah'])
        ->call('simpan');

    expect($vendor->fresh()->slug)->toBe('slug-asli')
        ->and($vendor->fresh()->status)->toBe(VendorStatus::Approved);
});

it('menutup layar profil usaha dari akun yang belum punya baris mitra', function () {
    $tanpaProfil = User::factory()->vendor()->create();

    $this->actingAs($tanpaProfil);

    expect(ProfilUsaha::canAccess())->toBeFalse();
});
