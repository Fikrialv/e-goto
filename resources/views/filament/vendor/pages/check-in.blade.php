<x-filament-panels::page>
    <form wire:submit="checkIn" class="space-y-4">
        {{ $this->form }}

        <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
            Check-in peserta
        </x-filament::button>
    </form>

    @if ($galat)
        <div class="rounded-xl border border-danger-300 bg-danger-50 p-6 text-danger-800 dark:border-danger-700 dark:bg-danger-950 dark:text-danger-200">
            <p class="text-lg font-semibold">Ditolak</p>
            <p class="mt-1">{{ $galat }}</p>
        </div>
    @endif

    @if ($terakhir)
        <div class="rounded-xl border border-success-300 bg-success-50 p-6 text-success-900 dark:border-success-700 dark:bg-success-950 dark:text-success-100">
            <p class="text-lg font-semibold">Check-in berhasil</p>

            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm opacity-80">Peserta</dt>
                    <dd class="text-xl font-semibold">{{ $terakhir->participant->full_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm opacity-80">Kode booking</dt>
                    <dd class="font-mono text-xl font-semibold">{{ $terakhir->booking->code }}</dd>
                </div>
                <div>
                    <dt class="text-sm opacity-80">Trip</dt>
                    <dd class="font-medium">{{ $terakhir->booking->schedule->trip->title }}</dd>
                </div>
                <div>
                    <dt class="text-sm opacity-80">Waktu masuk</dt>
                    <dd class="font-medium">{{ $terakhir->checked_in_at?->translatedFormat('j F Y, H:i') }}</dd>
                </div>
            </dl>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
        <p class="font-medium text-gray-900 dark:text-gray-100">Cara kerja</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Satu tiket hanya bisa dipakai sekali — percobaan kedua ditolak beserta jam pemakaian pertamanya.</li>
            <li>Tiket yang isinya diubah akan gagal verifikasi tanda tangan, walau tokennya ada di sistem.</li>
            <li>Anda hanya bisa men-check-in peserta trip milik Anda sendiri.</li>
        </ul>
    </div>
</x-filament-panels::page>
