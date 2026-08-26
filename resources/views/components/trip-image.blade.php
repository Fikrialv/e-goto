@props([
    'src' => null,
    'alt' => '',
    'caption' => null,
    'eager' => false,
])

{{--
    Fallback digambar lokal (gradient + label), bukan foto stok dari CDN luar:
    nol request eksternal dan tidak ada "kesan stok generik" saat konten asli
    belum masuk. `loading="lazy"` default; hero pakai :eager="true".
--}}
<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-mist-200']) }}>
    @if ($src)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($src) }}"
             alt="{{ $alt }}"
             loading="{{ $eager ? 'eager' : 'lazy' }}"
             decoding="async"
             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
    @else
        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-mist-200 via-mist-300 to-teal-200">
            <svg viewBox="0 0 120 60" class="absolute inset-0 h-full w-full opacity-40" aria-hidden="true" preserveAspectRatio="none">
                <path d="M0 46 L26 22 L44 40 L64 14 L92 44 L120 26 L120 60 L0 60 Z" fill="currentColor" class="text-teal-400"/>
                <circle cx="98" cy="14" r="6" fill="currentColor" class="text-mist-100"/>
            </svg>
            @if ($caption)
                <span class="relative font-display text-sm tracking-wide text-teal-800/80">{{ $caption }}</span>
            @endif
        </div>
    @endif
</div>
