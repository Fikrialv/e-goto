<?php

namespace Database\Seeders;

use App\Enums\TripDifficulty;
use App\Enums\TripStatus;
use App\Models\Category;
use App\Models\Trip;
use App\Models\TripPrice;
use App\Models\TripSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data demo V1: 13 trip di 6 kategori, dengan variasi yang memang perlu dilihat
 * sebelum rilis — kuota penuh, kuota tinggal sedikit, jadwal dekat/jauh, harga
 * bertingkat, dan satu trip internasional berstatus draft (kategorinya masih
 * ditutup, jadi tidak boleh muncul di halaman publik).
 */
class DemoTripSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $trips = [
            [
                'category' => 'pendakian',
                'title' => 'Open Trip Gunung Prau via Patak Banteng',
                'level' => TripDifficulty::Pemula,
                'meeting_point' => 'Basecamp Patak Banteng, Wonosobo',
                'featured' => true,
                'harga' => [['Reguler', 385_000, 1, null], ['Rombongan 5+', 350_000, 5, null]],
                'jadwal' => [[6, 20, 14], [27, 20, 3]],
            ],
            [
                'category' => 'pantai',
                'title' => 'Snorkeling Karimunjawa 3 Hari 2 Malam',
                'level' => TripDifficulty::Pemula,
                'meeting_point' => 'Pelabuhan Kartini, Jepara',
                'featured' => true,
                'harga' => [['Reguler', 1_450_000, 1, null], ['Rombongan 6+', 1_325_000, 6, null]],
                // Jadwal kedua sengaja penuh — untuk melihat state "Kuota habis".
                'jadwal' => [[12, 24, 19], [40, 24, 24]],
            ],
            [
                'category' => 'domestik',
                'title' => 'Jelajah Bromo Sunrise dari Malang',
                'level' => TripDifficulty::Menengah,
                'meeting_point' => 'Stasiun Malang Kota Baru',
                'featured' => false,
                'harga' => [['Reguler', 675_000, 1, null]],
                'jadwal' => [[9, 16, 12], [23, 16, 16]],
            ],
            [
                'category' => 'keliling-kota',
                'title' => 'Walking Tour Kota Lama Semarang',
                'meeting_point' => 'Gereja Blenduk, Semarang',
                'featured' => false,
                'harga' => [['Reguler', 150_000, 1, null], ['Pelajar', 120_000, 1, null]],
                'jadwal' => [[4, 25, 21], [18, 25, 5]],
            ],
            [
                'category' => 'aktivitas',
                'title' => 'Rafting Sungai Elo Setengah Hari',
                'level' => TripDifficulty::Menengah,
                'meeting_point' => 'Basecamp Blondo, Magelang',
                'featured' => true,
                'harga' => [['Reguler', 275_000, 1, null], ['Rombongan 10+', 240_000, 10, null]],
                'jadwal' => [[7, 30, 8], [21, 30, 29]],
            ],
            [
                'category' => 'pantai',
                'title' => 'Sunset Trip Pantai Menganti',
                'meeting_point' => 'Alun-alun Kebumen',
                'featured' => false,
                'harga' => [['Reguler', 225_000, 1, null]],
                'jadwal' => [[11, 18, 6]],
            ],
            [
                'category' => 'pendakian',
                'title' => 'Pendakian Gunung Merbabu via Selo',
                'level' => TripDifficulty::Lanjutan,
                'meeting_point' => 'Basecamp Selo, Boyolali',
                'featured' => false,
                'harga' => [['Reguler', 495_000, 1, null], ['Rombongan 4+', 460_000, 4, null]],
                // Sisa 1 kursi — untuk melihat tampilan "tinggal sedikit".
                'jadwal' => [[15, 15, 14], [33, 15, 2]],
            ],
            [
                'category' => 'domestik',
                'title' => 'Open Trip Labuan Bajo 4 Hari 3 Malam',
                'meeting_point' => 'Bandara Komodo, Labuan Bajo',
                'featured' => true,
                'harga' => [['Reguler', 3_250_000, 1, null], ['Rombongan 8+', 2_950_000, 8, null]],
                'jadwal' => [[30, 20, 7], [58, 20, 0]],
            ],
            [
                'category' => 'domestik',
                'title' => 'Wisata Budaya Yogyakarta 2 Hari',
                'meeting_point' => 'Stasiun Tugu Yogyakarta',
                'featured' => false,
                'harga' => [['Reguler', 550_000, 1, null], ['Pelajar', 475_000, 1, null]],
                'jadwal' => [[5, 22, 18]],
            ],
            [
                'category' => 'keliling-kota',
                'title' => 'Kuliner Malam Solo Naik Becak',
                'meeting_point' => 'Ngarsopuro Night Market, Solo',
                'featured' => false,
                'harga' => [['Reguler', 185_000, 1, null]],
                'jadwal' => [[3, 20, 11], [17, 20, 20]],
            ],
            [
                'category' => 'aktivitas',
                'title' => 'Paralayang Gunung Banyak Batu',
                'meeting_point' => 'Gunung Banyak, Batu',
                'featured' => false,
                'harga' => [['Tandem Reguler', 425_000, 1, null]],
                'jadwal' => [[8, 12, 4], [26, 12, 12]],
            ],
            [
                'category' => 'pantai',
                'title' => 'Island Hopping Kepulauan Seribu',
                'meeting_point' => 'Dermaga Marina Ancol, Jakarta',
                'featured' => false,
                'harga' => [['Reguler', 850_000, 1, null], ['Rombongan 6+', 780_000, 6, null]],
                'jadwal' => [[13, 28, 9]],
            ],
            [
                /*
                 * Kategori internasional masih ditutup (is_active = false), jadi
                 * trip ini sengaja berstatus draft: ada untuk uji coba internal,
                 * tidak muncul di halaman publik mana pun.
                 */
                'category' => 'internasional',
                'title' => 'Open Trip Kuala Lumpur 3 Hari 2 Malam',
                'meeting_point' => 'Terminal 2 Bandara Soekarno-Hatta',
                'featured' => false,
                'status' => TripStatus::Draft,
                'harga' => [['Reguler', 4_150_000, 1, null]],
                'jadwal' => [[45, 18, 0]],
            ],
        ];

        foreach ($trips as $data) {
            $category = Category::where('slug', $data['category'])->first();

            if (! $category) {
                continue;
            }

            $trip = Trip::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'vendor_id' => null,
                    'category_id' => $category->id,
                    'title' => $data['title'],
                    'description' => 'Perjalanan dikawal pemandu lokal. Kuota dibatasi supaya rombongan tetap nyaman dan jalur tidak padat.',
                    'itinerary' => "Hari 1 — Kumpul di titik temu, perjalanan menuju lokasi, makan malam bersama.\nHari 2 — Aktivitas utama sejak pagi, dokumentasi, kembali ke titik temu sore hari.",
                    'includes' => 'Transportasi lokal, pemandu, tiket masuk, dokumentasi, air mineral.',
                    'excludes' => 'Transportasi menuju titik kumpul, pengeluaran pribadi, asuransi tambahan.',
                    'meeting_point' => $data['meeting_point'],
                    'cover_image' => null,
                    'status' => $data['status'] ?? TripStatus::Published,
                    'is_featured' => $data['featured'],
                    'difficulty_level' => $data['level'] ?? null,
                    'published_at' => now()->subDays(random_int(1, 20)),
                ]
            );

            $trip->schedules()->delete();

            foreach ($data['jadwal'] as [$hariKeDepan, $kuota, $terisi]) {
                $schedule = TripSchedule::create([
                    'trip_id' => $trip->id,
                    'start_date' => now()->addDays($hariKeDepan)->toDateString(),
                    'end_date' => now()->addDays($hariKeDepan + 1)->toDateString(),
                    'quota' => $kuota,
                    'booked_count' => $terisi,
                    'status' => 'published',
                ]);

                foreach ($data['harga'] as [$label, $harga, $minPax, $maxPax]) {
                    TripPrice::create([
                        'trip_schedule_id' => $schedule->id,
                        'label' => $label,
                        'price' => $harga,
                        'min_pax' => $minPax,
                        'max_pax' => $maxPax,
                    ]);
                }
            }
        }
    }
}
