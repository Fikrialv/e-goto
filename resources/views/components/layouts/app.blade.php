@props([
    'title' => null,
    'description' => 'Open trip dan aktivitas wisata dari penyelenggara lokal terpilih.',
])

<!DOCTYPE html>
<html lang="id" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — E-GOTO' : 'E-GOTO — Jalan bareng, urusannya beres' }}</title>
    <meta name="description" content="{{ $description }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-sand-50 text-forest-800 font-sans">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-forest-800 focus:px-4 focus:py-2 focus:text-sand-50">
        Lompat ke konten
    </a>

    <header x-data="{ menuTerbuka: false }" class="sticky top-0 z-40 border-b border-sand-200 bg-sand-50/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="font-display text-2xl font-semibold tracking-tight text-forest-900">
                E<span class="text-terracotta-600">·</span>GOTO
            </a>

            <nav class="hidden items-center gap-7 text-sm lg:flex" aria-label="Kategori">
                @foreach ($navCategories as $navCategory)
                    <a href="{{ route('categories.show', $navCategory) }}"
                       class="text-forest-600 transition-colors hover:text-terracotta-600">{{ $navCategory->name }}</a>
                @endforeach
            </nav>

            <button type="button" @click="menuTerbuka = !menuTerbuka"
                    class="rounded-full border border-sand-300 px-3 py-2 text-sm text-forest-700 lg:hidden"
                    :aria-expanded="menuTerbuka" aria-controls="menu-mobile">
                <span x-text="menuTerbuka ? 'Tutup' : 'Kategori'">Kategori</span>
            </button>
        </div>

        <div id="menu-mobile" x-show="menuTerbuka" x-cloak x-transition.opacity class="border-t border-sand-200 lg:hidden">
            <nav class="mx-auto grid max-w-6xl gap-1 px-4 py-3 sm:grid-cols-2 sm:px-6" aria-label="Kategori (mobile)">
                @foreach ($navCategories as $navCategory)
                    <a href="{{ route('categories.show', $navCategory) }}"
                       class="rounded-lg px-3 py-2 text-sm text-forest-700 hover:bg-sand-100">{{ $navCategory->name }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main id="konten">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-sand-200 bg-sand-100">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
                <p class="font-display text-xl font-semibold text-forest-900">E·GOTO</p>
                <p class="mt-2 max-w-xs text-sm leading-relaxed text-forest-600">
                    Kumpulan open trip dan aktivitas wisata dari penyelenggara lokal. Pesan, bayar, tunjukkan tiket.
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold tracking-widest text-forest-500 uppercase">Jelajahi</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($navCategories as $navCategory)
                        <li>
                            <a href="{{ route('categories.show', $navCategory) }}"
                               class="text-forest-700 hover:text-terracotta-600">{{ $navCategory->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p class="text-xs font-semibold tracking-widest text-forest-500 uppercase">Bantuan</p>
                <p class="mt-3 text-sm leading-relaxed text-forest-600">
                    Butuh trip khusus rombongan? Fitur permintaan private trip menyusul.
                </p>
            </div>
        </div>

        <div class="border-t border-sand-200">
            <p class="mx-auto max-w-6xl px-4 py-5 text-xs text-forest-500 sm:px-6 lg:px-8">
                &copy; {{ now()->year }} E-GOTO.
            </p>
        </div>
    </footer>
</body>
</html>
