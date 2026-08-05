@props([
    'tone' => 'neutral',
])

@php
    $toneClass = match ($tone) {
        'success' => 'bg-forest-50 text-forest-700 ring-forest-200',
        'warning' => 'bg-terracotta-100 text-terracotta-700 ring-terracotta-500/30',
        'danger' => 'bg-sand-200 text-forest-600 ring-sand-300 line-through decoration-1',
        default => 'bg-sand-100 text-forest-600 ring-sand-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset $toneClass"]) }}>
    {{ $slot }}
</span>
