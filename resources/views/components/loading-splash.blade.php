{{--
    Splash awal. Menutup layar sampai Alpine dan CSS siap, lalu dilepas oleh
    resources/js/app.js. Tiga penjaga supaya tidak pernah mengunci halaman:
    - `<noscript>` menyembunyikannya kalau JavaScript mati,
    - app.js melepasnya pada DOMContentLoaded (bukan window.load, yang masih
      menunggu semua gambar),
    - animasinya mati sendiri saat prefers-reduced-motion aktif.
    Memakai logo2 (bentuk saja) — wordmark jadi tidak terbaca di ukuran ini.
--}}
<div id="splash" class="splash" role="status" aria-live="polite">
    <img src="{{ asset('images/logo2.svg') }}" alt="Memuat E-GOTO" width="1536" height="1024"
         class="splash-mark" fetchpriority="high">
</div>

<noscript>
    <style>
        .splash { display: none; }
    </style>
</noscript>
