@props([
    'names' => [],
    'total' => null,
])

{{--
    Inisial, bukan foto profil: kolom foto profil belum ada di tabel users dan
    memajang avatar kosong justru terbaca sebagai akun palsu. Pola inisial ini
    sama dengan avatar akun di header dan di panel Filament.
--}}
@php
    $shown = collect($names)->filter()->take(4)->values();
    $extra = ($total ?? $shown->count()) - $shown->count();
@endphp

@if ($shown->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
        <div class="flex -space-x-2">
            @foreach ($shown as $name)
                <span class="flex size-8 items-center justify-center rounded-full bg-teal-700 text-xs font-semibold text-mist-50 ring-2 ring-mist-50"
                      title="{{ $name }}">
                    {{ Str::upper(Str::substr($name, 0, 1)) }}
                </span>
            @endforeach

            @if ($extra > 0)
                <span class="flex size-8 items-center justify-center rounded-full bg-mist-200 text-xs font-semibold text-teal-700 ring-2 ring-mist-50">
                    +{{ $extra > 99 ? '99' : $extra }}
                </span>
            @endif
        </div>

        @if (trim($slot) !== '')
            <span class="text-sm text-teal-600">{{ $slot }}</span>
        @endif
    </div>
@endif
