@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $trip = $booking->schedule->trip;
@endphp

<x-layouts.app :title="'E-tiket '.$booking->code">
    <div class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <a href="{{ route('bookings.index') }}" class="text-sm text-forest-600 hover:text-terracotta-600">
            &larr; Kembali ke Booking Saya
        </a>

        <h1 class="mt-6 font-display text-3xl leading-tight font-semibold text-forest-900 sm:text-4xl">
            E-tiket Anda sudah terbit
        </h1>
        <p class="mt-3 max-w-xl text-sm leading-relaxed text-forest-600">
            Tunjukkan kode QR di bawah ke petugas saat berkumpul. Setiap peserta punya tiket sendiri dan hanya
            bisa dipakai satu kali.
        </p>

        <div class="mt-8 flex flex-wrap gap-3 print:hidden">
            <button type="button" onclick="window.print()"
                    class="rounded-full bg-forest-800 px-6 py-2.5 text-sm font-medium text-sand-50 transition-colors hover:bg-forest-900">
                Cetak / simpan PDF
            </button>
        </div>

        <div class="mt-8 space-y-6">
            @foreach ($booking->tickets as $ticket)
                <article class="overflow-hidden rounded-3xl border border-sand-200 bg-white print:break-inside-avoid">
                    <div class="flex items-center justify-between gap-4 bg-forest-800 px-6 py-4 text-sand-50">
                        <p class="font-display text-xl font-semibold">E<span class="text-terracotta-400">·</span>GOTO</p>
                        <p class="font-mono text-sm">{{ $booking->code }}</p>
                    </div>

                    <div class="grid gap-6 p-6 sm:grid-cols-[1fr_auto] sm:p-8">
                        <div>
                            <p class="text-xs font-semibold tracking-widest text-forest-500 uppercase">Peserta</p>
                            <p class="mt-1 font-display text-2xl font-semibold text-forest-900">
                                {{ $ticket->participant->full_name }}
                            </p>

                            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs tracking-wide text-forest-500 uppercase">Trip</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-forest-900">{{ $trip->title }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs tracking-wide text-forest-500 uppercase">Tanggal</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-forest-900">
                                        {{ $booking->schedule->start_date->translatedFormat('j F Y') }}
                                        @if ($booking->schedule->end_date)
                                            &ndash; {{ $booking->schedule->end_date->translatedFormat('j F Y') }}
                                        @endif
                                    </dd>
                                </div>
                                @if ($trip->meeting_point)
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs tracking-wide text-forest-500 uppercase">Titik kumpul</dt>
                                        <dd class="mt-0.5 text-sm font-medium text-forest-900">{{ $trip->meeting_point }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-xs tracking-wide text-forest-500 uppercase">Status tiket</dt>
                                    <dd class="mt-0.5">
                                        <x-status-badge>
                                            {{ $ticket->status === App\Enums\TicketStatus::Used ? 'sudah check-in' : 'siap dipakai' }}
                                        </x-status-badge>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="justify-self-center text-center">
                            {{-- QR berisi token tiket saja, bukan URL: tidak ada
                                 alamat publik yang bisa dibuka orang lain kalau
                                 tangkapan layar tiket ini tersebar. --}}
                            <div class="rounded-2xl border border-sand-200 bg-sand-50 p-3">
                                {!! QrCode::size(160)->margin(0)->generate($ticket->token) !!}
                            </div>
                            <p class="mt-2 font-mono text-[11px] tracking-tight break-all text-forest-500">
                                {{ $ticket->token }}
                            </p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <p class="mt-8 text-sm leading-relaxed text-forest-600">
            Jangan bagikan tangkapan layar tiket ini ke orang lain — siapa pun yang memegang kodenya bisa dipakai
            check-in menggantikan Anda.
        </p>
    </div>
</x-layouts.app>
