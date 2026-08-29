<x-filament-panels::page>
    <x-filament::section heading="Verifikasi dua langkah">
        <x-slot name="description">
            Akun ini bisa menyetujui pembayaran dan menerbitkan tiket. Satu kata sandi yang bocor
            cukup untuk menyetujui pembayaran yang tidak pernah masuk — langkah kedua menutup itu.
        </x-slot>

        @if ($this->aktif)
            <div class="space-y-4">
                <x-filament::badge color="success">Aktif</x-filament::badge>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Sisa kode pemulihan: <strong>{{ $this->sisaKodePemulihan }}</strong> dari 8.
                    Kalau tinggal sedikit, matikan lalu nyalakan lagi untuk mendapat set baru.
                </p>

                <form wire:submit="matikan" class="max-w-sm space-y-3">
                    <label for="password" class="block text-sm font-medium">
                        Kata sandi (untuk mematikan)
                    </label>
                    <input id="password" type="password" wire:model="password" autocomplete="current-password"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/20 dark:bg-white/5">
                    @error('password')
                        <p class="text-sm text-danger-600">{{ $message }}</p>
                    @enderror

                    <x-filament::button type="submit" color="danger" outlined>
                        Matikan verifikasi dua langkah
                    </x-filament::button>
                </form>
            </div>
        @elseif ($rahasiaBaru)
            <div class="space-y-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Pindai QR ini dengan Google Authenticator, Authy, atau aplikasi sejenis. Kalau
                    kameranya tidak bisa dipakai, ketik kunci di bawahnya secara manual.
                </p>

                <div class="inline-block rounded-lg bg-white p-3">
                    {!! $this->qr !!}
                </div>

                <p class="text-sm">
                    Kunci manual:
                    <code class="rounded bg-gray-100 px-2 py-1 font-mono dark:bg-white/10">{{ $rahasiaBaru }}</code>
                </p>

                <form wire:submit="konfirmasi" class="max-w-sm space-y-3">
                    <label for="kode" class="block text-sm font-medium">
                        Kode enam digit dari aplikasi
                    </label>
                    <input id="kode" type="text" wire:model="kode" inputmode="numeric" autocomplete="one-time-code"
                           class="w-full rounded-lg border-gray-300 text-center font-mono text-lg tracking-widest shadow-sm dark:border-white/20 dark:bg-white/5">
                    @error('kode')
                        <p class="text-sm text-danger-600">{{ $message }}</p>
                    @enderror

                    <div class="flex gap-3">
                        <x-filament::button type="submit">Konfirmasi &amp; aktifkan</x-filament::button>
                        <x-filament::button type="button" color="gray" wire:click="batal">Batal</x-filament::button>
                    </div>
                </form>
            </div>
        @else
            <div class="space-y-4">
                <x-filament::badge color="gray">Belum aktif</x-filament::badge>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Kamu akan diminta kode enam digit dari aplikasi authenticator setiap kali masuk
                    panel. Siapkan HP-nya dulu sebelum mulai.
                </p>

                <x-filament::button wire:click="mulai">Nyalakan verifikasi dua langkah</x-filament::button>
            </div>
        @endif
    </x-filament::section>

    @if (filled($kodePemulihanTampil))
        <x-filament::section heading="Kode pemulihan">
            <x-slot name="description">
                Ditampilkan sekali saja. Simpan di tempat aman sekarang — kalau HP-nya hilang, ini
                satu-satunya jalan masuk tanpa akses database.
            </x-slot>

            <ul class="grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4">
                @foreach ($kodePemulihanTampil as $kodeCadangan)
                    <li class="rounded bg-gray-100 px-3 py-2 text-center dark:bg-white/10">{{ $kodeCadangan }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-panels::page>
