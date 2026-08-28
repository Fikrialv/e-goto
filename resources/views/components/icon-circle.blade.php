@props([
    'tone' => 'default',
    'size' => 'md',
])

@php
    $toneClass = match ($tone) {
        'accent' => 'bg-amber-100 text-amber-700',
        'solid' => 'bg-teal-700 text-mist-50',
        default => 'bg-mist-100 text-teal-700',
    };

    $sizeClass = match ($size) {
        'lg' => 'size-16 [&>svg]:size-7',
        'sm' => 'size-9 [&>svg]:size-4',
        default => 'size-12 [&>svg]:size-6',
    };
@endphp

{{-- Angkat 2px + bayangan naik saat kartu induknya di-hover. Dipasang di sini,
     bukan di tiap pemanggil, supaya gerakannya sama di semua grid. --}}
<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full transition duration-200 ease-out group-hover:-translate-y-0.5 group-hover:shadow-md group-hover:shadow-teal-900/10 $sizeClass $toneClass"]) }}
      aria-hidden="true">
    {{ $slot }}
</span>
