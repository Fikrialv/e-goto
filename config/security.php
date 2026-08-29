<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Penegakan Content-Security-Policy
    |--------------------------------------------------------------------------
    |
    | Selama `false`, CSP dikirim sebagai `Content-Security-Policy-Report-Only`:
    | browser mencatat pelanggaran di console tanpa memblokir apa pun. Ini
    | disengaja — CSP yang langsung ditegakkan akan mematikan bagian panel
    | Filament tanpa suara, dan kegagalannya tidak muncul di log Laravel.
    |
    | Nyalakan setelah panel admin, panel vendor, dan alur booking→bayar→tiket
    | dibuka sekali dengan console browser terbuka dan tidak ada pelanggaran
    | tersisa.
    |
    */

    'csp_enforce' => (bool) env('SECURITY_CSP_ENFORCE', false),

];
