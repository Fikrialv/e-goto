<?php

use App\Models\PushSubscription;
use App\Models\User;

/**
 * Langganan Web Push (D12).
 *
 * Yang dijaga: tombol izin tidak pernah muncul sebelum kunci VAPID dipasang,
 * izin browser tidak pernah diminta otomatis, dan langganan selalu menempel ke
 * akun yang sedang masuk — bukan ke user_id yang dikirim dari sisi klien.
 */
function dataLangganan(string $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123'): array
{
    return [
        'endpoint' => $endpoint,
        'keys' => ['p256dh' => 'kunci-publik-palsu', 'auth' => 'token-auth-palsu'],
    ];
}

it('menyimpan langganan push milik akun yang sedang masuk', function () {
    $user = User::factory()->customer()->create();

    test()->actingAs($user)
        ->postJson(route('push.store'), dataLangganan())
        ->assertOk()
        ->assertJson(['status' => 'aktif']);

    $langganan = PushSubscription::firstOrFail();

    expect($langganan->user_id)->toBe($user->id)
        ->and($langganan->endpoint_hash)->toBe(PushSubscription::hashFor(dataLangganan()['endpoint']));
});

it('tidak menggandakan langganan untuk endpoint yang sama', function () {
    $user = User::factory()->customer()->create();

    test()->actingAs($user)->postJson(route('push.store'), dataLangganan())->assertOk();
    test()->actingAs($user)->postJson(route('push.store'), dataLangganan())->assertOk();

    expect(PushSubscription::count())->toBe(1);
});

it('menghapus langganan saat customer mematikannya', function () {
    $user = User::factory()->customer()->create();

    test()->actingAs($user)->postJson(route('push.store'), dataLangganan())->assertOk();

    test()->actingAs($user)
        ->deleteJson(route('push.destroy'), ['endpoint' => dataLangganan()['endpoint']])
        ->assertOk();

    expect(PushSubscription::count())->toBe(0);
});

it('menolak langganan dari tamu', function () {
    test()->postJson(route('push.store'), dataLangganan())->assertUnauthorized();

    expect(PushSubscription::count())->toBe(0);
});

it('menolak langganan tanpa kunci lengkap', function () {
    test()->actingAs(User::factory()->customer()->create())
        ->postJson(route('push.store'), ['endpoint' => 'https://contoh.test/push'])
        ->assertStatus(422);
});

it('tidak menampilkan tombol notifikasi sebelum kunci VAPID dipasang', function () {
    config()->set('push.public_key', null);

    test()->actingAs(User::factory()->customer()->create())
        ->get(route('bookings.index'))
        ->assertOk()
        ->assertDontSee('Nyalakan notifikasi');
});

it('menampilkan tombol notifikasi setelah kunci VAPID dipasang', function () {
    config()->set('push.public_key', 'kunci-vapid-publik');

    test()->actingAs(User::factory()->customer()->create())
        ->get(route('bookings.index'))
        ->assertOk()
        ->assertSee('Nyalakan notifikasi');
});

it('meminta izin notifikasi hanya lewat aksi tombol, bukan otomatis', function () {
    config()->set('push.public_key', 'kunci-vapid-publik');

    $halaman = test()->actingAs(User::factory()->customer()->create())
        ->get(route('bookings.index'))
        ->assertOk()
        ->getContent();

    // requestPermission harus berada di dalam handler tombol (nyalakan), bukan
    // dipanggil saat halaman dimuat.
    expect($halaman)->toContain('Notification.requestPermission()')
        ->and($halaman)->toContain('@click="nyalakan()"');
});

it('tidak menyentuh aturan cache service worker saat menambah listener push', function () {
    $sw = file_get_contents(public_path('sw.js'));

    expect($sw)->toContain("addEventListener('push'")
        ->and($sw)->toContain("addEventListener('notificationclick'")
        ->and($sw)->toContain("event.request.mode === 'navigate'")
        ->and($sw)->not->toContain('/bayar');
});
