@props([
    'trip',
    'schedule' => null,
])

@php
    /*
     * Kartu ini dipakai dua cara: dari daftar trip (relasi `schedules` sudah
     * ter-eager-load) dan dari blok "Jadwal Terdekat" (jadwal dioper langsung,
     * relasi trip->schedules TIDAK ter-load). Cek relationLoaded supaya kasus
     * kedua tidak memicu query per kartu — sumber N+1 klasik.
     */
    $schedule ??= $trip->relationLoaded('schedules') ? $trip->nextSchedule() : null;

    $startingPrice = $trip->relationLoaded('schedules')
        ? $trip->startingPrice()
        : ($schedule?->relationLoaded('prices') ? $schedule->prices->min('price') : null);

    $remaining = $schedule?->remainingQuota();

    /*
     * Rating hanya tampil kalau query pemanggil sudah menyertakan withAvg/
     * withCount — atribut agregat, bukan relasi. Tanpa penjaga ini, kartu di
     * halaman yang belum menghitungnya akan memicu satu query per kartu.
     */
    $ratingRata = $trip->reviews_avg_rating ?? null;
    $jumlahUlasan = $trip->reviews_count ?? 0;

    /*
     * Nama mitra hanya tampil kalau pemanggilnya sudah meng-eager-load
     * `vendor.vendorProfile` — penjaga yang sama dengan rating di atas. Di
     * halaman profil mitra sendiri relasi ini sengaja tidak di-load: mengulang
     * nama yang sudah jadi judul halaman cuma menambah kebisingan.
     */
    $mitra = $trip->relationLoaded('vendor') && $trip->vendor?->relationLoaded('vendorProfile')
        ? $trip->vendor->vendorProfile
        : null;
@endphp

<article {{ $attributes->merge(['class' => 'group flex h-full flex-col overflow-hidden rounded-2xl border border-mist-200 bg-white transition duration-200 ease-out hover:-translate-y-0.5 hover:border-mist-400 hover:shadow-lg hover:shadow-teal-900/5']) }}>
    <div class="relative">
        <a href="{{ route('trips.show', $trip) }}" class="block" tabindex="-1" aria-hidden="true">
            <x-trip-image :src="$trip->cover_image" :alt="$trip->title" :caption="$trip->category->name" class="aspect-[4/3] w-full" />
        </a>

        {{-- Chip ditempel di atas foto: status kuota adalah alasan orang klik
             atau tidak, jadi harus terbaca sebelum mata turun ke teks. --}}
        <div class="pointer-events-none absolute inset-x-3 top-3 flex items-start justify-between gap-2">
            @if ($schedule)
                <x-badge-chip>
                    <x-lucide-calendar class="size-3.5" aria-hidden="true" />
                    {{ $schedule->start_date->translatedFormat('j M') }}
                </x-badge-chip>
            @else
                <x-badge-chip tone="muted">Jadwal menyusul</x-badge-chip>
            @endif

            @if ($remaining !== null)
                @if ($remaining === 0)
                    <x-badge-chip tone="muted">Kuota habis</x-badge-chip>
                @elseif ($remaining <= 3)
                    <x-badge-chip tone="urgent">
                        <x-lucide-armchair class="size-3.5" aria-hidden="true" />
                        Sisa {{ $remaining }}
                    </x-badge-chip>
                @endif
            @endif
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <div class="flex items-center gap-2 text-xs text-teal-500">
            <span class="tracking-wide uppercase">{{ $trip->category->name }}</span>
            @if ($trip->meeting_point)
                <span class="inline-flex min-w-0 items-center gap-1">
                    <x-lucide-map-pin class="size-3.5 shrink-0" aria-hidden="true" />
                    <span class="truncate">{{ $trip->meeting_point }}</span>
                </span>
            @endif
        </div>

        <h3 class="font-display text-xl leading-snug font-bold text-teal-900">
            <a href="{{ route('trips.show', $trip) }}" class="transition-colors hover:text-amber-600">
                {{ $trip->title }}
            </a>
        </h3>

        @if ($ratingRata && $jumlahUlasan > 0)
            <p class="flex items-center gap-1.5 text-sm text-teal-600">
                <x-lucide-star class="size-4 fill-current text-teal-700" aria-hidden="true" />
                <span class="font-medium text-teal-800">{{ number_format((float) $ratingRata, 1, ',', '.') }}</span>
                <span class="text-teal-500">&middot; {{ $jumlahUlasan }} ulasan</span>
            </p>
        @endif

        @if ($mitra?->slug)
            <p class="text-sm text-teal-600">
                oleh
                <a href="{{ route('vendors.show', $mitra) }}"
                   class="font-medium text-teal-800 underline underline-offset-4 transition-colors hover:text-amber-600">
                    {{ $mitra->business_name }}
                </a>
            </p>
        @endif

        <div class="mt-auto flex items-end justify-between gap-3 border-t border-mist-200 pt-4">
            <x-price-tag :amount="$startingPrice" label="mulai dari" size="sm" />

            <a href="{{ route('trips.show', $trip) }}"
               class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 transition-colors hover:text-amber-600">
                Lihat detail
                <x-lucide-arrow-right class="size-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
            </a>
        </div>
    </div>
</article>
