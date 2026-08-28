@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
])

{{--
    Varian input khusus halaman masuk/daftar: latar terang di atas panel, cincin
    fokus lebih tebal daripada x-form-field. Sengaja TIDAK dipakai di form
    booking — form transaksi tetap seragam memakai x-form-field.

    Toggle kata sandi memakai Alpine: tipe input ditukar di klien, nilainya
    tidak pernah dikirim ke mana pun. Field kata sandi tetap dirender kosong
    (tidak pernah `old()`), sama seperti x-form-field.
--}}
@php($id = 'field-'.$name)
@php($pesanId = $id.'-pesan')
@php($isPassword = $type === 'password')

<div @if ($isPassword) x-data="{ terlihat: false }" @endif>
    <label for="{{ $id }}" class="block text-sm font-medium text-teal-800">
        {{ $label }}
        @unless ($required)
            <span class="ml-1 text-xs font-normal text-teal-500">(opsional)</span>
        @endunless
    </label>

    <div class="relative mt-2">
        <input id="{{ $id }}" name="{{ $name }}"
               @if ($isPassword) :type="terlihat ? 'text' : 'password'" type="password" @else type="{{ $type }}" @endif
               value="{{ $isPassword ? '' : old($name, $value) }}" @required($required)
               @error($name) aria-invalid="true" @enderror
               @if ($help || $errors->has($name)) aria-describedby="{{ $pesanId }}" @endif
               {{ $attributes->merge(['class' => 'w-full rounded-2xl border border-mist-300 bg-mist-50/80 px-4 py-3 text-sm text-teal-900 transition-shadow placeholder:text-teal-400 focus:border-teal-400 focus:bg-mist-50 focus:ring-4 focus:ring-teal-400/15 focus:outline-none'.($isPassword ? ' pr-12' : '')]) }}>

        @if ($isPassword)
            <button type="button" @click="terlihat = !terlihat"
                    class="absolute inset-y-0 right-0 flex items-center px-4 text-teal-500 transition-colors hover:text-teal-700"
                    :aria-pressed="terlihat">
                <span class="sr-only" x-text="terlihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">Tampilkan kata sandi</span>
                <x-lucide-eye class="size-5" x-show="!terlihat" aria-hidden="true" />
                <x-lucide-eye-off class="size-5" x-show="terlihat" x-cloak aria-hidden="true" />
            </button>
        @endif
    </div>

    @error($name)
        <p id="{{ $pesanId }}" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
    @else
        @if ($help)
            <p id="{{ $pesanId }}" class="mt-1.5 text-xs text-teal-500">{{ $help }}</p>
        @endif
    @enderror
</div>
