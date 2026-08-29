<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Masa berlaku booking
    |--------------------------------------------------------------------------
    |
    | Kuota trip ditahan sejak booking dibuat, bukan sejak dibayar. Angka ini
    | menentukan berapa lama kursi ditahan sebelum dilepas kembali oleh command
    | `bookings:expire` (PLAN.md §5.3). Ditaruh di config supaya bisa dilonggarkan
    | saat promo tanpa menyentuh kode.
    |
    */

    'expiry_minutes' => (int) env('BOOKING_EXPIRY_MINUTES', 120),

    /*
    |--------------------------------------------------------------------------
    | Nominal unik
    |--------------------------------------------------------------------------
    |
    | Tiga digit acak yang membuat nominal tiap booking berbeda supaya admin bisa
    | mencocokkan mutasi bank. Kalau 3 digit sudah habis terpakai oleh booking
    | lain yang masih menunggu bayar, generator naik ke 4 digit (PLAN.md §5.1).
    |
    */

    'unique_code_attempts' => (int) env('BOOKING_UNIQUE_CODE_ATTEMPTS', 10),

    /*
    |--------------------------------------------------------------------------
    | Batas peserta per booking
    |--------------------------------------------------------------------------
    |
    | Cap keras: berlaku walau sisa kuota jadwal jauh lebih besar (GUIDE.md
    | "Batas Peserta per Booking", PLAN.md §5.6). Rombongan di atas angka ini
    | punya kebutuhan berbeda (transport, akomodasi, negosiasi harga) dan
    | diarahkan ke Request Private Trip, bukan lewat checkout normal.
    |
    | Angkanya ditaruh di sini supaya form, validasi server, dan tampilan
    | membaca sumber yang sama — batas yang diketik ulang di beberapa tempat
    | cepat berbeda, dan yang paling longgar yang akhirnya menang.
    |
    */

    'max_pax_per_booking' => (int) env('BOOKING_MAX_PAX', 12),

    /*
    |--------------------------------------------------------------------------
    | Pembayaran QRIS statis
    |--------------------------------------------------------------------------
    |
    | Path gambar QRIS merchant, relatif terhadap folder `public/`. Selama file
    | asli belum ditempel, yang tampil adalah placeholder — halaman pembayaran
    | tetap bisa dikembangkan dan diuji tanpa menunggu berkas merchant.
    |
    */

    'qris_image_path' => env('QRIS_IMAGE_PATH', 'images/qris-placeholder.svg'),

    'qris_merchant_name' => env('QRIS_MERCHANT_NAME', 'E-GOTO Indonesia'),

    /*
    |--------------------------------------------------------------------------
    | QRIS bernominal
    |--------------------------------------------------------------------------
    |
    | String EMVCo hasil memindai QRIS merchant E-GOTO (diawali 000201...).
    | Diisi sekali, hasil scan pakai aplikasi pembaca QR biasa. Kalau terisi,
    | halaman bayar menyusun ulang payload ini dengan nominal booking (tag 54)
    | supaya pembayar tidak perlu mengetik angka sendiri.
    |
    | Ini BUKAN payment gateway: rekening tujuannya sama, verifikasinya tetap
    | manual admin. Selama kosong, halaman bayar memakai gambar QRIS statis
    | seperti sebelumnya.
    |
    | Ditaruh di .env, bukan di berkas ter-track git — isinya memang publik
    | (tercetak di QR yang ditempel di kasir), tapi tetap bukan milik repo.
    |
    */

    'qris_static_payload' => env('QRIS_STATIC_PAYLOAD'),

    /*
    |--------------------------------------------------------------------------
    | Estimasi waktu verifikasi pembayaran
    |--------------------------------------------------------------------------
    |
    | Kalimat yang tampil ke customer selama pembayarannya masih `pending`, dan
    | di panel konfirmasi metode pembayaran sebelum QRIS dibuka (D7.6 b & f).
    | Ditaruh di config, bukan diketik di Blade, supaya bisa dilonggarkan saat
    | antrean verifikasi ramai tanpa menyentuh view — dan supaya dua tempat yang
    | menampilkannya tidak pernah berbeda angka.
    |
    */

    'verification_eta' => env('BOOKING_VERIFICATION_ETA', 'maksimal 1x24 jam'),

    /*
    |--------------------------------------------------------------------------
    | Notifikasi admin
    |--------------------------------------------------------------------------
    |
    | Nomor tujuan tombol wa.me saat customer selesai mengunggah bukti bayar.
    | Format internasional tanpa tanda plus, misal 6281234567890.
    |
    */

    'admin_whatsapp' => env('ADMIN_WHATSAPP', '6281234567890'),

    /*
    |--------------------------------------------------------------------------
    | Penyimpanan bukti bayar
    |--------------------------------------------------------------------------
    |
    | Disk NON-publik. Bukti transfer memuat nama dan nomor rekening pengirim,
    | jadi tidak boleh bisa diambil siapa pun yang menebak URL — aksesnya lewat
    | route ber-authorize (PLAN.md §10).
    |
    */

    'proof_disk' => env('BOOKING_PROOF_DISK', 'local'),

    'proof_directory' => 'bukti-bayar',

];
