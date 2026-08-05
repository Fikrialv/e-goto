@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
])

@php($id = 'field-'.$name)
@php($pesanId = $id.'-pesan')

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-forest-800">
        {{ $label }}
        @unless ($required)
            <span class="ml-1 text-xs font-normal text-forest-500">(opsional)</span>
        @endunless
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="3" @required($required)
                  @error($name) aria-invalid="true" @enderror
                  @if ($help || $errors->has($name)) aria-describedby="{{ $pesanId }}" @endif
                  {{ $attributes->merge(['class' => 'mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 placeholder:text-forest-400 focus:border-forest-500']) }}>{{ old($name, $value) }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @required($required)
                @error($name) aria-invalid="true" @enderror
                @if ($help || $errors->has($name)) aria-describedby="{{ $pesanId }}" @endif
                {{ $attributes->merge(['class' => 'mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 focus:border-forest-500']) }}>
            {{ $slot }}
        </select>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               value="{{ $type === 'password' ? '' : old($name, $value) }}" @required($required)
               @error($name) aria-invalid="true" @enderror
               @if ($help || $errors->has($name)) aria-describedby="{{ $pesanId }}" @endif
               {{ $attributes->merge(['class' => 'mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 placeholder:text-forest-400 focus:border-forest-500']) }}>
    @endif

    @error($name)
        <p id="{{ $pesanId }}" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
    @else
        @if ($help)
            <p id="{{ $pesanId }}" class="mt-1.5 text-xs text-forest-500">{{ $help }}</p>
        @endif
    @enderror
</div>
