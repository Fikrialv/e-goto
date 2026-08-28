@props([
    'tone' => 'default',
])

{{--
    Chip yang menempel di atas foto, bukan di bawahnya seperti x-status-badge.
    Karena latarnya foto yang warnanya tidak bisa ditebak, tiap varian dikunci
    latar pekat + teks kontras tinggi — ring tipis saja tidak cukup terbaca di
    atas gambar terang.
--}}
@php
    $toneClass = match ($tone) {
        'urgent' => 'bg-amber-600 text-mist-50',
        'muted' => 'bg-teal-900/80 text-mist-100',
        default => 'bg-mist-50/95 text-teal-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium shadow-sm backdrop-blur-[2px] $toneClass"]) }}>
    {{ $slot }}
</span>
