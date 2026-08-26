@props([
    'tone' => 'neutral',
])

@php
    $toneClass = match ($tone) {
        'success' => 'bg-teal-50 text-teal-700 ring-teal-200',
        'warning' => 'bg-amber-100 text-amber-700 ring-amber-500/30',
        'danger' => 'bg-mist-200 text-teal-600 ring-mist-300 line-through decoration-1',
        default => 'bg-mist-100 text-teal-600 ring-mist-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset $toneClass"]) }}>
    {{ $slot }}
</span>
