@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $trip = $booking->schedule->trip;
@endphp

<x-layouts.app :title="'E-tiket '.$booking->code">
    <div class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <a href="{{ route('bookings.index') }}" class="text-sm text-teal-600 hover:text-amber-600">
            &larr; Kembali ke Booking Saya
        </a>

        <h1 class="mt-6 font-display text-3xl leading-tight font-bold text-teal-900 sm:text-4xl">
            E-tiket Anda sudah terbit
        </h1>
        <p class="mt-3 max-w-xl text-sm leading-relaxed text-teal-600">
            Tunjukkan kode QR di bawah ke petugas saat berkumpul. Setiap peserta punya tiket sendiri dan hanya
            bisa dipakai satu kali.
        </p>

        <div class="mt-8 flex flex-wrap gap-3 print:hidden">
            <button type="button" onclick="window.print()"
                    class="rounded-full bg-teal-800 px-6 py-2.5 text-sm font-medium text-mist-50 transition-colors hover:bg-teal-900">
                Cetak / simpan PDF
            </button>
        </div>

        <div class="mt-8 space-y-6">
            @foreach ($booking->tickets as $ticket)
                <article class="overflow-hidden rounded-3xl border border-mist-200 bg-white print:break-inside-avoid">
                    <div class="flex items-center justify-between gap-4 bg-teal-800 px-6 py-4 text-mist-50">
                        {{-- Kop tiket berlatar teal gelap: wordmark dibalik jadi terang lewat filter,
                             supaya tidak perlu berkas logo kedua khusus latar gelap. --}}
                        <img src="{{ asset('images/Logo1.svg') }}" alt="E-GOTO" width="1983" height="793"
                             class="h-6 w-auto brightness-0 invert" loading="lazy">
                        <p class="font-mono text-sm">{{ $booking->code }}</p>
                    </div>

                    <div class="grid gap-6 p-6 sm:grid-cols-[1fr_auto] sm:p-8">
                        <div>
                            <p class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-widest text-teal-500 uppercase"><x-lucide-users class="size-4" aria-hidden="true" />Peserta</p>
                            <p class="mt-1 font-display text-2xl font-bold text-teal-900">
                                {{ $ticket->participant->full_name }}
                            </p>

                            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="inline-flex items-center gap-1.5 text-xs tracking-wide text-teal-500 uppercase"><x-lucide-map-pin class="size-3.5" aria-hidden="true" />Trip</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-teal-900">{{ $trip->title }}</dd>
                                </div>
                                <div>
                                    <dt class="inline-flex items-center gap-1.5 text-xs tracking-wide text-teal-500 uppercase"><x-lucide-calendar class="size-3.5" aria-hidden="true" />Tanggal</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-teal-900">
                                        {{ $booking->schedule->start_date->translatedFormat('j F Y') }}
                                        @if ($booking->schedule->end_date)
                                            &ndash; {{ $booking->schedule->end_date->translatedFormat('j F Y') }}
                                        @endif
                                    </dd>
                                </div>
                                @if ($trip->meeting_point)
                                    <div class="sm:col-span-2">
                                        <dt class="inline-flex items-center gap-1.5 text-xs tracking-wide text-teal-500 uppercase"><x-lucide-map-pin class="size-3.5" aria-hidden="true" />Titik kumpul</dt>
                                        <dd class="mt-0.5 text-sm font-medium text-teal-900">{{ $trip->meeting_point }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="inline-flex items-center gap-1.5 text-xs tracking-wide text-teal-500 uppercase"><x-lucide-ticket class="size-3.5" aria-hidden="true" />Status tiket</dt>
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
                            <div class="rounded-2xl border border-mist-200 bg-mist-50 p-3">
                                {!! QrCode::size(160)->margin(0)->generate($ticket->token) !!}
                            </div>
                            <p class="mt-2 font-mono text-[11px] tracking-tight break-all text-teal-500">
                                {{ $ticket->token }}
                            </p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if (filled($trip->category->gear_checklist))
            <section class="mt-10 rounded-3xl border border-mist-200 bg-white p-6 sm:p-7">
                <h2 class="font-display text-xl font-bold text-teal-900">Checklist sebelum berangkat</h2>
                <p class="mt-1.5 text-sm text-teal-600">Bawaan standar trip {{ Str::lower($trip->category->name) }}.</p>

                <ul class="mt-5 grid gap-x-6 gap-y-2.5 sm:grid-cols-2">
                    @foreach ($trip->category->gear_checklist as $barang)
                        <li class="flex gap-2.5 text-sm text-teal-700">
                            <x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />
                            {{ $barang }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="mt-8 text-sm leading-relaxed text-teal-600">
            Jangan bagikan tangkapan layar tiket ini ke orang lain — siapa pun yang memegang kodenya bisa dipakai
            check-in menggantikan Anda.
        </p>
    </div>
</x-layouts.app>
