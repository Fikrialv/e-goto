<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Auth customer (D3): daftar/masuk manual + login Google.
 *
 * Fokus test ini bukan cuma "bisa masuk", tapi juga tiga hal yang mahal kalau
 * salah: role tidak boleh bisa dinaikkan lewat body request, percobaan login
 * harus direm, dan login Google tidak boleh menggandakan akun.
 */
function googleSocialUser(string $email, string $id = '110044', string $name = 'Budi Santoso'): SocialiteUser
{
    $user = new SocialiteUser;

    return $user->map([
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'avatar' => 'https://lh3.googleusercontent.com/contoh',
    ]);
}

beforeEach(function () {
    // Tanpa client_id, route Google sengaja 404 — isi nilai palsu supaya
    // yang diuji alur callback-nya, bukan gerbang konfigurasinya.
    config(['services.google.client_id' => 'client-id-uji', 'services.google.client_secret' => 'rahasia-uji']);
});

it('mendaftarkan customer baru dan mengantarnya ke layar lengkapi profil', function () {
    $response = $this->post('/daftar', [
        'name' => 'Sinta',
        'email' => 'sinta@contoh.test',
        'password' => 'rahasia-kuat-123',
        'password_confirmation' => 'rahasia-kuat-123',
    ]);

    $response->assertRedirect(route('profile.complete'));

    $user = User::query()->where('email', 'sinta@contoh.test')->firstOrFail();

    expect($user->role)->toBe(UserRole::Customer)
        ->and($user->password)->not->toBe('rahasia-kuat-123');

    $this->assertAuthenticatedAs($user);
});

it('menolak kenaikan role lewat body request saat mendaftar', function () {
    $this->post('/daftar', [
        'name' => 'Penyusup',
        'email' => 'penyusup@contoh.test',
        'password' => 'rahasia-kuat-123',
        'password_confirmation' => 'rahasia-kuat-123',
        'role' => 'admin',
    ]);

    expect(User::query()->where('email', 'penyusup@contoh.test')->firstOrFail()->role)
        ->toBe(UserRole::Customer);
});

it('menolak pendaftaran dengan email yang sudah dipakai', function () {
    User::factory()->customer()->create(['email' => 'sudah@contoh.test']);

    $this->post('/daftar', [
        'name' => 'Kembar',
        'email' => 'sudah@contoh.test',
        'password' => 'rahasia-kuat-123',
        'password_confirmation' => 'rahasia-kuat-123',
    ])->assertSessionHasErrors('email');

    expect(User::query()->where('email', 'sudah@contoh.test')->count())->toBe(1);
    $this->assertGuest();
});

it('memasukkan customer dengan kata sandi benar', function () {
    $user = User::factory()->customer()->create(['email' => 'rina@contoh.test']);

    $this->post('/masuk', ['email' => 'rina@contoh.test', 'password' => 'password'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('menolak kata sandi salah tanpa membocorkan apakah email terdaftar', function () {
    User::factory()->customer()->create(['email' => 'rina@contoh.test']);

    $this->post('/masuk', ['email' => 'rina@contoh.test', 'password' => 'salah-total'])
        ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);

    $this->assertGuest();
});

it('merem percobaan login setelah lima kali gagal', function () {
    User::factory()->customer()->create(['email' => 'target@contoh.test']);

    foreach (range(1, 5) as $percobaan) {
        $this->post('/masuk', ['email' => 'target@contoh.test', 'password' => 'salah'])
            ->assertStatus(302);
    }

    $this->post('/masuk', ['email' => 'target@contoh.test', 'password' => 'salah'])
        ->assertStatus(429);
});

it('membuat akun customer baru dari callback Google tanpa kata sandi', function () {
    Socialite::shouldReceive('driver->user')
        ->once()
        ->andReturn(googleSocialUser('baru@gmail.test'));

    $this->get('/auth/google/callback')->assertRedirect(route('profile.complete'));

    $user = User::query()->where('email', 'baru@gmail.test')->firstOrFail();

    expect($user->provider)->toBe('google')
        ->and($user->provider_id)->toBe('110044')
        ->and($user->password)->toBeNull()
        ->and($user->role)->toBe(UserRole::Customer);

    $this->assertAuthenticatedAs($user);
});

it('menautkan akun Google ke akun manual dengan email sama, bukan membuat akun kedua', function () {
    $lama = User::factory()->customer()->create(['email' => 'rina@contoh.test']);

    Socialite::shouldReceive('driver->user')
        ->once()
        ->andReturn(googleSocialUser('rina@contoh.test', id: '998877'));

    $this->get('/auth/google/callback');

    expect(User::query()->where('email', 'rina@contoh.test')->count())->toBe(1)
        ->and($lama->fresh()->provider_id)->toBe('998877');

    $this->assertAuthenticatedAs($lama);
});

it('menyembunyikan login Google selama kredensial belum dipasang', function () {
    config(['services.google.client_id' => null]);

    $this->get('/masuk')->assertOk()->assertDontSee('Masuk dengan Google');
    $this->get('/auth/google/redirect')->assertNotFound();
});

it('mengeluarkan user dan membersihkan sesinya', function () {
    $user = User::factory()->customer()->create();

    $this->actingAs($user)->post('/keluar')->assertRedirect(route('home'));

    expect(Auth::check())->toBeFalse();
});
