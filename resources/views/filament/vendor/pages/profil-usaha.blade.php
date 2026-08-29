<x-filament-panels::page>
    <form wire:submit="simpan" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3">
            <x-filament::button type="submit">
                Simpan perubahan
            </x-filament::button>

            @if (auth()->user()?->vendorProfile?->slug)
                <x-filament::link
                    :href="route('vendors.show', auth()->user()->vendorProfile)"
                    target="_blank"
                    rel="noopener"
                >
                    Lihat halaman publik
                </x-filament::link>
            @endif
        </div>
    </form>
</x-filament-panels::page>
