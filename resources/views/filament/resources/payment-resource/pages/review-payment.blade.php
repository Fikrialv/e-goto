@php
    use App\Enums\PaymentStatus;

    $booking = $this->record->booking;
    $selisih = $this->record->amount_declared - $booking->total_amount;
@endphp

<x-filament-panels::page>
    @if ($this->record->is_duplicate_flagged)
        <div class="rounded-xl border border-danger-300 bg-danger-50 p-4 text-danger-800 dark:border-danger-700 dark:bg-danger-950 dark:text-danger-200">
            <p class="font-semibold">Bukti ini sama persis dengan bukti pada booking lain.</p>
            <p class="mt-1 text-sm">
                Cocokkan mutasi rekening sebelum menyetujui. Bisa jadi customer salah unggah, bisa juga satu bukti
                dipakai untuk dua pesanan — keputusan tetap di Anda, sistem tidak menolak otomatis.
            </p>
        </div>
    @endif

    @if ($this->record->status !== PaymentStatus::Pending)
        <div class="rounded-xl border border-gray-300 bg-gray-50 p-4 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            Pembayaran ini sudah diputuskan
            {{ $this->record->verified_at?->translatedFormat('j F Y, H:i') }}
            oleh {{ $this->record->verifiedBy?->name ?? 'sistem' }}.
            @if ($this->record->reject_reason)
                <span class="block mt-1">Alasan penolakan: {{ $this->record->reject_reason }}</span>
            @endif
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Yang seharusnya dibayar</h2>

            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Kode booking</dt>
                    <dd class="mt-0.5 font-mono text-2xl font-semibold text-gray-950 dark:text-white">{{ $booking->code }}</dd>
                </div>

                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Nominal seharusnya</dt>
                    <dd class="mt-0.5 text-3xl font-semibold text-gray-950 dark:text-white">
                        Rp{{ number_format($booking->total_amount, 0, ',', '.') }}
                    </dd>
                    <dd class="mt-1 text-gray-500 dark:text-gray-400">
                        Harga trip Rp{{ number_format($booking->subtotal, 0, ',', '.') }}
                        + kode unik <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $booking->unique_code }}</span>
                    </dd>
                </div>

                @if ($selisih !== 0)
                    <div class="rounded-lg bg-warning-50 p-3 text-warning-800 dark:bg-warning-950 dark:text-warning-200">
                        Nominal yang diakui customer berbeda
                        {{ $selisih > 0 ? 'lebih' : 'kurang' }} Rp{{ number_format(abs($selisih), 0, ',', '.') }}
                        (Rp{{ number_format($this->record->amount_declared, 0, ',', '.') }}).
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Pemesan</dt>
                        <dd class="mt-0.5 text-gray-950 dark:text-white">{{ $booking->user->name }}</dd>
                        <dd class="text-gray-500 dark:text-gray-400">{{ $booking->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Peserta</dt>
                        <dd class="mt-0.5 text-gray-950 dark:text-white">{{ $booking->pax_count }} orang</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Trip</dt>
                        <dd class="mt-0.5 text-gray-950 dark:text-white">{{ $booking->schedule->trip->title }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Berangkat</dt>
                        <dd class="mt-0.5 text-gray-950 dark:text-white">
                            {{ $booking->schedule->start_date->translatedFormat('j F Y') }}
                        </dd>
                    </div>
                </div>

                @if ($booking->notes)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Catatan customer</dt>
                        <dd class="mt-0.5 text-gray-950 dark:text-white">{{ $booking->notes }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Bukti yang dikirim</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Diunggah {{ $this->record->created_at->translatedFormat('j F Y, H:i') }}
            </p>

            @if ($this->record->proof_path)
                <a href="{{ route('admin.payments.proof', $this->record) }}" target="_blank" rel="noopener"
                   class="mt-4 block overflow-hidden rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                    <img src="{{ route('admin.payments.proof', $this->record) }}"
                         alt="Bukti pembayaran booking {{ $booking->code }}"
                         class="w-full bg-gray-50 object-contain dark:bg-gray-950">
                </a>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Klik gambar untuk membukanya ukuran penuh.</p>
            @else
                <p class="mt-4 rounded-lg bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                    Berkas bukti sudah tidak tersimpan (customer mengunggah ulang).
                </p>
            @endif
        </section>
    </div>
</x-filament-panels::page>
