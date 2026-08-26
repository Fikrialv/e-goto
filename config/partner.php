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

];
