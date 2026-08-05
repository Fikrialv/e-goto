@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="max-w-2xl">
        @if ($eyebrow)
            <p class="text-xs font-semibold tracking-[0.18em] text-terracotta-600 uppercase">{{ $eyebrow }}</p>
        @endif

        <h2 class="font-display mt-2 text-3xl leading-tight font-semibold text-forest-900 sm:text-4xl">{{ $title }}</h2>

        @if ($subtitle)
            <p class="mt-3 text-sm leading-relaxed text-forest-600 sm:text-base">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}"
           class="border-b border-forest-200 pb-0.5 text-sm font-medium text-forest-700 transition-colors hover:border-terracotta-600 hover:text-terracotta-600">
            {{ $actionLabel }} &rarr;
        </a>
    @endif
</div>
