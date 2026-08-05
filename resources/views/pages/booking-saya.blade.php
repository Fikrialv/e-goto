<x-layouts.app title="Booking Saya">
    <div class="mx-auto w-full max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <h1 class="font-display text-3xl leading-tight font-semibold text-forest-900 sm:text-4xl">Booking Saya</h1>
        <p class="mt-3 max-w-lg text-sm leading-relaxed text-forest-600">
            Riwayat pemesanan dan e-tiket Anda akan muncul di sini.
        </p>

        @if ($bookings->isEmpty())
            <x-empty-state class="mt-10"
                           title="Belum ada pemesanan"
                           message="Pilih trip yang Anda suka, pesan, dan e-tiketnya tersimpan di halaman ini.">
                <a href="{{ route('home') }}"
                   class="inline-block rounded-full bg-terracotta-600 px-6 py-3 text-sm font-medium text-sand-50 transition-colors hover:bg-terracotta-700">
                    Lihat trip
                </a>
            </x-empty-state>
        @else
            <ul class="mt-10 space-y-4">
                @foreach ($bookings as $booking)
                    <li class="rounded-2xl border border-sand-200 bg-white/70 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-display text-lg font-semibold text-forest-900">
                                    {{ $booking->schedule->trip->title }}
                                </p>
                                <p class="mt-1 text-sm text-forest-600">
                                    {{ $booking->schedule->start_date->translatedFormat('j F Y') }} ·
                                    {{ $booking->pax_count }} peserta
                                </p>
                                <p class="mt-1 font-mono text-xs text-forest-500">{{ $booking->code }}</p>
                            </div>

                            <x-status-badge>{{ $booking->status->value }}</x-status-badge>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $bookings->links() }}</div>
        @endif
    </div>
</x-layouts.app>
