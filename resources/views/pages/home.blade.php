<x-layouts.app>
    {{-- Hero: tipografi yang bekerja, tanpa foto latar atau gradient dekoratif. Sejak
         heading & body sama-sama sans (Plus Jakarta Sans + Inter), kontrasnya dibawa
         `.text-hero` — ukuran melompat jauh + weight 800 + tracking rapat. --}}
    <section class="border-b border-mist-200 bg-mist-100">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-12 lg:gap-8 lg:px-8 lg:py-24">
            <div class="lg:col-span-7">
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
                       class="rounded-full bg-amber-600 px-6 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                        Jelajahi kategori
                    </a>
                    <a href="#jadwal-terdekat"
                       class="rounded-full border border-teal-200 px-6 py-3 text-sm font-medium text-teal-700 transition-colors hover:border-teal-400">
                        Lihat jadwal terdekat
                    </a>
                </div>
            </div>

            <dl class="grid grid-cols-3 gap-4 self-end lg:col-span-4 lg:col-start-9 lg:grid-cols-1 lg:gap-6">
                <div class="border-t border-mist-300 pt-3">
                    <dt class="text-xs tracking-wide text-teal-500 uppercase">Kategori aktif</dt>
                    <dd class="font-display text-2xl font-bold text-teal-900">{{ $categories->count() }}</dd>
                </div>
                <div class="border-t border-mist-300 pt-3">
                    <dt class="text-xs tracking-wide text-teal-500 uppercase">Jadwal dibuka</dt>
                    <dd class="font-display text-2xl font-bold text-teal-900">{{ $upcomingSchedules->count() }}</dd>
                </div>
                <div class="border-t border-mist-300 pt-3">
                    <dt class="text-xs tracking-wide text-teal-500 uppercase">Tanpa login</dt>
                    <dd class="font-display text-2xl font-bold text-teal-900">Bebas lihat</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- Trip Populer --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <x-section-heading
            eyebrow="Pilihan"
            title="Trip populer"
            subtitle="Yang paling sering ditanyakan dan cepat penuh." />

        @if ($featuredTrips->isEmpty())
            <x-empty-state class="mt-8"
                title="Belum ada trip pilihan"
                message="Trip unggulan akan muncul di sini begitu penyelenggara menerbitkan jadwalnya." />
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
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
                <x-empty-state class="mt-8"
                    title="Belum ada jadwal dibuka"
                    message="Semua jadwal terdekat sedang kosong. Coba lihat kategori untuk trip lain." />
            @else
                <ul class="mt-8 divide-y divide-mist-300 border-t border-mist-300">
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
                                    <x-status-badge tone="warning">Sisa {{ $remaining }} kursi</x-status-badge>
                                @else
                                    <x-status-badge tone="success">Sisa {{ $remaining }} kursi</x-status-badge>
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

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category) }}"
                   class="group flex items-center justify-between gap-4 rounded-2xl border border-mist-200 bg-white/70 px-6 py-6 transition-colors hover:border-amber-500">
                    <span>
                        <span class="font-display block text-xl font-bold text-teal-900 transition-colors group-hover:text-amber-600">
                            {{ $category->name }}
                        </span>
                        <span class="mt-1 block text-sm text-teal-500">
                            {{ $category->trips_count }} trip tersedia
                        </span>
                    </span>
                    <span aria-hidden="true" class="text-teal-400 transition-transform group-hover:translate-x-1">&rarr;</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="border-t border-mist-200 bg-mist-100">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-6 px-4 py-12 sm:px-6 lg:px-8">
            <div class="max-w-xl">
                <p class="text-xs font-semibold tracking-[0.18em] text-amber-600 uppercase">Untuk penyelenggara</p>
                <h2 class="font-display mt-3 text-2xl font-bold text-teal-900 sm:text-3xl">Punya trip sendiri?</h2>
                <p class="mt-2 text-sm leading-relaxed text-teal-700">
                    Buka trip Anda di E-GOTO. Kami yang urus halaman, pemesanan, dan pembayaran.
                </p>
            </div>

            <a href="{{ route('partners.show') }}"
               class="rounded-full bg-teal-800 px-6 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-teal-900">
                Jadi Mitra E-GOTO
            </a>
        </div>
    </section>
</x-layouts.app>
