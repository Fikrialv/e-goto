<?php

use App\Enums\TripDifficulty;
use App\Enums\TripStatus;
use App\Models\Category;
use App\Models\Trip;
use App\Models\TripPrice;
use App\Models\TripSchedule;
use Illuminate\Support\Facades\Cache;

/**
 * Browsing publik (D2).
 *
 * Inti yang dijaga test ini: tiga halaman ini HARUS bisa dibuka guest tanpa
 * satu pun redirect ke login (PLAN.md §5.5) — kalau suatu saat ada yang
 * menempelkan middleware `auth` ke route publik, test ini yang menangkap.
 * Sekaligus sisi sebaliknya: yang belum published tidak boleh bocor.
 */
beforeEach(function () {
    // Homepage di-cache 5 menit; tanpa flush, test berikutnya membaca sisa
    // cache test sebelumnya dan hasilnya menyesatkan.
    Cache::flush();
});

/**
 * Trip published lengkap dengan satu jadwal mendatang + harga.
 */
function tripPublished(?Category $category = null, int $harga = 500_000, string $startDate = '+10 days'): Trip
{
    $trip = Trip::factory()
        ->published()
        ->for($category ?? Category::factory())
        ->create();

    $schedule = TripSchedule::factory()->for($trip)->create([
        'start_date' => now()->modify($startDate)->toDateString(),
        'quota' => 20,
        'booked_count' => 0,
    ]);

    TripPrice::factory()->for($schedule, 'schedule')->create(['price' => $harga]);

    return $trip;
}

it('membuka homepage untuk guest tanpa redirect login', function () {
    tripPublished();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Jadwal terdekat');
});

it('membuka halaman kategori untuk guest tanpa redirect login', function () {
    $category = Category::factory()->create(['name' => 'Pantai Demo', 'slug' => 'pantai-demo']);
    tripPublished($category);

    $this->get(route('categories.show', $category))
        ->assertSuccessful()
        ->assertSee('Pantai Demo');
});

it('membuka detail trip untuk guest tanpa redirect login', function () {
    $trip = tripPublished();

    $this->get(route('trips.show', $trip))
        ->assertSuccessful()
        ->assertSee($trip->title);
});

it('menampilkan hanya trip published di homepage', function () {
    $published = tripPublished();
    $published->update(['is_featured' => true]);

    $draft = Trip::factory()->create(['status' => TripStatus::Draft, 'title' => 'Trip Rahasia Draft']);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee($published->title)
        ->assertDontSee($draft->title);
});

it('menolak akses detail trip yang belum published', function () {
    $trip = Trip::factory()->create(['status' => TripStatus::Draft]);

    $this->get(route('trips.show', $trip))->assertNotFound();
});

it('menolak akses kategori yang dinonaktifkan', function () {
    $category = Category::factory()->inactive()->create();

    $this->get(route('categories.show', $category))->assertNotFound();
});

it('menyaring trip di luar rentang harga yang diminta', function () {
    $category = Category::factory()->create();
    $murah = tripPublished($category, harga: 200_000);
    $mahal = tripPublished($category, harga: 2_000_000);

    $this->get(route('categories.show', [$category, 'harga_max' => 500_000]))
        ->assertSuccessful()
        ->assertSee($murah->title)
        ->assertDontSee($mahal->title);
});

it('menyaring trip di luar rentang tanggal yang diminta', function () {
    $category = Category::factory()->create();
    $dekat = tripPublished($category, startDate: '+5 days');
    $jauh = tripPublished($category, startDate: '+80 days');

    $this->get(route('categories.show', [$category, 'tanggal_akhir' => now()->addDays(30)->toDateString()]))
        ->assertSuccessful()
        ->assertSee($dekat->title)
        ->assertDontSee($jauh->title);
});

it('tidak menampilkan jadwal yang sudah lewat di detail trip', function () {
    $trip = Trip::factory()->published()->create();
    TripSchedule::factory()->for($trip)->past()->create();

    $this->get(route('trips.show', $trip))
        ->assertSuccessful()
        ->assertSee('Belum ada jadwal terbuka');
});

/*
 * Halaman legal (D7.5). Justru dibaca orang yang belum punya akun — saat
 * memutuskan mau pesan atau tidak — jadi wajib terbuka untuk guest.
 */
it('membuka halaman legal untuk guest tanpa redirect login', function (string $url) {
    $this->get($url)->assertOk();
})->with(['/faq', '/syarat-ketentuan', '/kebijakan-privasi']);

it('menautkan halaman legal dari footer', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('pages.faq'), escape: false)
        ->assertSee(route('pages.terms'), escape: false)
        ->assertSee(route('pages.privacy'), escape: false);
});

it('menandai angka refund dan retensi yang belum final', function () {
    $this->get('/syarat-ketentuan')->assertOk()->assertSee('[SEMENTARA');
    $this->get('/kebijakan-privasi')->assertOk()->assertSee('[SEMENTARA');
});

/*
 * Filter level fisik (D7.6 c, test §9 #17). Tingkat kesulitan menempel di trip,
 * bukan kategori: dua trip dalam satu kategori bisa jauh berbeda beratnya, dan
 * label yang salah di konteks fisik bukan sekadar bikin kecewa.
 */
it('menyaring trip berdasarkan tingkat kesulitan', function () {
    $category = Category::factory()->create();

    $pemula = tripPublished($category);
    $pemula->update(['difficulty_level' => TripDifficulty::Pemula]);

    $lanjutan = tripPublished($category);
    $lanjutan->update(['difficulty_level' => TripDifficulty::Lanjutan]);

    $this->get(route('categories.show', [$category, 'level' => 'pemula']))
        ->assertOk()
        ->assertSee($pemula->title)
        ->assertDontSee($lanjutan->title);
});

it('menampilkan seluruh trip saat filter tingkat kesulitan tidak dipakai', function () {
    $category = Category::factory()->create();

    $pemula = tripPublished($category);
    $pemula->update(['difficulty_level' => TripDifficulty::Pemula]);

    $tanpaLevel = tripPublished($category);

    $this->get(route('categories.show', $category))
        ->assertOk()
        ->assertSee($pemula->title)
        ->assertSee($tanpaLevel->title);
});

it('menolak nilai tingkat kesulitan di luar daftar', function () {
    $category = Category::factory()->create();
    tripPublished($category);

    $this->get(route('categories.show', [$category, 'level' => 'ekstrem']))
        ->assertSessionHasErrors('level');
});

/*
 * Checklist perlengkapan (D7.6 e, test §9 #19). Kategori tanpa checklist tidak
 * boleh meninggalkan judul menggantung di halaman detail.
 */
it('menampilkan checklist perlengkapan kategori di detail trip', function () {
    $category = Category::factory()->create(['gear_checklist' => ['Tenda', 'Sleeping bag']]);
    $trip = tripPublished($category);

    $this->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('Yang perlu Anda bawa')
        ->assertSee('Sleeping bag');
});

it('tidak menampilkan blok checklist untuk kategori tanpa checklist', function () {
    $category = Category::factory()->create(['gear_checklist' => null]);
    $trip = tripPublished($category);

    $this->get(route('trips.show', $trip))
        ->assertOk()
        ->assertDontSee('Yang perlu Anda bawa');
});
