@props([
    'value',
    'label',
    'suffix' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1']) }}>
    <span class="font-display text-3xl leading-none font-bold tracking-[-0.02em] text-teal-900 sm:text-4xl">
        {{ is_numeric($value) ? number_format((int) $value, 0, ',', '.') : $value }}@if ($suffix)<span class="text-amber-600">{{ $suffix }}</span>@endif
    </span>

    <span class="text-sm text-teal-600">{{ $label }}</span>
</div>
