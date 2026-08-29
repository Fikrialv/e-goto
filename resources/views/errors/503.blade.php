{{--
    Halaman mode maintenance, dipakai lewat:
        php artisan down --render="errors::503" --retry=60

    Sengaja berdiri sendiri: gaya ditulis inline, tidak ada @vite, tidak ada
    query, tidak ada komponen layout. Halaman ini justru harus tampil saat
    aplikasinya sedang tidak bisa diandalkan — kalau ia bergantung pada manifest
    Vite atau database, ia akan ikut gagal tepat saat paling dibutuhkan.

    Satu-satunya berkas luar yang dirujuk adalah logo, dan itu dilayani langsung
    oleh web server dari public/ tanpa melewati Laravel.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sebentar ya — E-GOTO</title>
    <link rel="icon" type="image/svg+xml" href="/images/logo2.svg">
    <style>
        :root {
            --teal-900: #0d3b40;
            --teal-700: #14707a;
            --teal-600: #1a8a95;
            --teal-500: #3aa6ae;
            --mist-100: #eef4f5;
            --mist-200: #dde8ea;
            --mist-300: #c4d6d9;
            --amber-600: #c77b1f;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
            background-color: #ffffff;
            color: var(--teal-900);
            font-family: "Plus Jakarta Sans", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .kartu {
            width: 100%;
            max-width: 34rem;
            text-align: center;
        }

        .tanda {
            width: 4.5rem;
            height: auto;
            margin: 0 auto 2.5rem;
            display: block;
        }

        .label {
            margin: 0 0 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--teal-500);
        }

        h1 {
            margin: 0 0 1.25rem;
            font-size: clamp(1.875rem, 5vw, 2.5rem);
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.02em;
        }

        p {
            margin: 0 auto;
            max-width: 26rem;
            font-size: 1rem;
            color: var(--teal-700);
        }

        .catatan {
            margin-top: 2.5rem;
            padding: 1.125rem 1.5rem;
            border-radius: 1rem;
            background-color: var(--mist-100);
            font-size: 0.875rem;
            color: var(--teal-700);
        }

        .catatan strong { color: var(--teal-900); }

        .kaki {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--mist-200);
            font-size: 0.8125rem;
            color: var(--teal-500);
        }

        .kaki a {
            color: var(--teal-700);
            font-weight: 500;
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .kaki a:hover { color: var(--amber-600); }

        @media (prefers-color-scheme: dark) {
            /* Dark mode penuh belum ada di project ini (backlog), tapi halaman
               ini bisa muncul di perangkat mana pun — cukup jaga kontrasnya
               supaya tidak menyilaukan, bukan bikin tema kedua. */
            body { background-color: #ffffff; }
        }
    </style>
</head>
<body>
    <main class="kartu">
        <img src="/images/logo2.svg" alt="E-GOTO" class="tanda" width="1536" height="1024">

        <p class="label">Sedang perawatan</p>

        <h1>Sebentar ya, kami lagi rapikan sistemnya</h1>

        <p>
            Situsnya lagi diperbarui dan akan balik normal dalam waktu dekat. Data booking,
            pembayaran, dan e-tiket kamu aman — tidak ada yang hilang selama perawatan.
        </p>

        <div class="catatan">
            <strong>Sudah bayar tapi belum diverifikasi?</strong>
            Tidak perlu bayar ulang atau kirim bukti lagi. Antrean verifikasinya jalan terus
            setelah situs kembali.
        </div>

        <p class="kaki">
            Ada yang mendesak? Hubungi kami lewat
            <a href="https://wa.me/{{ config('booking.admin_whatsapp') }}">WhatsApp</a>.
        </p>
    </main>
</body>
</html>
