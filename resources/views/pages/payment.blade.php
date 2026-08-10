<x-layouts.app title="Pembayaran">
    <div class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <p class="text-xs font-semibold tracking-widest text-forest-500 uppercase">Langkah terakhir</p>

        <h1 class="mt-2 font-display text-3xl leading-tight font-semibold text-forest-900 sm:text-4xl">
            Selesaikan pembayaran
        </h1>

        <p class="mt-3 max-w-xl text-sm leading-relaxed text-forest-600">
            {{ $booking->schedule->trip->title }} &middot;
            {{ $booking->schedule->start_date->translatedFormat('j F Y') }} &middot;
            {{ $booking->pax_count }} peserta
        </p>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="rounded-3xl border border-sand-200 bg-white/70 p-6">
                <p class="text-xs font-semibold tracking-widest text-forest-500 uppercase">Kode booking</p>
                <p class="mt-2 font-mono text-3xl font-semibold tracking-tight text-forest-900 sm:text-4xl">
                    {{ $booking->code }}
                </p>
                <p class="mt-2 text-sm text-forest-600">
                    Tulis kode ini di catatan/berita transfer supaya pembayaran Anda cepat dikenali.
                </p>

                <hr class="my-6 border-sand-200">

                <p class="text-xs font-semibold tracking-widest text-forest-500 uppercase">Nominal yang harus dibayar</p>
                <p class="mt-2 font-display text-3xl font-semibold text-forest-900 sm:text-4xl">
                    Rp{{ number_format($instruksi->totalAmount, 0, ',', '.') }}
                </p>
                <p class="mt-2 text-sm leading-relaxed text-forest-600">
                    Sudah termasuk <strong class="text-terracotta-700">kode unik {{ $instruksi->uniqueCode }}</strong>
                    (harga trip Rp{{ number_format($instruksi->totalAmount - $instruksi->uniqueCode, 0, ',', '.') }}).
                    Transfer <strong>persis sampai digit terakhir</strong> — angka itulah yang membedakan pembayaran
                    Anda dari pemesan lain.
                </p>

                @if ($instruksi->expiresAt)
                    <p x-data="{
                            sisa: {{ max(0, $instruksi->expiresAt->diffInSeconds(now(), absolute: true)) }},
                            get teks() {
                                const j = String(Math.floor(this.sisa / 3600)).padStart(2, '0');
                                const m = String(Math.floor((this.sisa % 3600) / 60)).padStart(2, '0');
                                const d = String(this.sisa % 60).padStart(2, '0');
                                return j + ':' + m + ':' + d;
                            },
                       }"
                       x-init="setInterval(() => { if (sisa > 0) sisa-- }, 1000)"
                       class="mt-6 rounded-2xl bg-sand-100 px-5 py-4 text-sm text-forest-700">
                        Kursi ditahan sampai
                        <strong>{{ $instruksi->expiresAt->translatedFormat('j F Y, H:i') }}</strong>
                        &middot; sisa waktu <span class="font-mono font-semibold" x-text="teks">--:--:--</span>
                    </p>
                @endif
            </div>

            <div class="rounded-3xl border border-sand-200 bg-white/70 p-6">
                <p class="text-xs font-semibold tracking-widest text-forest-500 uppercase">Scan QRIS</p>
                <p class="mt-1 text-sm text-forest-600">{{ $instruksi->merchantName }}</p>

                <img src="{{ asset($instruksi->qrisImagePath) }}" alt="Kode QRIS pembayaran {{ $instruksi->merchantName }}"
                     loading="lazy" class="mt-4 w-full max-w-xs rounded-2xl border border-sand-200 bg-sand-50">

                <ol class="mt-5 list-decimal space-y-1.5 pl-5 text-sm leading-relaxed text-forest-700">
                    <li>Buka aplikasi bank/e-wallet, pilih bayar QRIS.</li>
                    <li>Masukkan nominal <strong>persis</strong> seperti di samping.</li>
                    <li>Tulis kode booking di catatan transfer.</li>
                    <li>Unggah bukti pembayaran di halaman ini.</li>
                </ol>
            </div>
        </div>

        @if (session('status'))
            <p role="status" class="mt-6 rounded-2xl border border-forest-200 bg-forest-50 px-5 py-4 text-sm text-forest-800">
                {{ session('status') }}
            </p>
        @endif

        @php($pembayaran = $booking->latestPayment)

        @if ($pembayaran?->status === App\Enums\PaymentStatus::Rejected)
            <div role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                <p class="font-medium">Bukti pembayaran sebelumnya ditolak admin.</p>
                <p class="mt-1">Alasan: {{ $pembayaran->reject_reason }}</p>
                <p class="mt-1">Silakan perbaiki dan unggah ulang bukti transfer Anda di bawah ini.</p>
            </div>
        @endif

        <div class="mt-8 rounded-3xl border border-sand-200 bg-white/70 p-6 sm:p-8">
            <h2 class="font-display text-2xl font-semibold text-forest-900">Unggah bukti pembayaran</h2>
            <p class="mt-1 text-sm leading-relaxed text-forest-600">
                Unggah tangkapan layar atau foto struk transfer. Berkas disimpan tertutup dan hanya bisa dilihat
                Anda dan admin yang memverifikasi.
            </p>

            @if ($pembayaran?->status === App\Enums\PaymentStatus::Pending && $pembayaran->proof_path)
                <div class="mt-5 flex flex-wrap items-center gap-4 rounded-2xl bg-sand-100 px-5 py-4">
                    <p class="text-sm text-forest-700">
                        Bukti sudah terkirim {{ $pembayaran->created_at->diffForHumans() }} &middot; menunggu verifikasi admin.
                    </p>
                    <a href="{{ route('payments.proof', $booking) }}" target="_blank" rel="noopener"
                       class="text-sm text-forest-700 underline underline-offset-4 hover:text-terracotta-600">
                        Lihat bukti yang saya kirim
                    </a>
                </div>
            @endif

            @error('proof')
                <p role="alert" class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-800">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ route('payments.store', $booking) }}" enctype="multipart/form-data" class="mt-5">
                @csrf

                <label for="bukti" class="block text-sm font-medium text-forest-800">Berkas bukti transfer</label>
                <input id="bukti" name="proof" type="file" accept="image/jpeg,image/png,image/webp" required
                       class="mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 file:mr-4 file:rounded-full file:border-0 file:bg-forest-700 file:px-4 file:py-2 file:text-sm file:text-sand-50">
                <p class="mt-1.5 text-xs text-forest-500">JPG, PNG, atau WebP. Maksimal 4 MB.</p>

                <button type="submit"
                        class="mt-5 rounded-full bg-terracotta-600 px-7 py-3 text-sm font-medium text-sand-50 transition-colors hover:bg-terracotta-700">
                    Kirim bukti pembayaran
                </button>
            </form>

            <p class="mt-6 text-sm text-forest-600">
                Sudah mengunggah tapi ingin memberi tahu admin langsung?
                <a href="{{ $linkWhatsApp }}" target="_blank" rel="noopener"
                   class="font-medium text-forest-800 underline underline-offset-4 hover:text-terracotta-600">
                    Kirim notifikasi lewat WhatsApp
                </a>
            </p>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('bookings.index') }}"
               class="rounded-full border border-sand-300 px-5 py-2.5 text-sm text-forest-700 hover:border-sand-400">
                Booking Saya
            </a>
        </div>
    </div>
</x-layouts.app>
