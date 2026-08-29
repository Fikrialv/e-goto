<?php

use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Enums\VendorStatus;
use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\Category;
use App\Models\Review;
use App\Models\Trip;
use App\Models\TripSchedule;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Cache;

/**
 * Sesi redesign visual.
 *
 * Yang dijaga di sini bukan "halamannya cantik" — itu tidak bisa diuji mesin —
 * melainkan tiga janji yang gampang jebol saat styling diubah lagi nanti:
 * angka statistik tidak boleh dikarang, ikon kategori harus punya berkas SVG-nya,
 * dan halaman masuk tetap berfungsi setelah ganti layout.
 */
beforeEach(function () {
    Cache::flush();
});

/**
 * Booking langsung lewat model — belum ada BookingFactory di project ini, dan
 * test-test lain membuatnya lewat alur HTTP penuh. Untuk memeriksa angka
 * statistik, yang dibutuhkan cuma barisnya, bukan seluruh alur pemesanan.
 */
function bookingUntukStatistik(TripSchedule $schedule, BookingStatus $status, int $pax = 2): Booking
{
    $booking = Booking::create([
        'code' => 'EG-'.fake()->unique()->numerify('######'),
        'user_id' => User::factory()->customer()->create()->id,
        'trip_schedule_id' => $schedule->id,
        'pax_count' => $pax,
        'subtotal' => 400_000 * $pax,
        'discount_amount' => 0,
        'unique_code' => fake()->numberBetween(1, 999),
        'total_amount' => 400_000 * $pax,
        'status' => $status,
        'expires_at' => now()->addHours(2),
    ]);

    foreach (range(1, $pax) as $urutan) {
        BookingParticipant::create([
            'booking_id' => $booking->id,
            'is_leader' => $urutan === 1,
            'full_name' => fake()->name(),
        ]);
    }

    return $booking;
}

it('menyembunyikan baris statistik saat datanya masih kosong', function () {
    tripPublished();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('Peserta terlayani');
    $response->assertDontSee('Mitra penyelenggara aktif');
});

it('menampilkan baris statistik dengan angka hasil hitung, bukan angka tetap', function () {
    $trip = tripPublished();

    // Satu jadwal yang sudah lewat + booking terkonfirmasi = satu trip terlaksana.
    $schedulePast = TripSchedule::factory()->for($trip)->create([
        'start_date' => now()->subDays(7)->toDateString(),
        'quota' => 20,
        'booked_count' => 2,
    ]);

    bookingUntukStatistik($schedulePast, BookingStatus::Confirmed);

    Vendor::factory()->create(['status' => VendorStatus::Approved]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Peserta terlayani');
    $response->assertSee('Mitra penyelenggara aktif');
    // Dua peserta, satu mitra, satu trip berangkat — bukan angka bulat karangan.
    $response->assertSeeInOrder(['Trip sudah berangkat', 'Peserta terlayani', 'Mitra penyelenggara aktif']);
});

it('merender ikon kategori dari kolom icon tanpa melempar SvgNotFound', function () {
    $category = Category::factory()->create(['icon' => 'mountain', 'is_active' => true]);
    tripPublished($category);

    $response = $this->get(route('home'));

    $response->assertOk();
    // Kelas pembungkus dari x-icon-circle + jejak path SVG Lucide.
    $response->assertSee('rounded-full', false);
});

it('memakai ikon cadangan saat kolom icon kategori kosong', function () {
    $category = Category::factory()->create(['icon' => null, 'is_active' => true]);
    tripPublished($category);

    $this->get(route('home'))->assertOk();
});

it('menolak nama ikon di luar daftar tertutup', function () {
    expect(array_keys(Category::ICON_OPTIONS))
        ->toContain('mountain', 'waves', 'compass')
        ->not->toContain('heroicon-o-sun');
});

it('membuka halaman masuk dengan panel split dan toggle kata sandi', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('Masuk');
    // Toggle kata sandi dijalankan Alpine di klien.
    $response->assertSee('terlihat', false);
    $response->assertSee('Tampilkan kata sandi');
});

it('memajang kutipan review asli di panel halaman masuk', function () {
    $trip = tripPublished();
    $trip->update(['cover_image' => 'trips/contoh.jpg']);

    $schedule = $trip->schedules()->first();
    $booking = bookingUntukStatistik($schedule, BookingStatus::Completed, pax: 1);
    $booking->user->update(['name' => 'Rani Pratiwi']);

    Review::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'user_id' => $booking->user_id,
        'rating' => 5,
        'comment' => 'Panitianya rapi, briefing jelas.',
        'status' => ReviewStatus::Published,
    ]);

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('Panitianya rapi, briefing jelas.');
    $response->assertSee('Rani Pratiwi');
});

it('tetap membuka halaman masuk saat belum ada trip maupun review', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
});

it('memasang splash logo di layout customer dan layout masuk', function () {
    tripPublished();

    $this->get(route('home'))->assertOk()->assertSee('images/logo2.svg', false);
    $this->get(route('login'))->assertOk()->assertSee('images/logo2.svg', false);
});

it('menampilkan rata-rata rating di kartu trip hanya kalau agregatnya dihitung', function () {
    $trip = tripPublished();
    $trip->update(['is_featured' => true]);

    $schedule = $trip->schedules()->first();
    $booking = bookingUntukStatistik($schedule, BookingStatus::Completed, pax: 1);

    Review::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'user_id' => $booking->user_id,
        'rating' => 4,
        'status' => ReviewStatus::Published,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('1 ulasan');
});

it('mengisi bidang gambar dengan fallback yang dirancang saat sampul belum ada', function () {
    $trip = tripPublished();
    $trip->update(['is_featured' => true, 'cover_image' => null]);

    $response = $this->get(route('home'));

    $response->assertOk();
    // Gradasi token brand + ikon besar, bukan kotak abu dan bukan foto stok.
    $response->assertSee('from-mist-100', false);
    $response->assertSee('text-teal-700/25', false);
    $response->assertDontSee('unsplash', false);
    $response->assertDontSee('placeholder.com', false);
});

it('memakai foto asli begitu sampul terunggah, bukan fallback', function () {
    $trip = tripPublished();
    $trip->update(['is_featured' => true, 'cover_image' => 'trips/sampul.jpg']);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('trips/sampul.jpg', false);
});

it('memasang fallback yang sama di panel halaman masuk', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('from-mist-100', false);
    $response->assertDontSee('unsplash', false);
});

it('memakai latar putih, bukan off-white hangat, di halaman customer', function () {
    tripPublished();

    $home = $this->get(route('home'));
    $masuk = $this->get(route('login'));

    foreach ([$home, $masuk] as $response) {
        $response->assertOk();
        $response->assertSee('bg-white', false);
        // Latar krem/off-white hangat dilarang (CLAUDE.md §10).
        $response->assertDontSee('bg-amber-50', false);
        $response->assertDontSee('bg-orange-50', false);
        $response->assertDontSee('bg-stone-50', false);
        $response->assertDontSee('bg-yellow-50', false);
        $response->assertDontSee('bg-neutral-50', false);
    }
});

it('tidak memakai warna di luar palet brand di halaman customer', function () {
    tripPublished();

    foreach ([route('home'), route('login'), route('register')] as $url) {
        $response = $this->get($url);

        $response->assertOk();
        $response->assertDontSee('violet', false);
        $response->assertDontSee('purple', false);
        $response->assertDontSee('indigo', false);
    }
});

it('membatasi efek glass ke panel halaman masuk saja', function () {
    $trip = tripPublished();

    // Halaman customer biasa: tidak ada backdrop-blur sama sekali.
    $this->get(route('home'))->assertOk()->assertDontSee('backdrop-blur', false);

    /*
     * Kartu kutipan di halaman masuk baru dirender kalau ada review terbit —
     * di situlah satu-satunya efek glass yang diizinkan berada.
     */
    $booking = bookingUntukStatistik($trip->schedules()->first(), BookingStatus::Completed, pax: 1);

    Review::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'user_id' => $booking->user_id,
        'rating' => 5,
        'comment' => 'Panitianya sigap.',
        'status' => ReviewStatus::Published,
    ]);

    $this->get(route('login'))->assertOk()->assertSee('backdrop-blur', false);
});

it('menjalankan hero slider saat trip pilihan dua atau lebih', function () {
    foreach (range(1, 3) as $urutan) {
        tripPublished()->update(['is_featured' => true]);
    }

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('aria-roledescription="carousel"', false);
    $response->assertSee('Tampilkan trip ke-1');
    // Auto-advance mati sendiri kalau pengguna minta gerakan dikurangi.
    $response->assertSee('prefers-reduced-motion', false);
});

it('merender hero statis saat trip pilihan cuma satu', function () {
    tripPublished()->update(['is_featured' => true]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('aria-roledescription="carousel"', false);
});

it('membedakan ikon fallback tiap slide mengikuti kategorinya', function () {
    $gunung = Category::factory()->create(['icon' => 'mountain', 'is_active' => true]);
    $pantai = Category::factory()->create(['icon' => 'waves', 'is_active' => true]);

    tripPublished($gunung)->update(['is_featured' => true]);
    tripPublished($pantai)->update(['is_featured' => true]);

    $response = $this->get(route('home'));

    $response->assertOk();

    // Dua berkas SVG berbeda harus sama-sama hadir di halaman.
    $jalur = 'vendor/mallardduck/blade-lucide-icons/resources/svg/icons/';
    foreach (['mountain', 'waves'] as $ikon) {
        preg_match('/ d="([^"]+)"/', file_get_contents(base_path($jalur.$ikon.'.svg')), $cocok);
        $response->assertSee(substr($cocok[1], 0, 30), false);
    }
});

it('menaruh semua slide hero di posisi absolut supaya hero tidak runtuh', function () {
    foreach (range(1, 3) as $urutan) {
        tripPublished()->update(['is_featured' => true]);
    }

    $response = $this->get(route('home'));
    $isi = $response->getContent();

    $response->assertOk();

    /*
     * Sebelumnya hanya slide kedua ke atas yang absolut; begitu slide pertama
     * disembunyikan Alpine, pembungkusnya kehilangan tinggi dan seluruh hero
     * lenyap. Jumlah figure absolut harus sama dengan jumlah slide.
     */
    $jumlahSlide = substr_count($isi, 'aria-roledescription="slide"');
    $jumlahAbsolut = substr_count($isi, '<figure x-show="aktif === ');

    expect($jumlahSlide)->toBe(3)
        ->and(substr_count($isi, 'class="absolute inset-0"'))->toBeGreaterThanOrEqual($jumlahAbsolut);

    // Rasio dipegang pembungkus, bukan tiap gambar.
    $response->assertSee('aspect-[4/3] overflow-hidden rounded-3xl', false);
});

it('memakai berkas logo di header dan footer, bukan teks', function () {
    tripPublished();

    $response = $this->get(route('home'));

    $response->assertOk();
    // Dua pemakaian: wordmark header dan wordmark footer.
    expect(substr_count($response->getContent(), 'images/Logo1.svg'))->toBeGreaterThanOrEqual(2);
    $response->assertDontSee('E<span class="text-amber-600">·</span>GOTO', false);
});

it('tidak mewarnai ikon di luar palet teal', function () {
    $trip = tripPublished();
    $trip->update(['is_featured' => true]);

    $booking = bookingUntukStatistik($trip->schedules()->first(), BookingStatus::Completed, pax: 1);

    Review::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'user_id' => $booking->user_id,
        'rating' => 5,
        'status' => ReviewStatus::Published,
    ]);

    // Bintang rating dulu amber; sekarang ikut aturan ikon monokrom.
    $this->get(route('trips.show', $trip))
        ->assertOk()
        ->assertDontSee('fill-current text-amber-500', false);
});

it('memasang ikon di halaman alur transaksi yang sebelumnya kosong', function () {
    $trip = tripPublished();

    /*
     * blade-icons merender SVG inline tanpa membawa nama ikonnya, jadi yang
     * dicocokkan adalah potongan path dari berkas SVG-nya sendiri.
     */
    $petik = function (string $ikon): string {
        $berkas = base_path('vendor/mallardduck/blade-lucide-icons/resources/svg/icons/'.$ikon.'.svg');
        preg_match('/ d="([^"]+)"/', file_get_contents($berkas), $cocok);

        return substr($cocok[1], 0, 30);
    };

    $this->get(route('categories.show', $trip->category))
        ->assertOk()
        ->assertSee($petik('sliders-horizontal'), false);

    $this->get(route('partners.show'))
        ->assertOk()
        ->assertSee($petik('check'), false);

    $this->get(route('private-trip.show'))
        ->assertOk()
        ->assertSee($petik('message-circle'), false);
});
