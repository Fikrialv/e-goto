<x-layouts.app>
    @php
        /*
         * Hero mengambil trip pilihan pertama yang punya sampul; kalau belum
         * ada satu pun sampul terunggah, trip pilihan pertama tetap dipakai dan
         * bidang fotonya jatuh ke x-media-fallback. Yang tidak pernah dilakukan:
         * menambal dengan foto stok (GUIDE.md — Fallback State).
         */
        $heroTrip = $featuredTrips->firstWhere(fn ($trip) => filled($trip->cover_image))
            ?? $featuredTrips->first();
        $statsLengkap = $stats['tripTerlaksana'] > 0 && $stats['mitraAktif'] > 0 && $stats['pesertaTerlayani'] > 0;
    @endphp

    {{-- Hero: tipografi yang bekerja. Sejak heading & body sama-sama sans
         (Plus Jakarta Sans + Inter), kontrasnya dibawa `.text-hero` — ukuran
         melompat jauh + weight 800 + tracking rapat. --}}
    <section class="relative border-b border-mist-200 bg-mist-100 {{ $categories->isNotEmpty() ? 'pb-28 lg:pb-24' : '' }}">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-12 lg:gap-10 lg:px-8 lg:py-24">
            <div class="lg:col-span-5">
                <p class="text-xs font-semibold tracking-[0.18em] text-amber-600 uppercase">Open trip &amp; aktivitas wisata</p>

                <h1 class="font-display text-hero mt-4 text-teal-900">
                    Jalan bareng,<br class="hidden sm:block">
                    <span class="text-amber-600">urusannya beres.</span>
                </h1>

                <p class="mt-6 max-w-xl text-base leading-relaxed text-teal-600 sm:text-lg">
                    Pilih jadwal, lihat sisa kursi apa adanya, pesan tanpa berbalas pesan berjam-jam.
                    Penyelenggaranya lokal, tiketnya digital.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#kategori"
                       class="rounded-full bg-amber-600 px-6 py-3 text-sm font-medium text-mist-50 transition duration-200 ease-out hover:-translate-y-0.5 hover:bg-amber-700 hover:shadow-lg hover:shadow-amber-600/20">
                        Jelajahi kategori
                    </a>
                    <a href="#jadwal-terdekat"
                       class="rounded-full border border-teal-200 px-6 py-3 text-sm font-medium text-teal-700 transition-colors hover:border-teal-400">
                        Lihat jadwal terdekat
                    </a>
                </div>
            </div>

            <div class="lg:col-span-7">
                <x-hero-slider :trips="$featuredTrips" />
            </div>
        </div>

        {{-- Kartu filter melayang, menumpuk ke foto hero di layar lebar. Isinya
             mengantar ke halaman kategori yang filternya memang sudah ada —
             bukan kotak pencarian hiasan yang tidak menuju ke mana-mana. --}}
        @if ($categories->isNotEmpty())
            <div class="absolute inset-x-0 bottom-0 translate-y-1/2 px-4 sm:px-6 lg:px-8">
                <form method="GET" x-data="{ kategori: '{{ $categories->first()->slug }}' }"
                      :action="'{{ url('/kategori') }}/' + kategori"
                      action="{{ route('categories.show', $categories->first()) }}"
                      class="mx-auto grid max-w-4xl gap-4 rounded-3xl border border-mist-200 bg-mist-50 p-5 shadow-xl shadow-teal-900/5 sm:grid-cols-[1.2fr_1fr_auto] sm:items-end sm:p-6">
                    <label class="block">
                        <span class="text-xs font-semibold tracking-wide text-teal-500 uppercase">Mau ke mana</span>
                        <select x-model="kategori"
                                class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 focus:border-teal-400 focus:ring-4 focus:ring-teal-400/15 focus:outline-none">
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold tracking-wide text-teal-500 uppercase">Berangkat setelah</span>
                        <input type="date" name="tanggal_mulai" min="{{ today()->toDateString() }}"
                               class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 focus:border-teal-400 focus:ring-4 focus:ring-teal-400/15 focus:outline-none">
                    </label>

                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-teal-800 px-6 py-3 text-sm font-medium text-mist-50 transition duration-200 ease-out hover:-translate-y-0.5 hover:bg-teal-900 hover:shadow-lg hover:shadow-teal-900/15">
                        <x-lucide-search class="size-4" aria-hidden="true" />
                        Cari trip
                    </button>
                </form>
            </div>
        @endif
    </section>

    {{-- Baris statistik. Hanya muncul kalau ketiga angkanya sudah berarti —
         angka nol di homepage lebih merugikan daripada seksi yang absen. --}}
    @if ($statsLengkap)
        <section class="{{ $categories->isNotEmpty() ? 'pt-24 lg:pt-20' : 'pt-16' }}">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <dl class="grid gap-8 border-y border-mist-200 py-10 sm:grid-cols-3">
                    <x-stat-bar :value="$stats['tripTerlaksana']" label="Trip sudah berangkat" />
                    <x-stat-bar :value="$stats['pesertaTerlayani']" label="Peserta terlayani" suffix="+" />
                    <x-stat-bar :value="$stats['mitraAktif']" label="Mitra penyelenggara aktif" />
                </dl>
            </div>
        </section>
    @endif

    {{-- Trip Populer --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20 {{ ($categories->isNotEmpty() && ! $statsLengkap) ? 'pt-28 lg:pt-28' : '' }}">
        <x-section-heading
            eyebrow="Pilihan"
            title="Trip populer"
            subtitle="Yang paling sering ditanyakan dan cepat penuh." />

        @if ($featuredTrips->isEmpty())
            <x-empty-state class="mt-10"
                title="Belum ada trip pilihan"
                message="Trip unggulan akan muncul di sini begitu penyelenggara menerbitkan jadwalnya.">
                <x-slot:icon><x-lucide-compass class="size-6" /></x-slot:icon>
            </x-empty-state>
        @else
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredTrips as $trip)
                    <x-trip-card :trip="$trip" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Jadwal Terdekat: sengaja berbentuk daftar, bukan kartu lagi — yang dicari
         pengunjung di blok ini tanggalnya, bukan gambarnya. --}}
    <section id="jadwal-terdekat" class="border-y border-mist-200 bg-mist-100">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <x-section-heading
                eyebrow="Berangkat sebentar lagi"
                title="Jadwal terdekat"
                subtitle="Urut dari tanggal paling dekat, hanya yang kursinya masih ada." />

            @if ($upcomingSchedules->isEmpty())
                <x-empty-state class="mt-10"
                    title="Belum ada jadwal dibuka"
                    message="Semua jadwal terdekat sedang kosong. Coba lihat kategori untuk trip lain.">
                    <x-slot:icon><x-lucide-calendar class="size-6" /></x-slot:icon>
                </x-empty-state>
            @else
                <ul class="mt-10 divide-y divide-mist-300 border-t border-mist-300">
                    @foreach ($upcomingSchedules as $schedule)
                        @php($remaining = $schedule->remainingQuota())
                        <li class="grid gap-3 py-5 sm:grid-cols-12 sm:items-center sm:gap-6">
                            <div class="sm:col-span-2">
                                <p class="font-display text-2xl leading-none font-bold text-teal-900">
                                    {{ $schedule->start_date->translatedFormat('j M') }}
                                </p>
                                <p class="mt-1 text-xs text-teal-500">{{ $schedule->start_date->translatedFormat('Y') }}</p>
                            </div>

                            <div class="sm:col-span-6">
                                <p class="text-xs tracking-wide text-teal-500 uppercase">{{ $schedule->trip->category->name }}</p>
                                <h3 class="font-display text-lg font-bold text-teal-900">
                                    <a href="{{ route('trips.show', $schedule->trip) }}" class="transition-colors hover:text-amber-600">
                                        {{ $schedule->trip->title }}
                                    </a>
                                </h3>
                            </div>

                            <div class="sm:col-span-2">
                                <x-price-tag :amount="$schedule->prices->min('price')" label="mulai dari" size="sm" />
                            </div>

                            <div class="sm:col-span-2 sm:text-right">
                                @if ($remaining <= 3)
                                    <x-status-badge tone="warning">
                                        <x-lucide-armchair class="size-3.5" aria-hidden="true" />
                                        Sisa {{ $remaining }} kursi
                                    </x-status-badge>
                                @else
                                    <x-status-badge tone="success">
                                        <x-lucide-armchair class="size-3.5" aria-hidden="true" />
                                        Sisa {{ $remaining }} kursi
                                    </x-status-badge>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- Grid kategori --}}
    <section id="kategori" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <x-section-heading
            eyebrow="Mau ke mana"
            title="Jelajahi per kategori"
            subtitle="Tiap kategori punya syarat identitas berbeda — pendakian butuh NIK, jalan-jalan kota tidak." />

        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category) }}"
                   class="group flex items-center gap-4 rounded-2xl border border-mist-200 bg-white px-6 py-6 transition duration-200 ease-out hover:-translate-y-0.5 hover:border-amber-500 hover:shadow-lg hover:shadow-teal-900/5">
                    <x-icon-circle class="transition-colors group-hover:bg-amber-100 group-hover:text-amber-700">
                        @svg('lucide-'.($category->icon ?: 'compass'))
                    </x-icon-circle>

                    <span class="min-w-0 flex-1">
                        <span class="font-display block text-xl font-bold text-teal-900 transition-colors group-hover:text-amber-600">
                            {{ $category->name }}
                        </span>
                        <span class="mt-1 block text-sm text-teal-500">
                            {{ $category->trips_count }} trip tersedia
                        </span>
                    </span>

                    <x-lucide-arrow-right class="size-5 shrink-0 text-teal-400 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                </a>
            @endforeach
        </div>
    </section>

    {{-- Jadi Mitra: satu gambar, satu ajakan. Sengaja tidak dibikin blok
         bertingkat — halaman onboarding-nya yang menjelaskan detail. --}}
    <section class="border-t border-mist-200 bg-mist-100">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-14 lg:px-8">
            <x-trip-image :src="$heroTrip?->cover_image" alt="Suasana trip E-GOTO"
                          caption="Trip bersama mitra E-GOTO" fallback-icon="handshake"
                          class="aspect-[16/10] w-full rounded-3xl" />

            <div>
                <p class="text-xs font-semibold tracking-[0.18em] text-amber-600 uppercase">Untuk penyelenggara</p>
                <h2 class="font-display mt-3 text-2xl font-bold text-teal-900 sm:text-3xl">Punya trip sendiri?</h2>
                <p class="mt-3 max-w-md text-sm leading-relaxed text-teal-700">
                    Buka trip Anda di E-GOTO. Kami yang urus halaman, pemesanan, dan pembayaran —
                    Anda fokus jalanin tripnya.
                </p>

                <a href="{{ route('partners.show') }}"
                   class="mt-7 inline-flex items-center gap-2 rounded-full bg-teal-800 px-6 py-3 text-sm font-medium text-mist-50 transition duration-200 ease-out hover:-translate-y-0.5 hover:bg-teal-900 hover:shadow-lg hover:shadow-teal-900/15">
                    <x-lucide-handshake class="size-4" aria-hidden="true" />
                    Jadi Mitra E-GOTO
                </a>
            </div>
        </div>
    </section>
</x-layouts.app>
