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

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full $sizeClass $toneClass"]) }}
      aria-hidden="true">
    {{ $slot }}
</span>
