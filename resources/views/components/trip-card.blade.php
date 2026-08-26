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
@endphp

<article {{ $attributes->merge(['class' => 'group flex h-full flex-col overflow-hidden rounded-2xl border border-mist-200 bg-white/70 transition-colors hover:border-mist-400']) }}>
    <a href="{{ route('trips.show', $trip) }}" class="block" tabindex="-1" aria-hidden="true">
        <x-trip-image :src="$trip->cover_image" :alt="$trip->title" :caption="$trip->category->name" class="aspect-[4/3] w-full" />
    </a>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <div class="flex items-center gap-2 text-xs text-teal-500">
            <span class="tracking-wide uppercase">{{ $trip->category->name }}</span>
            @if ($trip->meeting_point)
                <span aria-hidden="true">&middot;</span>
                <span class="truncate">{{ $trip->meeting_point }}</span>
            @endif
        </div>

        <h3 class="font-display text-xl leading-snug font-bold text-teal-900">
            <a href="{{ route('trips.show', $trip) }}" class="transition-colors hover:text-amber-600">
                {{ $trip->title }}
            </a>
        </h3>

        <div class="mt-auto flex flex-wrap items-center gap-2">
            @if ($schedule)
                <x-status-badge tone="neutral">
                    {{ $schedule->start_date->translatedFormat('j M Y') }}
                </x-status-badge>

                @if ($remaining === 0)
                    <x-status-badge tone="danger">Kuota habis</x-status-badge>
                @elseif ($remaining <= 3)
                    <x-status-badge tone="warning">Sisa {{ $remaining }} kursi</x-status-badge>
                @else
                    <x-status-badge tone="success">Sisa {{ $remaining }} kursi</x-status-badge>
                @endif
            @else
                <x-status-badge tone="neutral">Jadwal menyusul</x-status-badge>
            @endif
        </div>

        <div class="flex items-end justify-between gap-3 border-t border-mist-200 pt-4">
            <x-price-tag :amount="$startingPrice" label="mulai dari" size="sm" />

            <a href="{{ route('trips.show', $trip) }}"
               class="text-sm font-medium text-teal-700 transition-colors hover:text-amber-600">
                Lihat detail &rarr;
            </a>
        </div>
    </div>
</article>
