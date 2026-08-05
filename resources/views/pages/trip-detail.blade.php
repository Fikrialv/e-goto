@php
    /*
     * Galeri = cover + gambar tambahan. Semua path hanya dilewatkan ke
     * Storage::url di komponen gambar; tidak ada HTML dari database yang
     * dirender mentah di halaman ini (deskripsi & itinerary diisi mitra,
     * jadi seluruhnya lewat escaping Blade `{{ }}` — bukan `{!! !!}`).
     */
    $galeri = collect([$trip->cover_image])
        ->merge($trip->images->pluck('path'))
        ->filter()
        ->values();

    $jadwal = $trip->schedules;
    $hargaTermurah = $trip->startingPrice();
@endphp

<x-layouts.app :title="$trip->title" :description="Str::limit(strip_tags((string) $trip->description), 150)">
    <div class="mx-auto max-w-6xl px-4 pt-8 sm:px-6 lg:px-8">
        <nav class="text-xs text-forest-500" aria-label="Remah roti">
            <a href="{{ route('home') }}" class="hover:text-terracotta-600">Beranda</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('categories.show', $trip->category) }}" class="hover:text-terracotta-600">{{ $trip->category->name }}</a>
            <span aria-hidden="true"> / </span>
            <span class="text-forest-700">{{ $trip->title }}</span>
        </nav>

        <header class="mt-4 max-w-3xl">
            <p class="text-xs font-semibold tracking-[0.18em] text-terracotta-600 uppercase">{{ $trip->category->name }}</p>
            <h1 class="font-display mt-3 text-4xl leading-[1.1] font-semibold text-forest-900 sm:text-5xl">{{ $trip->title }}</h1>

            @if ($trip->meeting_point)
                <p class="mt-4 text-sm text-forest-600">Titik kumpul: <span class="text-forest-800">{{ $trip->meeting_point }}</span></p>
            @endif
        </header>
    </div>

    {{-- Galeri --}}
    {{-- State galeri pakai indeks angka, bukan path gambar: path ikut data dan
         tidak perlu ikut masuk ke ekspresi Alpine. --}}
    <section class="mx-auto mt-8 max-w-6xl px-4 sm:px-6 lg:px-8" x-data="{ utama: 0 }">
        <div class="overflow-hidden rounded-3xl border border-sand-200">
            @if ($galeri->isEmpty())
                <x-trip-image :caption="$trip->category->name" :eager="true" class="aspect-[16/9] w-full" />
            @else
                @foreach ($galeri as $indeks => $gambar)
                    <div x-show="utama === {{ $indeks }}" @if (! $loop->first) x-cloak @endif>
                        <x-trip-image :src="$gambar" :alt="$trip->title" :eager="$loop->first" class="aspect-[16/9] w-full" />
                    </div>
                @endforeach
            @endif
        </div>

        @if ($galeri->count() > 1)
            <ul class="mt-3 flex gap-3 overflow-x-auto pb-1">
                @foreach ($galeri as $indeks => $gambar)
                    <li class="shrink-0">
                        <button type="button" @click="utama = {{ $indeks }}"
                                :class="utama === {{ $indeks }} ? 'ring-2 ring-terracotta-600' : 'ring-1 ring-sand-300'"
                                class="block overflow-hidden rounded-xl"
                                aria-label="Lihat gambar {{ $loop->iteration }}">
                            <x-trip-image :src="$gambar" :alt="''" class="h-16 w-24" />
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:grid lg:grid-cols-12 lg:gap-12 lg:px-8">
        {{-- Kolom kiri: deskripsi, itinerary, termasuk/tidak termasuk --}}
        <div class="lg:col-span-7">
            @if ($trip->description)
                <div class="max-w-2xl">
                    <h2 class="font-display text-2xl font-semibold text-forest-900">Tentang trip ini</h2>
                    <p class="mt-3 leading-relaxed whitespace-pre-line text-forest-700">{{ $trip->description }}</p>
                </div>
            @endif

            <div x-data="{ terbuka: 'itinerary' }" class="mt-10 divide-y divide-sand-200 border-y border-sand-200">
                @foreach ([
                    'itinerary' => ['Rencana perjalanan', $trip->itinerary],
                    'includes' => ['Sudah termasuk', $trip->includes],
                    'excludes' => ['Belum termasuk', $trip->excludes],
                ] as $kunci => [$judul, $isi])
                    @continue (blank($isi))
                    <section>
                        <h2>
                            <button type="button" @click="terbuka = (terbuka === '{{ $kunci }}' ? null : '{{ $kunci }}')"
                                    class="flex w-full items-center justify-between gap-4 py-5 text-left"
                                    :aria-expanded="terbuka === '{{ $kunci }}'">
                                <span class="font-display text-xl font-semibold text-forest-900">{{ $judul }}</span>
                                <span aria-hidden="true" class="text-forest-500"
                                      x-text="terbuka === '{{ $kunci }}' ? '−' : '+'">+</span>
                            </button>
                        </h2>
                        <div x-show="terbuka === '{{ $kunci }}'" x-transition.opacity class="pb-6">
                            <p class="leading-relaxed whitespace-pre-line text-forest-700">{{ $isi }}</p>
                        </div>
                    </section>
                @endforeach
            </div>

            @if ($relatedTrips->isNotEmpty())
                <section class="mt-14">
                    <h2 class="font-display text-2xl font-semibold text-forest-900">Trip lain di {{ $trip->category->name }}</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        @foreach ($relatedTrips as $lain)
                            <x-trip-card :trip="$lain" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- Kolom kanan: jadwal, kuota, harga bertingkat, CTA --}}
        <aside id="jadwal" class="mt-12 lg:col-span-5 lg:mt-0">
            <div class="lg:sticky lg:top-24">
                <div class="rounded-3xl border border-sand-200 bg-white/70 p-6">
                    <x-price-tag :amount="$hargaTermurah" label="mulai dari" size="lg" />

                    <h2 class="mt-6 text-xs font-semibold tracking-wide text-forest-500 uppercase">Jadwal tersedia</h2>

                    @if ($jadwal->isEmpty())
                        <p class="mt-3 text-sm leading-relaxed text-forest-600">
                            Belum ada jadwal terbuka untuk trip ini. Jadwal baru biasanya dibuka tiap awal bulan.
                        </p>
                    @else
                        <ul class="mt-3 space-y-3">
                            @foreach ($jadwal as $item)
                                @php($sisa = $item->remainingQuota())
                                <li class="rounded-2xl border border-sand-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-display text-lg font-semibold text-forest-900">
                                                {{ $item->start_date->translatedFormat('j F Y') }}
                                            </p>
                                            @if ($item->end_date)
                                                <p class="text-xs text-forest-500">
                                                    sampai {{ $item->end_date->translatedFormat('j F Y') }}
                                                </p>
                                            @endif
                                        </div>

                                        @if ($item->isSoldOut())
                                            <x-status-badge tone="danger">Kuota habis</x-status-badge>
                                        @elseif ($sisa <= 3)
                                            <x-status-badge tone="warning">Sisa {{ $sisa }} kursi</x-status-badge>
                                        @else
                                            <x-status-badge tone="success">Sisa {{ $sisa }} kursi</x-status-badge>
                                        @endif
                                    </div>

                                    @if ($item->prices->isNotEmpty())
                                        <dl class="mt-3 space-y-1.5 border-t border-sand-200 pt-3 text-sm">
                                            @foreach ($item->prices as $harga)
                                                <div class="flex items-baseline justify-between gap-3">
                                                    <dt class="text-forest-600">
                                                        {{ $harga->label }}
                                                        <span class="text-xs text-forest-500">
                                                            ({{ $harga->min_pax }}@if ($harga->max_pax)–{{ $harga->max_pax }}@else+ @endif pax)
                                                        </span>
                                                    </dt>
                                                    <dd class="font-medium text-forest-900">
                                                        Rp{{ number_format($harga->price, 0, ',', '.') }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- CTA booking: alurnya baru dibangun di D3-D4. Tombol sengaja
                         nonaktif dan jujur menyebut statusnya, bukan menautkan ke
                         route yang belum ada. --}}
                    <button type="button" disabled
                            class="mt-6 w-full cursor-not-allowed rounded-full bg-terracotta-600/40 px-6 py-3 text-sm font-medium text-sand-50">
                        Booking sekarang
                    </button>
                    <p class="mt-2 text-center text-xs text-forest-500">
                        Pemesanan online dibuka sebentar lagi.
                    </p>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.app>
