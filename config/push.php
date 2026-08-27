<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Web Push (D12)
    |--------------------------------------------------------------------------
    |
    | Kunci VAPID. Selama `public_key` kosong, tombol "Nyalakan notifikasi" tidak
    | dirender dan tidak ada izin browser yang diminta — pola yang sama dengan
    | tombol Google sebelum kredensialnya masuk.
    |
    | Izin notifikasi HANYA boleh diminta setelah pengunjung menekan tombol itu.
    | Prompt yang muncul sendiri saat halaman dibuka hampir selalu ditolak, dan
    | penolakan itu permanen per browser — sekali mati, tidak bisa diminta ulang
    | lewat kode.
    |
    | Isi notifikasi dibatasi kode booking, judul trip, dan waktu. NIK/paspor
    | tidak boleh ikut: isinya melewati server push browser (FCM/Mozilla/Apple),
    | jadi diperlakukan sebagai kanal luar seperti WhatsApp.
    |
    */

    'public_key' => env('VAPID_PUBLIC_KEY'),

    'private_key' => env('VAPID_PRIVATE_KEY'),

    'subject' => env('VAPID_SUBJECT', 'mailto:admin@e-goto.test'),

];
