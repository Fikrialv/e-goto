@props([
    'src' => null,
    'alt' => '',
    'caption' => null,
    'eager' => false,
    'fallbackIcon' => 'camera',
])

{{--
    Foto belum ada bukan keadaan darurat — mitra memang mengunggah sampul
    belakangan. Karena itu cabang kosongnya diserahkan ke x-media-fallback,
    komponen yang sama dengan yang dipakai hero, panel masuk, dan seksi mitra:
    nol request ke domain luar, dan bidangnya terbaca sengaja dirancang.
    `loading="lazy"` default; hero pakai :eager="true".
--}}
<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-mist-200']) }}>
    @if ($src)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($src) }}"
             alt="{{ $alt }}"
             loading="{{ $eager ? 'eager' : 'lazy' }}"
             decoding="async"
             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
    @else
        <x-media-fallback :icon="$fallbackIcon" :label="$caption" />
    @endif
</div>
