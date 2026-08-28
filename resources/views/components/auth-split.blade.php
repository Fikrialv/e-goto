@props([
    'title',
    'subtitle' => null,
])

{{--
    Dua kolom di layar lebar, satu kolom di ponsel. Kolom kanan murni dekoratif
    (foto + kutipan), jadi di ponsel dihapus sepenuhnya lewat `hidden lg:flex` —
    bukan sekadar disembunyikan setelah gambarnya sempat diunduh.

    $heroTrip dan $heroReviews diisi View composer di AppServiceProvider.
    Keduanya bisa null/kosong pada database yang masih bersih; setiap bagian
    di bawah menjaga kasus itu sendiri, tidak ada data contoh yang dikarang.
--}}
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
    <div class="flex items-center justify-center px-4 py-12 sm:px-8 lg:py-16">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="inline-block">
                <img src="{{ asset('images/Logo1.svg') }}" alt="E-GOTO" width="1983" height="793"
                     class="h-9 w-auto" fetchpriority="high">
            </a>

            <h1 class="font-display mt-10 text-3xl leading-tight font-bold tracking-[-0.02em] text-teal-900 sm:text-4xl">
                {{ $title }}
            </h1>

            @if ($subtitle)
                <p class="mt-3 text-sm leading-relaxed text-teal-600">{{ $subtitle }}</p>
            @endif

            <div class="mt-9">
                {{ $slot }}
            </div>

            @if (isset($footer))
                <p class="mt-8 text-sm text-teal-600">{{ $footer }}</p>
            @endif
        </div>
    </div>

    <div class="relative hidden overflow-hidden bg-teal-900 lg:flex">
        @if ($heroTrip?->cover_image)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($heroTrip->cover_image) }}"
                 alt="{{ $heroTrip->title }}" loading="lazy" decoding="async"
                 class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-teal-900/45"></div>
        @else
            {{-- Belum ada sampul trip terunggah: bidangnya diisi fallback yang sama
                 dengan hero dan kartu trip, lalu digelapkan supaya kartu kutipan di
                 atasnya tetap terbaca. Tidak diganti foto stok (GUIDE.md). --}}
            <x-media-fallback icon="map-pin" class="absolute inset-0" />
            <div class="absolute inset-0 bg-teal-900/35"></div>
        @endif

        <div class="relative flex w-full flex-col justify-between p-10 xl:p-14">
            <p class="max-w-sm text-lg leading-snug text-mist-100">
                @if ($heroTrip)
                    Trip yang lagi jalan sekarang: <span class="font-display font-bold text-mist-50">{{ $heroTrip->title }}</span>.
                @else
                    Jalan bareng, urusannya beres.
                @endif
            </p>

            @if ($heroReviews->isNotEmpty())
                <div class="grid gap-4">
                    @foreach ($heroReviews as $heroReview)
                        <figure class="max-w-sm rounded-2xl bg-mist-50/95 p-5 shadow-lg backdrop-blur-sm {{ $loop->odd ? 'lg:ml-auto' : '' }}">
                            <div class="flex items-center gap-1 text-amber-500" aria-hidden="true">
                                @for ($bintang = 1; $bintang <= $heroReview->rating; $bintang++)
                                    <x-lucide-star class="size-4 fill-current" />
                                @endfor
                            </div>

                            <blockquote class="mt-3 text-sm leading-relaxed text-teal-800">
                                {{ Str::limit($heroReview->comment, 120) }}
                            </blockquote>

                            <figcaption class="mt-3 flex items-center gap-2 text-xs text-teal-600">
                                <span class="flex size-7 items-center justify-center rounded-full bg-teal-700 text-[11px] font-semibold text-mist-50">
                                    {{ Str::upper(Str::substr($heroReview->user->name, 0, 1)) }}
                                </span>
                                <span>
                                    {{ $heroReview->user->name }}
                                    <span class="sr-only">memberi</span>
                                    <span aria-hidden="true">&middot;</span>
                                    {{ $heroReview->rating }} dari 5
                                </span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
