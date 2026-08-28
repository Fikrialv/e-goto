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
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#077C82">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}" sizes="192x192" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-mist-50 text-teal-800 font-sans">
    <x-loading-splash />

    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-teal-800 focus:px-4 focus:py-2 focus:text-mist-50">
        Lompat ke konten
    </a>

    <header x-data="{ menuTerbuka: false }" class="sticky top-0 z-40 border-b border-mist-200 bg-mist-50/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="font-display text-2xl font-bold tracking-tight text-teal-900">
                E<span class="text-amber-600">·</span>GOTO
            </a>

            <nav class="hidden items-center gap-7 text-sm lg:flex" aria-label="Kategori">
                @foreach ($navCategories as $navCategory)
                    <a href="{{ route('categories.show', $navCategory) }}"
                       class="text-teal-600 transition-colors hover:text-amber-600">{{ $navCategory->name }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                @guest
                    <a href="{{ route('login') }}" class="text-sm text-teal-700 hover:text-amber-600">Masuk</a>
                    <a href="{{ route('register') }}"
                       class="rounded-full bg-amber-600 px-4 py-2 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                        Daftar
                    </a>
                @else
                    <div x-data="{ akunTerbuka: false }" class="relative" @keydown.escape.window="akunTerbuka = false">
                        <button type="button" @click="akunTerbuka = !akunTerbuka" :aria-expanded="akunTerbuka"
                                class="flex items-center gap-2 rounded-full border border-mist-300 py-1.5 pr-3 pl-1.5 text-sm text-teal-700 hover:border-mist-400">
                            <span class="flex size-7 items-center justify-center rounded-full bg-teal-700 text-xs font-semibold text-mist-50">
                                {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            {{ Str::limit(auth()->user()->name, 14) }}
                        </button>

                        <div x-show="akunTerbuka" x-cloak x-transition.opacity @click.outside="akunTerbuka = false"
                             class="absolute right-0 z-50 mt-2 w-48 rounded-2xl border border-mist-200 bg-mist-50 p-1.5 shadow-sm">
                            <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2 text-sm text-teal-700 hover:bg-mist-100">Profil</a>
                            <a href="{{ route('bookings.index') }}" class="block rounded-xl px-3 py-2 text-sm text-teal-700 hover:bg-mist-100">Booking Saya</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full rounded-xl px-3 py-2 text-left text-sm text-teal-700 hover:bg-mist-100">Keluar</button>
                            </form>
                        </div>
                    </div>
                @endguest
            </div>

            <button type="button" @click="menuTerbuka = !menuTerbuka"
                    class="rounded-full border border-mist-300 px-3 py-2 text-sm text-teal-700 lg:hidden"
                    :aria-expanded="menuTerbuka" aria-controls="menu-mobile">
                <span x-text="menuTerbuka ? 'Tutup' : 'Menu'">Menu</span>
            </button>
        </div>

        <div id="menu-mobile" x-show="menuTerbuka" x-cloak x-transition.opacity class="border-t border-mist-200 lg:hidden">
            <nav class="mx-auto grid max-w-6xl gap-1 px-4 py-3 sm:grid-cols-2 sm:px-6" aria-label="Kategori (mobile)">
                @foreach ($navCategories as $navCategory)
                    <a href="{{ route('categories.show', $navCategory) }}"
                       class="rounded-lg px-3 py-2 text-sm text-teal-700 hover:bg-mist-100">{{ $navCategory->name }}</a>
                @endforeach
            </nav>

            <div class="mx-auto max-w-6xl border-t border-mist-200 px-4 py-3 sm:px-6">
                @guest
                    <div class="flex gap-2">
                        <a href="{{ route('login') }}"
                           class="flex-1 rounded-full border border-mist-300 px-4 py-2 text-center text-sm text-teal-700">Masuk</a>
                        <a href="{{ route('register') }}"
                           class="flex-1 rounded-full bg-amber-600 px-4 py-2 text-center text-sm font-medium text-mist-50">Daftar</a>
                    </div>
                @else
                    <div class="grid gap-1">
                        <a href="{{ route('profile.edit') }}" class="rounded-lg px-3 py-2 text-sm text-teal-700 hover:bg-mist-100">Profil</a>
                        <a href="{{ route('bookings.index') }}" class="rounded-lg px-3 py-2 text-sm text-teal-700 hover:bg-mist-100">Booking Saya</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-teal-700 hover:bg-mist-100">Keluar</button>
                        </form>
                    </div>
                @endguest
            </div>
        </div>
    </header>

    <main id="konten">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-mist-200 bg-mist-100">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
                <p class="font-display text-xl font-bold text-teal-900">E·GOTO</p>
                <p class="mt-2 max-w-xs text-sm leading-relaxed text-teal-600">
                    Kumpulan open trip dan aktivitas wisata dari penyelenggara lokal. Pesan, bayar, tunjukkan tiket.
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Jelajahi</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($navCategories as $navCategory)
                        <li>
                            <a href="{{ route('categories.show', $navCategory) }}"
                               class="text-teal-700 hover:text-amber-600">{{ $navCategory->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Bantuan</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('pages.faq') }}" class="text-teal-700 hover:text-amber-600">FAQ</a></li>
                    <li><a href="{{ route('pages.terms') }}" class="text-teal-700 hover:text-amber-600">Syarat &amp; Ketentuan</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="text-teal-700 hover:text-amber-600">Kebijakan Privasi</a></li>
                    <li><a href="{{ route('private-trip.show') }}" class="text-teal-700 hover:text-amber-600">Private Trip</a></li>
                    <li><a href="{{ route('partners.show') }}" class="text-teal-700 hover:text-amber-600">Jadi Mitra E-GOTO</a></li>
                </ul>
                <p class="mt-3 text-sm leading-relaxed text-teal-600">
                    Butuh trip khusus rombongan? Hubungi admin untuk private trip.
                </p>
            </div>
        </div>

        <div class="border-t border-mist-200">
            <p class="mx-auto max-w-6xl px-4 py-5 text-xs text-teal-500 sm:px-6 lg:px-8">
                &copy; {{ now()->year }} E-GOTO.
            </p>
        </div>
    </footer>
    <x-chat-widget />
</body>
</html>
