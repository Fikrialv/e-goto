<?php

use App\Enums\IdType;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Rem pada tiga titik tulis (D7, checklist keamanan PLAN.md §10).
 *
 * Booking menahan kuota sejak dibuat, jadi tanpa rem satu akun bisa memborong
 * kursi lewat pesanan yang tidak pernah dibayar. Unggah bukti menulis berkas ke
 * disk, jadi tanpa rem satu akun bisa memenuhi penyimpanan.
 */
it('menahan banjir pemesanan dari satu akun', function () {
    $schedule = jadwalUntukBooking(IdType::None, quota: 50, harga: 200_000);
    $user = User::factory()->customer()->create();

    // Lima permintaan pertama lolos (batas: 5 per menit per akun).
    foreach (range(1, 5) as $i) {
        $this->actingAs($user)
            ->post("/booking/{$schedule->id}", ['participants' => [['full_name' => "Peserta {$i}"]]])
            ->assertRedirect();
    }

    $this->actingAs($user)
        ->post("/booking/{$schedule->id}", ['participants' => [['full_name' => 'Peserta keenam']]])
        ->assertStatus(429);

    expect(Booking::count())->toBe(5)
        ->and($schedule->fresh()->booked_count)->toBe(5);
});

it('membatasi unggahan bukti bayar berulang', function () {
    Storage::fake('local');

    $booking = bookingSiapBayar();

    foreach (range(1, 10) as $i) {
        unggahBukti($booking, "bukti-percobaan-{$i}")->assertRedirect();
    }

    unggahBukti($booking, 'bukti-kesebelas')->assertStatus(429);
});

it('membatasi percobaan masuk yang gagal berulang', function () {
    User::factory()->customer()->create(['email' => 'rina@contoh.test']);

    foreach (range(1, 5) as $i) {
        $this->post('/masuk', ['email' => 'rina@contoh.test', 'password' => 'salah'])
            ->assertSessionHasErrors('email');
    }

    $this->post('/masuk', ['email' => 'rina@contoh.test', 'password' => 'salah'])
        ->assertStatus(429);
});
