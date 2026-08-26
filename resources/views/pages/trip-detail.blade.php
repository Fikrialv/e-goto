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
        <nav class="text-xs text-teal-500" aria-label="Remah roti">
            <a href="{{ route('home') }}" class="hover:text-amber-600">Beranda</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('categories.show', $trip->category) }}" class="hover:text-amber-600">{{ $trip->category->name }}</a>
            <span aria-hidden="true"> / </span>
            <span class="text-teal-700">{{ $trip->title }}</span>
        </nav>

        <header class="mt-4 max-w-3xl">
            <p class="text-xs font-semibold tracking-[0.18em] text-amber-600 uppercase">{{ $trip->category->name }}</p>
            <h1 class="font-display mt-3 text-4xl leading-[1.05] font-extrabold tracking-[-0.03em] text-teal-900 sm:text-5xl">{{ $trip->title }}</h1>

            @if ($trip->meeting_point)
                <p class="mt-4 text-sm text-teal-600">Titik kumpul: <span class="text-teal-800">{{ $trip->meeting_point }}</span></p>
            @endif

            @if ($trip->difficulty_level)
                <p class="mt-3 text-sm text-teal-600">
                    <span class="font-medium text-teal-800">{{ $trip->difficulty_level->label() }}</span>
                    &middot; {{ $trip->difficulty_level->description() }}
                </p>
            @endif
        </header>
    </div>

    {{-- Galeri --}}
    {{-- State galeri pakai indeks angka, bukan path gambar: path ikut data dan
         tidak perlu ikut masuk ke ekspresi Alpine. --}}
    <section class="mx-auto mt-8 max-w-6xl px-4 sm:px-6 lg:px-8" x-data="{ utama: 0 }">
        <div class="overflow-hidden rounded-3xl border border-mist-200">
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
                                :class="utama === {{ $indeks }} ? 'ring-2 ring-amber-600' : 'ring-1 ring-mist-300'"
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
                    <h2 class="font-display text-2xl font-bold text-teal-900">Tentang trip ini</h2>
                    <p class="mt-3 leading-relaxed whitespace-pre-line text-teal-700">{{ $trip->description }}</p>
                </div>
            @endif

            <div x-data="{ terbuka: 'itinerary' }" class="mt-10 divide-y divide-mist-200 border-y border-mist-200">
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
                                <span class="font-display text-xl font-bold text-teal-900">{{ $judul }}</span>
                                <span aria-hidden="true" class="text-teal-500"
                                      x-text="terbuka === '{{ $kunci }}' ? '−' : '+'">+</span>
                            </button>
                        </h2>
                        <div x-show="terbuka === '{{ $kunci }}'" x-transition.opacity class="pb-6">
                            <p class="leading-relaxed whitespace-pre-line text-teal-700">{{ $isi }}</p>
                        </div>
                    </section>
                @endforeach
            </div>

            {{-- Checklist perlengkapan menempel di kategori, bukan trip (PLAN.md §4).
                 Kategori tanpa checklist tidak meninggalkan judul menggantung. --}}
            @if (filled($trip->category->gear_checklist))
                <section class="mt-12 rounded-3xl border border-mist-200 bg-white/70 p-6 sm:p-7">
                    <h2 class="font-display text-xl font-bold text-teal-900">Yang perlu Anda bawa</h2>
                    <p class="mt-1.5 text-sm text-teal-600">
                        Daftar bawaan standar untuk trip {{ Str::lower($trip->category->name) }}. Cek lagi H-1 sebelum berangkat.
                    </p>

                    <ul class="mt-5 grid gap-x-6 gap-y-2.5 sm:grid-cols-2">
                        @foreach ($trip->category->gear_checklist as $barang)
                            <li class="flex gap-2.5 text-sm text-teal-700">
                                <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>
                                {{ $barang }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($relatedTrips->isNotEmpty())
                <section class="mt-14">
                    <h2 class="font-display text-2xl font-bold text-teal-900">Trip lain di {{ $trip->category->name }}</h2>
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
                <div class="rounded-3xl border border-mist-200 bg-white/70 p-6">
                    <x-price-tag :amount="$hargaTermurah" label="mulai dari" size="lg" />

                    <h2 class="mt-6 text-xs font-semibold tracking-wide text-teal-500 uppercase">Jadwal tersedia</h2>

                    @if ($jadwal->isEmpty())
                        <p class="mt-3 text-sm leading-relaxed text-teal-600">
                            Belum ada jadwal terbuka untuk trip ini. Jadwal baru biasanya dibuka tiap awal bulan.
                        </p>
                    @else
                        <ul class="mt-3 space-y-3">
                            @foreach ($jadwal as $item)
                                @php($sisa = $item->remainingQuota())
                                <li class="rounded-2xl border border-mist-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-display text-lg font-bold text-teal-900">
                                                {{ $item->start_date->translatedFormat('j F Y') }}
                                            </p>
                                            @if ($item->end_date)
                                                <p class="text-xs text-teal-500">
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

                                    {{-- CTA per jadwal, bukan satu tombol untuk seluruh trip:
                                         pemesanan selalu terikat ke satu `trip_schedule`.
                                         Halaman ini tetap publik — hanya tujuannya yang
                                         terkunci `auth`, dan middleware itulah yang menyimpan
                                         `url.intended` supaya tamu kembali ke jadwal ini
                                         sesudah masuk. --}}
                                    @unless ($item->isSoldOut())
                                        <a href="{{ route('bookings.create', $item) }}"
                                           class="mt-3 block rounded-full bg-amber-600 px-5 py-2.5 text-center text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                                            Booking tanggal ini
                                        </a>
                                    @endunless

                                    @if ($item->prices->isNotEmpty())
                                        <dl class="mt-3 space-y-1.5 border-t border-mist-200 pt-3 text-sm">
                                            @foreach ($item->prices as $harga)
                                                <div class="flex items-baseline justify-between gap-3">
                                                    <dt class="text-teal-600">
                                                        {{ $harga->label }}
                                                        <span class="text-xs text-teal-500">
                                                            ({{ $harga->min_pax }}@if ($harga->max_pax)–{{ $harga->max_pax }}@else+ @endif pax)
                                                        </span>
                                                    </dt>
                                                    <dd class="font-medium text-teal-900">
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

                    @if ($jadwal->isNotEmpty())
                        <p class="mt-6 text-center text-xs leading-relaxed text-teal-500">
                            Pilih tanggal di atas untuk memesan. Kuota ditahan 2 jam sejak booking dibuat.
                        </p>
                    @endif

                    {{-- Muncul hanya kalau ada jadwal yang kursinya melebihi cap 12:
                         di jadwal kecil, ajakan ini cuma jadi gangguan. --}}
                    @if ($jadwal->contains(fn ($item) => $item->remainingQuota() > config('booking.max_pax_per_booking')))
                        <p class="mt-4 border-t border-mist-200 pt-4 text-center text-xs leading-relaxed text-teal-600">
                            Rombongan lebih dari {{ config('booking.max_pax_per_booking') }} orang?
                            <a href="{{ app(\App\Contracts\MessagingService::class)->requestPrivateTrip($trip) }}"
                               target="_blank" rel="noopener"
                               class="font-medium text-amber-600 underline underline-offset-2 hover:text-amber-700">Ajukan private trip</a>
                            — jadwal dan titik jemput diatur khusus.
                        </p>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</x-layouts.app>
