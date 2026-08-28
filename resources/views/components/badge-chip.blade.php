@props([
    'tone' => 'default',
])

{{--
    Chip yang menempel di atas foto, bukan di bawahnya seperti x-status-badge.
    Karena latarnya foto yang warnanya tidak bisa ditebak, tiap varian dikunci
    latar SOLID + teks kontras tinggi — ring tipis saja tidak cukup terbaca di
    atas gambar terang. Tanpa backdrop-blur: efek glass hanya dipakai di panel
    halaman masuk (CLAUDE.md §10).
--}}
@php
    $toneClass = match ($tone) {
        'urgent' => 'bg-amber-600 text-white',
        'muted' => 'bg-teal-900/85 text-mist-100',
        default => 'bg-teal-50 text-teal-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium shadow-sm $toneClass"]) }}>
    {{ $slot }}
</span>
