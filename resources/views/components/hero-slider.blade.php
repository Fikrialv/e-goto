@props([
    'trips',
])

@php
    /*
     * Maksimal 5 slide. Di bawah 2 trip, slider tidak dipaksakan — dot tunggal
     * yang tidak bisa diklik ke mana-mana cuma bikin bingung, jadi hero-nya
     * dirender statis.
     */
    $slides = $trips->take(5)->values();
    $adaSlider = $slides->count() >= 2;
@endphp

@if (! $adaSlider)
    <figure class="relative aspect-[4/3] overflow-hidden rounded-3xl sm:aspect-[3/2] lg:aspect-[16/10] lg:min-h-[30rem]">
        <x-trip-image :src="$slides->first()?->cover_image" :alt="$slides->first()?->title ?? ''"
                      :caption="$slides->first()?->category->name ?? 'Foto trip menyusul'"
                      :fallback-icon="$slides->first()?->category->icon ?: 'mountain'" eager
                      class="h-full w-full" />

        @if ($slides->first())
            <figcaption class="absolute inset-x-4 bottom-4">
                <a href="{{ route('trips.show', $slides->first()) }}"
                   class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-medium text-teal-800 shadow-sm transition-colors hover:text-amber-600">
                    <x-lucide-camera class="size-4" aria-hidden="true" />
                    {{ Str::limit($slides->first()->title, 40) }}
                </a>
            </figcaption>
        @endif
    </figure>
@else
    {{--
        Slider trip pilihan. Alpine + CSS saja — tidak ada library carousel
        pihak ketiga (CLAUDE.md §10).

        Auto-advance berhenti saat kursor di atasnya, dan tidak pernah menyala
        sama sekali kalau prefers-reduced-motion aktif. Dot tetap bisa diklik di
        kedua keadaan, jadi mematikan gerakan tidak berarti mematikan isinya.

        Selama sampul belum diunggah, tiap slide dibedakan lewat ikon fallback
        yang mengikuti ikon kategorinya — bukan empat bidang kembar.
    --}}
    <div x-data="{
             aktif: 0,
             jumlah: {{ $slides->count() }},
             timer: null,
             pelanGerak: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
             mulai() {
                 if (this.pelanGerak || this.timer) return;
                 this.timer = setInterval(() => { this.aktif = (this.aktif + 1) % this.jumlah }, 5000);
             },
             berhenti() {
                 clearInterval(this.timer);
                 this.timer = null;
             },
         }"
         x-init="mulai()"
         @mouseenter="berhenti()" @mouseleave="mulai()"
         @focusin="berhenti()" @focusout="mulai()"
         class="relative"
         role="region" aria-roledescription="carousel" aria-label="Trip pilihan">

        {{-- Rasio dipegang pembungkus, bukan slide. Sebelumnya hanya slide pertama
             yang berada di aliran normal dan sisanya absolut — begitu slide pertama
             disembunyikan, pembungkusnya kehilangan tinggi dan seluruh hero lenyap
             dari layar. --}}
        <div class="relative aspect-[4/3] overflow-hidden rounded-3xl sm:aspect-[3/2] lg:aspect-[16/10] lg:min-h-[30rem]">
            @foreach ($slides as $indeks => $slide)
                <figure x-show="aktif === {{ $indeks }}" class="absolute inset-0"
                        @if (! $loop->first) x-cloak @endif
                        x-transition:enter="transition duration-250 ease-out"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        role="group" aria-roledescription="slide"
                        aria-label="{{ $loop->iteration }} dari {{ $slides->count() }}">
                    <x-trip-image :src="$slide->cover_image" :alt="$slide->title"
                                  :caption="$slide->category->name"
                                  :fallback-icon="$slide->category->icon ?: 'compass'"
                                  :eager="$loop->first"
                                  class="h-full w-full" />

                    <figcaption class="absolute inset-x-4 bottom-4">
                        <a href="{{ route('trips.show', $slide) }}"
                           class="inline-flex max-w-full items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-medium text-teal-800 shadow-sm transition-colors hover:text-amber-600">
                            <x-lucide-map-pin class="size-4 shrink-0" aria-hidden="true" />
                            <span class="truncate">{{ $slide->title }}</span>
                        </a>
                    </figcaption>
                </figure>
            @endforeach
        </div>

        <div class="mt-4 flex items-center justify-center gap-2">
            @foreach ($slides as $indeks => $slide)
                <button type="button" @click="aktif = {{ $indeks }}"
                        :class="aktif === {{ $indeks }} ? 'w-6 bg-teal-700' : 'w-2 bg-mist-300 hover:bg-mist-400'"
                        :aria-current="aktif === {{ $indeks }}"
                        class="h-2 rounded-full transition-all duration-200 ease-out">
                    <span class="sr-only">Tampilkan trip ke-{{ $loop->iteration }}: {{ $slide->title }}</span>
                </button>
            @endforeach
        </div>
    </div>
@endif
