@props([
    'title' => 'Belum ada yang bisa ditampilkan',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-sand-300 bg-sand-100/60 px-6 py-14 text-center']) }}>
    <p class="font-display text-xl text-forest-800">{{ $title }}</p>

    @if ($message)
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-forest-600">{{ $message }}</p>
    @endif

    @if (trim($slot) !== '')
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
