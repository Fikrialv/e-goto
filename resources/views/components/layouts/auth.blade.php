@props([
    'title' => null,
    'description' => 'Open trip dan aktivitas wisata dari penyelenggara lokal terpilih.',
])

{{--
    Layout khusus halaman masuk/daftar. Tanpa header dan footer situs: halaman
    ini punya satu tujuan, dan menu kategori di sampingnya cuma menawarkan jalan
    keluar. Jalan kembali ke beranda tetap ada lewat wordmark di atas form.
--}}
<!DOCTYPE html>
<html lang="id" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — E-GOTO' : 'E-GOTO — Jalan bareng, urusannya beres' }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#077C82">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}" sizes="192x192" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-mist-50 text-teal-800 font-sans">
    <x-loading-splash />

    <main>
        {{ $slot }}
    </main>
</body>
</html>
