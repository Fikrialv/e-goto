<x-layouts.app title="Pembayaran">
    <div class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Langkah terakhir</p>

        <h1 class="mt-2 font-display text-3xl leading-tight font-bold text-teal-900 sm:text-4xl">
            Selesaikan pembayaran
        </h1>

        <p class="mt-3 max-w-xl text-sm leading-relaxed text-teal-600">
            {{ $booking->schedule->trip->title }} &middot;
            {{ $booking->schedule->start_date->translatedFormat('j F Y') }} &middot;
            {{ $booking->pax_count }} peserta
        </p>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="rounded-3xl border border-mist-200 bg-white/70 p-6">
                <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Kode booking</p>
                <p class="mt-2 font-mono text-3xl font-semibold tracking-tight text-teal-900 sm:text-4xl">
                    {{ $booking->code }}
                </p>
                <p class="mt-2 text-sm text-teal-600">
                    Tulis kode ini di catatan/berita transfer supaya pembayaran Anda cepat dikenali.
                </p>

                <hr class="my-6 border-mist-200">

                <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Nominal yang harus dibayar</p>
                <p class="mt-2 font-display text-3xl font-bold text-teal-900 sm:text-4xl">
                    Rp{{ number_format($instruksi->totalAmount, 0, ',', '.') }}
                </p>
                <p class="mt-2 text-sm leading-relaxed text-teal-600">
                    Sudah termasuk <strong class="text-amber-700">kode unik {{ $instruksi->uniqueCode }}</strong>
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
                       class="mt-6 rounded-2xl bg-mist-100 px-5 py-4 text-sm text-teal-700">
                        Kursi ditahan sampai
                        <strong>{{ $instruksi->expiresAt->translatedFormat('j F Y, H:i') }}</strong>
                        &middot; sisa waktu <span class="font-mono font-semibold" x-text="teks">--:--:--</span>
                    </p>
                @endif
            </div>

            @if ($sudahKonfirmasi)
                <div class="rounded-3xl border border-mist-200 bg-white/70 p-6">
                    <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Scan QRIS</p>
                    <p class="mt-1 text-sm text-teal-600">{{ $instruksi->merchantName }}</p>

                    <img src="{{ asset($instruksi->qrisImagePath) }}" alt="Kode QRIS pembayaran {{ $instruksi->merchantName }}"
                         loading="lazy" class="mt-4 w-full max-w-xs rounded-2xl border border-mist-200 bg-mist-50">

                    {{-- Unduh kodenya: banyak orang membayar dari HP lain atau dari
                         aplikasi bank yang tidak bisa memindai layar yang sama. --}}
                    <a href="{{ asset($instruksi->qrisImagePath) }}" download
                       class="mt-4 inline-flex items-center gap-2 rounded-full border border-mist-300 px-4 py-2 text-sm text-teal-700 transition-colors hover:border-teal-400">
                        Unduh gambar QRIS
                    </a>

                    <ol class="mt-5 list-decimal space-y-1.5 pl-5 text-sm leading-relaxed text-teal-700">
                        <li>Buka aplikasi bank/e-wallet, pilih bayar QRIS.</li>
                        <li>Masukkan nominal <strong>persis</strong> seperti di samping.</li>
                        <li>Tulis kode booking di catatan transfer.</li>
                        <li>Unggah bukti pembayaran di halaman ini.</li>
                    </ol>
                </div>
            @else
                {{-- Konfirmasi metode pembayaran (D7.6 f). QRIS & form unggah baru
                     dirender setelah tombol di bawah ditekan — orang perlu tahu
                     lebih dulu bahwa pembayarannya diperiksa manusia. --}}
                <div class="rounded-3xl border border-amber-500/40 bg-amber-50/60 p-6">
                    <p class="text-xs font-semibold tracking-widest text-amber-700 uppercase">Sebelum bayar</p>

                    <h2 class="mt-2 font-display text-xl font-bold text-teal-900">Begini cara pembayarannya diproses</h2>

                    <ol class="mt-4 space-y-2.5 text-sm leading-relaxed text-teal-800">
                        <li>1. Bayar lewat QRIS sesuai nominal persis di samping.</li>
                        <li>2. Unggah bukti transfer di halaman ini.</li>
                        <li>3. Admin memeriksa bukti Anda satu per satu, {{ config('booking.verification_eta') }}.</li>
                        <li>4. Setelah disetujui, e-tiket terbit otomatis untuk semua peserta.</li>
                    </ol>

                    <p class="mt-4 rounded-2xl bg-white/70 px-4 py-3 text-sm leading-relaxed text-teal-700">
                        <strong class="text-teal-900">Pembayaran tidak langsung terkonfirmasi.</strong>
                        QRIS ini diverifikasi manual oleh admin, bukan sistem otomatis — jadi tiket tidak terbit
                        sedetik setelah Anda transfer.
                    </p>

                    <p class="mt-4 text-sm text-teal-600">
                        Dengan lanjut, Anda setuju pada
                        <a href="{{ route('pages.terms') }}" class="font-medium text-teal-800 underline underline-offset-4 hover:text-amber-600">Syarat &amp; Ketentuan</a>
                        dan
                        <a href="{{ route('pages.privacy') }}" class="font-medium text-teal-800 underline underline-offset-4 hover:text-amber-600">Kebijakan Privasi</a>.
                    </p>

                    <form method="POST" action="{{ route('payments.confirm', $booking) }}" class="mt-5">
                        @csrf
                        <button type="submit"
                                class="rounded-full bg-amber-600 px-7 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                            Saya paham, lanjutkan ke pembayaran
                        </button>
                    </form>
                </div>
            @endif
        </div>

        @if (session('status'))
            <p role="status" class="mt-6 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm text-teal-800">
                {{ session('status') }}
            </p>
        @endif

        @php($pembayaran = $booking->latestPayment)

        {{-- Status pembayaran memakai PaymentStatus + komponen status-badge yang
             sudah ada — tidak ada state baru yang diperkenalkan di sini. --}}
        @if ($pembayaran)
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-status-badge :tone="match ($pembayaran->status) {
                    App\Enums\PaymentStatus::Verified => 'success',
                    App\Enums\PaymentStatus::Pending => 'warning',
                    App\Enums\PaymentStatus::Rejected => 'danger',
                }">
                    {{ match ($pembayaran->status) {
                        App\Enums\PaymentStatus::Verified => 'Pembayaran terverifikasi',
                        App\Enums\PaymentStatus::Pending => 'Menunggu verifikasi',
                        App\Enums\PaymentStatus::Rejected => 'Bukti ditolak',
                    } }}
                </x-status-badge>

                @if ($pembayaran->status === App\Enums\PaymentStatus::Pending)
                    <p class="text-sm text-teal-600">
                        Admin memeriksa bukti Anda {{ config('booking.verification_eta') }}.
                    </p>
                @endif
            </div>
        @endif

        @if ($pembayaran?->status === App\Enums\PaymentStatus::Rejected)
            <div role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                <p class="font-medium">Bukti pembayaran sebelumnya ditolak admin.</p>
                <p class="mt-1">Alasan: {{ $pembayaran->reject_reason }}</p>
                <p class="mt-1">Silakan perbaiki dan unggah ulang bukti transfer Anda di bawah ini.</p>
            </div>
        @endif

        @if ($sudahKonfirmasi)
        <div class="mt-8 rounded-3xl border border-mist-200 bg-white/70 p-6 sm:p-8">
            <h2 class="font-display text-2xl font-bold text-teal-900">Unggah bukti pembayaran</h2>
            <p class="mt-1 text-sm leading-relaxed text-teal-600">
                Unggah tangkapan layar atau foto struk transfer. Berkas disimpan tertutup dan hanya bisa dilihat
                Anda dan admin yang memverifikasi.
            </p>

            @if ($pembayaran?->status === App\Enums\PaymentStatus::Pending && $pembayaran->proof_path)
                <div class="mt-5 flex flex-wrap items-center gap-4 rounded-2xl bg-mist-100 px-5 py-4">
                    <p class="text-sm text-teal-700">
                        Bukti sudah terkirim {{ $pembayaran->created_at->diffForHumans() }} &middot; menunggu verifikasi admin.
                    </p>
                    <a href="{{ route('payments.proof', $booking) }}" target="_blank" rel="noopener"
                       class="text-sm text-teal-700 underline underline-offset-4 hover:text-amber-600">
                        Lihat bukti yang saya kirim
                    </a>
                </div>
            @endif

            @error('proof')
                <p role="alert" class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-800">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ route('payments.store', $booking) }}" enctype="multipart/form-data" class="mt-5">
                @csrf

                <label for="bukti" class="block text-sm font-medium text-teal-800">Berkas bukti transfer</label>
                <input id="bukti" name="proof" type="file" accept="image/jpeg,image/png,image/webp" required
                       class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 file:mr-4 file:rounded-full file:border-0 file:bg-teal-700 file:px-4 file:py-2 file:text-sm file:text-mist-50">
                <p class="mt-1.5 text-xs text-teal-500">JPG, PNG, atau WebP. Maksimal 4 MB.</p>

                <button type="submit"
                        class="mt-5 rounded-full bg-amber-600 px-7 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                    Kirim bukti pembayaran
                </button>
            </form>

            <p class="mt-6 text-sm text-teal-600">
                Sudah mengunggah tapi ingin memberi tahu admin langsung?
                <a href="{{ $linkWhatsApp }}" target="_blank" rel="noopener"
                   class="font-medium text-teal-800 underline underline-offset-4 hover:text-amber-600">
                    Kirim notifikasi lewat WhatsApp
                </a>
            </p>
        </div>
        @endif

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('bookings.index') }}"
               class="rounded-full border border-mist-300 px-5 py-2.5 text-sm text-teal-700 hover:border-mist-400">
                Booking Saya
            </a>
        </div>
    </div>
</x-layouts.app>
