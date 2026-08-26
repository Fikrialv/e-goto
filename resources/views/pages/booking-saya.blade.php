<x-layouts.app title="Booking Saya">
    <div class="mx-auto w-full max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <h1 class="font-display text-3xl leading-tight font-bold text-teal-900 sm:text-4xl">Booking Saya</h1>
        <p class="mt-3 max-w-lg text-sm leading-relaxed text-teal-600">
            Riwayat pemesanan dan e-tiket Anda akan muncul di sini.
        </p>

        @if ($bookings->isEmpty())
            <x-empty-state class="mt-10"
                           title="Belum ada pemesanan"
                           message="Pilih trip yang Anda suka, pesan, dan e-tiketnya tersimpan di halaman ini.">
                <a href="{{ route('home') }}"
                   class="inline-block rounded-full bg-amber-600 px-6 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                    Lihat trip
                </a>
            </x-empty-state>
        @else
            <ul class="mt-10 space-y-4">
                @foreach ($bookings as $booking)
                    <li class="rounded-2xl border border-mist-200 bg-white/70 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-display text-lg font-bold text-teal-900">
                                    {{ $booking->schedule->trip->title }}
                                </p>
                                <p class="mt-1 text-sm text-teal-600">
                                    {{ $booking->schedule->start_date->translatedFormat('j F Y') }} ·
                                    {{ $booking->pax_count }} peserta
                                </p>
                                <p class="mt-1 font-mono text-xs text-teal-500">{{ $booking->code }}</p>
                            </div>

                            <div class="flex flex-col items-end gap-1.5">
                                <x-status-badge>{{ $booking->status->label() }}</x-status-badge>

                                @if ($booking->latestPayment?->status === App\Enums\PaymentStatus::Verified)
                                    <x-status-badge tone="success">Pembayaran terverifikasi</x-status-badge>
                                @elseif ($booking->latestPayment?->status === App\Enums\PaymentStatus::Pending)
                                    <span class="text-xs text-teal-500">Diperiksa admin {{ config('booking.verification_eta') }}</span>
                                @endif
                            </div>
                        </div>

                        @php($aksi = match ($booking->status) {
                            App\Enums\BookingStatus::PendingPayment, App\Enums\BookingStatus::AwaitingVerification => ['label' => 'Lihat pembayaran', 'url' => route('payments.show', $booking)],
                            App\Enums\BookingStatus::Confirmed, App\Enums\BookingStatus::Completed => ['label' => 'Lihat e-tiket', 'url' => route('tickets.show', $booking)],
                            default => null,
                        })

                        @if ($aksi)
                            <a href="{{ $aksi['url'] }}"
                               class="mt-4 inline-block rounded-full border border-mist-300 px-5 py-2 text-sm text-teal-700 hover:border-teal-500">
                                {{ $aksi['label'] }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $bookings->links() }}</div>
        @endif
    </div>
</x-layouts.app>
