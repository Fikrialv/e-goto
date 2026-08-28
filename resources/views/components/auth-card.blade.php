@props([
    'title',
    'subtitle' => null,
])

<div class="mx-auto w-full max-w-md px-4 py-14 sm:px-6 lg:py-20">
    <h1 class="font-display text-3xl leading-tight font-bold text-teal-900 sm:text-4xl">{{ $title }}</h1>

    @if ($subtitle)
        <p class="mt-2 text-sm leading-relaxed text-teal-600">{{ $subtitle }}</p>
    @endif

    <div class="mt-8 rounded-3xl border border-mist-200 bg-white p-6 sm:p-8">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <p class="mt-6 text-center text-sm text-teal-600">{{ $footer }}</p>
    @endif
</div>
