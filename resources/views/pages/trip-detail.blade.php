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

            <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-teal-600">
                @if ($trip->meeting_point)
                    <p class="inline-flex items-center gap-2">
                        <x-lucide-map-pin class="size-4 shrink-0 text-teal-500" aria-hidden="true" />
                        Titik kumpul: <span class="text-teal-800">{{ $trip->meeting_point }}</span>
                    </p>
                @endif

                @if ($trip->difficulty_level)
                    <p class="inline-flex items-center gap-2">
                        <x-lucide-mountain class="size-4 shrink-0 text-teal-500" aria-hidden="true" />
                        <span class="font-medium text-teal-800">{{ $trip->difficulty_level->label() }}</span>
                    </p>
                @endif

                @if ($reviews->total() > 0)
                    <p class="inline-flex items-center gap-2">
                        <x-lucide-star class="size-4 shrink-0 fill-current text-teal-700" aria-hidden="true" />
                        <span class="font-medium text-teal-800">{{ number_format((float) $ratingRata, 1, ',', '.') }}</span>
                        dari {{ $reviews->total() }} penilaian
                    </p>
                @endif
            </div>

            @if ($trip->difficulty_level)
                <p class="mt-3 text-sm leading-relaxed text-teal-600">{{ $trip->difficulty_level->description() }}</p>
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
                            {{-- Daftar fasilitas diisi mitra satu baris satu item, jadi
                                 dipecah per baris supaya tiap poin dapat penanda ada/
                                 tidak ada. Rencana perjalanan tetap paragraf utuh —
                                 memecahnya per baris akan merusak alur ceritanya. --}}
                            @if (in_array($kunci, ['includes', 'excludes'], true))
                                <ul class="grid gap-2.5">
                                    @foreach (preg_split('/

|
|
/', trim($isi)) as $baris)
                                        @continue (blank($baris))
                                        <li class="flex items-start gap-2.5 text-sm leading-relaxed text-teal-700">
                                            @if ($kunci === 'includes')
                                                <x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />
                                            @else
                                                <x-lucide-x class="mt-0.5 size-4 shrink-0 text-teal-400" aria-hidden="true" />
                                            @endif
                                            {{ ltrim($baris, "-* 	") }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="leading-relaxed whitespace-pre-line text-teal-700">{{ $isi }}</p>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>

            @if ($trip->options->isNotEmpty())
                <section class="mt-12">
                    <h2 class="font-display text-xl font-bold text-teal-900">Tambahan opsional</h2>
                    <p class="mt-1.5 text-sm text-teal-600">Dipilih saat memesan, harganya per orang.</p>

                    <ul class="mt-5 divide-y divide-mist-200 border-y border-mist-200">
                        @foreach ($trip->options as $opsi)
                            <li class="flex flex-wrap items-baseline justify-between gap-2 py-4">
                                <div>
                                    <p class="text-sm font-medium text-teal-900">{{ $opsi->name }}</p>
                                    @if ($opsi->description)
                                        <p class="mt-0.5 text-xs leading-relaxed text-teal-600">{{ $opsi->description }}</p>
                                    @endif
                                </div>
                                <p class="text-sm text-teal-700">+ Rp{{ number_format($opsi->extra_price, 0, ',', '.') }} / orang</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Checklist perlengkapan menempel di kategori, bukan trip (PLAN.md §4).
                 Kategori tanpa checklist tidak meninggalkan judul menggantung. --}}
            @if (filled($trip->category->gear_checklist))
                <section class="mt-12 rounded-3xl border border-mist-200 bg-white p-6 sm:p-7">
                    <h2 class="font-display text-xl font-bold text-teal-900">Yang perlu Anda bawa</h2>
                    <p class="mt-1.5 text-sm text-teal-600">
                        Daftar bawaan standar untuk trip {{ Str::lower($trip->category->name) }}. Cek lagi H-1 sebelum berangkat.
                    </p>

                    <ul class="mt-5 grid gap-x-6 gap-y-2.5 sm:grid-cols-2">
                        @foreach ($trip->category->gear_checklist as $barang)
                            <li class="flex gap-2.5 text-sm text-teal-700">
                                <x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />
                                {{ $barang }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="mt-14">
                <x-section-heading eyebrow="Kata peserta" title="Yang bilang sudah ikut" />

                @if ($reviews->isEmpty())
                    <x-empty-state class="mt-8"
                        title="Belum ada penilaian"
                        message="Penilaian baru bisa ditulis peserta setelah tripnya selesai.">
                        <x-slot:icon><x-lucide-message-circle class="size-6" /></x-slot:icon>
                    </x-empty-state>
                @else
                    {{-- Carousel, bukan daftar panjang: satu ulasan sekali baca lebih
                         mungkin benar-benar dibaca. Indeks disimpan Alpine; semua
                         ulasan halaman ini tetap ada di DOM supaya bisa dibaca
                         pembaca layar dan tetap ketemu Ctrl+F. --}}
                    <div x-data="{ aktif: 0, jumlah: {{ $reviews->count() }} }" class="mt-8">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <x-avatar-cluster :names="$reviews->pluck('user.name')->filter()->all()"
                                              :total="$reviews->total()">
                                Dipercaya {{ $reviews->total() }} peserta
                            </x-avatar-cluster>

                            <p class="inline-flex items-center gap-2 text-sm text-teal-700">
                                <x-lucide-star class="size-4 fill-current text-teal-700" aria-hidden="true" />
                                Rata-rata <strong class="text-teal-900">{{ number_format((float) $ratingRata, 1, ',', '.') }}</strong> dari 5
                            </p>
                        </div>

                        <div class="relative mt-6 min-h-56">
                            @foreach ($reviews as $indeks => $ulasan)
                                <figure x-show="aktif === {{ $indeks }}"
                                        @if (! $loop->first) x-cloak @endif
                                        x-transition:enter="transition duration-250 ease-out"
                                        x-transition:enter-start="opacity-0 translate-y-2"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        class="rounded-3xl border border-mist-200 bg-white p-6 sm:p-8">
                                    <div class="flex items-center gap-1 text-teal-700" aria-hidden="true">
                                        @for ($bintang = 1; $bintang <= $ulasan->rating; $bintang++)
                                            <x-lucide-star class="size-4 fill-current" />
                                        @endfor
                                    </div>
                                    <span class="sr-only">{{ $ulasan->rating }} dari 5 bintang</span>

                                    @if ($ulasan->comment)
                                        <blockquote class="font-display mt-4 text-lg leading-relaxed text-teal-800">
                                            {{ $ulasan->comment }}
                                        </blockquote>
                                    @endif

                                    <figcaption class="mt-5 flex items-center gap-3 border-t border-mist-200 pt-4 text-sm">
                                        <span class="flex size-9 items-center justify-center rounded-full bg-teal-700 text-xs font-semibold text-mist-50">
                                            {{ Str::upper(Str::substr($ulasan->user?->name ?? 'P', 0, 1)) }}
                                        </span>
                                        <span>
                                            <span class="block font-medium text-teal-900">{{ $ulasan->user?->name ?? 'Peserta' }}</span>
                                            <span class="block text-xs text-teal-500">{{ $ulasan->created_at->translatedFormat('j F Y') }}</span>
                                        </span>
                                    </figcaption>
                                </figure>
                            @endforeach
                        </div>

                        @if ($reviews->count() > 1)
                            <div class="mt-5 flex items-center justify-between gap-4">
                                <p class="text-xs text-teal-500">
                                    <span x-text="aktif + 1">1</span> dari {{ $reviews->count() }} di halaman ini
                                </p>

                                <div class="flex gap-2">
                                    <button type="button" @click="aktif = (aktif - 1 + jumlah) % jumlah"
                                            class="flex size-10 items-center justify-center rounded-full border border-mist-300 text-teal-700 transition duration-200 ease-out hover:-translate-x-0.5 hover:border-teal-400 hover:text-amber-600">
                                        <span class="sr-only">Ulasan sebelumnya</span>
                                        <x-lucide-arrow-left class="size-4" aria-hidden="true" />
                                    </button>
                                    <button type="button" @click="aktif = (aktif + 1) % jumlah"
                                            class="flex size-10 items-center justify-center rounded-full border border-mist-300 text-teal-700 transition duration-200 ease-out hover:translate-x-0.5 hover:border-teal-400 hover:text-amber-600">
                                        <span class="sr-only">Ulasan berikutnya</span>
                                        <x-lucide-arrow-right class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6">{{ $reviews->links() }}</div>
                @endif
            </section>

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
                <div class="rounded-3xl border border-mist-200 bg-white p-6">
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
