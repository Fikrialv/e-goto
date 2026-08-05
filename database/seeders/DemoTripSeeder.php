<?php

namespace Database\Seeders;

use App\Enums\TripStatus;
use App\Models\Category;
use App\Models\Trip;
use App\Models\TripPrice;
use App\Models\TripSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data demo secukupnya untuk melihat halaman publik (D2) di tiga breakpoint.
 * Seeder demo lengkap 10–12 trip dengan variasi kuota penuh/hampir penuh
 * dijadwalkan di D7 — jangan digemukkan di sini.
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
                'meeting_point' => 'Basecamp Patak Banteng, Wonosobo',
                'featured' => true,
                'harga' => [['Reguler', 385_000, 1, null], ['Rombongan 5+', 350_000, 5, null]],
                'jadwal' => [[6, 20, 14], [27, 20, 3]],
            ],
            [
                'category' => 'pantai',
                'title' => 'Snorkeling Karimunjawa 3 Hari 2 Malam',
                'meeting_point' => 'Pelabuhan Kartini, Jepara',
                'featured' => true,
                'harga' => [['Reguler', 1_450_000, 1, null], ['Rombongan 6+', 1_325_000, 6, null]],
                // Jadwal kedua sengaja penuh — untuk melihat state "Kuota habis".
                'jadwal' => [[12, 24, 19], [40, 24, 24]],
            ],
            [
                'category' => 'domestik',
                'title' => 'Jelajah Bromo Sunrise dari Malang',
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
                    'status' => TripStatus::Published,
                    'is_featured' => $data['featured'],
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
