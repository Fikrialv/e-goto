<?php

use App\Models\Booking;
use App\Services\Payment\ManualQrisGateway;
use App\Services\Payment\QrisDynamicPayload;
use phumin\PromptParse\Library\TLV;
use phumin\PromptParse\Parser;
use phumin\PromptParse\Validate;

/**
 * Booking tanpa simpan ke database: createCharge() cuma membaca kolomnya, dan
 * yang diuji di sini transformasi payload, bukan alur booking.
 */
function bookingContohQris(int $total = 1500437): Booking
{
    return new Booking([
        'code' => 'EG-TEST-01',
        'total_amount' => $total,
        'unique_code' => 437,
        'expires_at' => now()->addHours(2),
    ]);
}

it('menyusun ulang payload statis tanpa perubahan jadi string yang identik', function () {
    $statis = qrisStatisContoh();

    // Round-trip: kalau decode/encode saja sudah menggeser satu karakter, semua
    // hasil turunannya ikut cacat — dan itu baru ketahuan di depan kasir.
    $tags = Parser::parse($statis, subTags: false)->getTags();
    $tanpaCrc = array_values(array_filter($tags, fn ($tag) => $tag->id !== '63'));

    expect(TLV::withCrcTag(TLV::encode($tanpaCrc), '63'))->toBe($statis);
});

it('menyisipkan nominal ke tag 54 dan mengubah metode jadi dinamis', function () {
    $hasil = (new QrisDynamicPayload)->untukNominal(qrisStatisContoh(), 1250417);

    $qr = Parser::parse($hasil, subTags: false);

    expect($qr->getTagValue('54'))->toBe('1250417')
        ->and($qr->getTagValue('01'))->toBe('12')
        ->and($qr->getTagValue('53'))->toBe('360')
        ->and($qr->getTagValue('59'))->toBe('E-GOTO INDONESIA');
});

it('menghasilkan CRC yang sah dan tag tingkat atas berurutan menaik', function () {
    $hasil = (new QrisDynamicPayload)->untukNominal(qrisStatisContoh(), 890123);

    expect(Validate::verify($hasil))->toBeTrue();

    $ids = array_map(fn ($tag) => $tag->id, Parser::parse($hasil, subTags: false)->getTags());
    $urut = $ids;
    sort($urut);

    expect($ids)->toBe($urut)
        ->and($ids)->toContain('54');
});

it('mengganti nominal lama, bukan menumpuk tag 54 kedua', function () {
    $sekali = (new QrisDynamicPayload)->untukNominal(qrisStatisContoh(), 500000);
    $duaKali = (new QrisDynamicPayload)->untukNominal($sekali, 750000);

    $ids = array_map(fn ($tag) => $tag->id, Parser::parse($duaKali, subTags: false)->getTags());

    expect(array_count_values($ids)['54'])->toBe(1)
        ->and(Parser::parse($duaKali, subTags: false)->getTagValue('54'))->toBe('750000');
});

it('menolak payload yang CRC-nya sudah cacat', function () {
    $rusak = substr(qrisStatisContoh(), 0, -4).'0000';

    expect(fn () => (new QrisDynamicPayload)->untukNominal($rusak, 100000))
        ->toThrow(InvalidArgumentException::class);
});

it('menolak nominal nol atau minus', function () {
    expect(fn () => (new QrisDynamicPayload)->untukNominal(qrisStatisContoh(), 0))
        ->toThrow(InvalidArgumentException::class);
});

it('tidak menghasilkan payload selama QRIS_STATIC_PAYLOAD kosong', function () {
    config()->set('booking.qris_static_payload', null);

    $instruksi = app(ManualQrisGateway::class)->createCharge(bookingContohQris());

    expect($instruksi->qrisPayload)->toBeNull()
        ->and($instruksi->qrisImagePath)->not->toBeEmpty();
});

it('mengunci nominal booking ke dalam QR saat payload statis terisi', function () {
    config()->set('booking.qris_static_payload', qrisStatisContoh());

    $booking = bookingContohQris();
    $instruksi = app(ManualQrisGateway::class)->createCharge($booking);

    expect($instruksi->qrisPayload)->not->toBeNull()
        ->and(Parser::parse($instruksi->qrisPayload, subTags: false)->getTagValue('54'))
        ->toBe((string) $booking->total_amount);
});

it('jatuh ke gambar statis kalau payload di config cacat', function () {
    config()->set('booking.qris_static_payload', 'bukan-payload-emvco');

    $instruksi = app(ManualQrisGateway::class)->createCharge(bookingContohQris());

    expect($instruksi->qrisPayload)->toBeNull();
});
