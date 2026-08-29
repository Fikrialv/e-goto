<?php

namespace App\Services\Payment;

use InvalidArgumentException;
use phumin\PromptParse\Library\TLV;
use phumin\PromptParse\Parser;
use phumin\PromptParse\Validate;

/**
 * Mengubah payload QRIS statis merchant jadi payload bernominal.
 *
 * Bukan payment gateway: rekening tujuannya tetap rekening yang sama, dan
 * pembayarannya tetap diverifikasi admin. Yang dikerjakan di sini murni
 * transformasi teks — nominal (termasuk kode unik) dikunci di dalam QR supaya
 * pembayar tidak bisa salah ketik angka, sehingga pencocokan mutasi jadi rapat.
 *
 * Kelas ini sengaja tanpa ketergantungan Laravel supaya bisa diuji sebagai
 * fungsi murni.
 */
class QrisDynamicPayload
{
    /** Point of Initiation Method: 11 = statis (bisa dipindai berkali-kali), 12 = dinamis. */
    private const TAG_METODE = '01';

    private const TAG_NOMINAL = '54';

    private const TAG_CRC = '63';

    /**
     * @param  string  $payloadStatis  String EMVCo hasil pindai QRIS merchant.
     * @param  int  $nominal  Rupiah penuh, sudah termasuk kode unik.
     *
     * @throws InvalidArgumentException Kalau payload cacat atau nominal tidak masuk akal.
     */
    public function untukNominal(string $payloadStatis, int $nominal): string
    {
        $payloadStatis = trim($payloadStatis);

        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal QRIS harus lebih dari nol.');
        }

        /*
         * CRC diperiksa lebih dulu. Payload yang sudah cacat sejak di .env akan
         * menghasilkan QR yang baru ketahuan rusak di depan kasir — kegagalannya
         * harus terjadi di sini, bukan di sana.
         */
        if (! Validate::verify($payloadStatis)) {
            throw new InvalidArgumentException('Payload QRIS statis tidak lolos verifikasi CRC.');
        }

        /*
         * subTags sengaja dimatikan: TLV::encode() milik promptparse menulis
         * sub-tag DAN value induknya sekaligus, jadi tag ber-sub-tag (26/51,
         * data merchant) akan tergandakan kalau ikut diurai. Untuk pekerjaan ini
         * isi tag 26 memang tidak perlu disentuh — cukup diteruskan apa adanya.
         */
        $tags = Parser::parse($payloadStatis, subTags: false)->getTags();

        $hasil = [];

        foreach ($tags as $tag) {
            if ($tag->id === self::TAG_CRC || $tag->id === self::TAG_NOMINAL) {
                continue;
            }

            $hasil[] = $tag->id === self::TAG_METODE
                ? TLV::tag(self::TAG_METODE, '12')
                : $tag;
        }

        $hasil[] = TLV::tag(self::TAG_NOMINAL, (string) $nominal);

        // Tag tingkat atas wajib menaik; tag 54 baru harus jatuh sebelum 58/59/60.
        usort($hasil, fn ($a, $b) => strcmp($a->id, $b->id));

        return TLV::withCrcTag(TLV::encode($hasil), self::TAG_CRC);
    }
}
