<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Berkas pengajuan mitra
    |--------------------------------------------------------------------------
    |
    | Disk NON-publik. Dokumen pengajuan memuat identitas penanggung jawab dan
    | akta usaha — tidak boleh bisa diambil siapa pun yang menebak URL, jadi
    | aksesnya lewat route ber-`role:admin` (pola yang sama dengan bukti bayar).
    |
    */

    'document_disk' => env('PARTNER_DOC_DISK', 'local'),

    'document_directory' => 'pengajuan-mitra',

    'max_documents' => 5,

    /*
    |--------------------------------------------------------------------------
    | Widget chat pihak ketiga (D12)
    |--------------------------------------------------------------------------
    |
    | Kosongkan `chat_widget_id` dan widget tidak dirender sama sekali — pola
    | yang sama dengan tombol Google sebelum kredensialnya masuk. Widget ini
    | HANYA untuk tanya-jawab umum/CS: approve/reject pembayaran dan
    | penerbitan tiket tetap wajib lewat layar verifikasi Filament (D5).
    |
    | Penyedia yang dipakai WAJIB dicantumkan di /kebijakan-privasi sebagai
    | pihak ketiga penerima data — isi percakapan mengalir ke server mereka.
    |
    */

    'chat_widget_provider' => env('CHAT_WIDGET_PROVIDER', 'tawkto'),

    'chat_widget_id' => env('CHAT_WIDGET_ID'),

];
