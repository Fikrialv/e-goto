@props([
    'amount' => null,
    'label' => null,
    'size' => 'md',
])

@php
    $sizeClass = match ($size) {
        'lg' => 'text-2xl sm:text-3xl',
        'sm' => 'text-base',
        default => 'text-lg',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex flex-col leading-tight']) }}>
    @if ($label)
        <span class="text-[11px] tracking-wide text-teal-500 uppercase">{{ $label }}</span>
    @endif

    @if ($amount === null)
        <span class="{{ $sizeClass }} font-display font-bold text-teal-500">Harga menyusul</span>
    @else
        <span class="{{ $sizeClass }} font-display font-bold text-teal-900">
            <span class="text-[0.6em] align-middle text-teal-500">Rp</span>{{ number_format((int) $amount, 0, ',', '.') }}
        </span>
    @endif
</span>
