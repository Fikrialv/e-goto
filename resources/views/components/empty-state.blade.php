@props([
    'title' => 'Belum ada yang bisa ditampilkan',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-mist-300 bg-mist-100/60 px-6 py-14 text-center']) }}>
    {{-- Ikon dioper pemanggil supaya sesuai konteksnya (kalender untuk jadwal
         kosong, kompas untuk trip kosong) — bukan satu ikon serba-guna yang
         dipakai di semua layar. --}}
    @isset ($icon)
        <x-icon-circle class="mx-auto mb-5">{{ $icon }}</x-icon-circle>
    @endisset

    <p class="font-display text-xl text-teal-800">{{ $title }}</p>

    @if ($message)
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-teal-600">{{ $message }}</p>
    @endif

    @if (trim($slot) !== '')
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
