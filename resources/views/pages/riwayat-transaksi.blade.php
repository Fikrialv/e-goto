<x-layouts.app title="Riwayat Transaksi">
    <div class="mx-auto w-full max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Catatan uang</p>

        <h1 class="mt-2 font-display text-3xl leading-tight font-bold text-teal-900 sm:text-4xl">
            Riwayat transaksi
        </h1>

        <p class="mt-3 max-w-xl text-sm leading-relaxed text-teal-600">
            Semua pembayaran dan pengajuan refund kamu, dari yang terbaru. Kalau cari status trip,
            bukan uangnya, lihat
            <a href="{{ route('bookings.index') }}" class="font-medium text-teal-800 underline underline-offset-4 hover:text-amber-600">Booking Saya</a>.
        </p>

        @if (session('status'))
            <p role="status" class="mt-8 rounded-2xl border border-teal-200 bg-white px-5 py-4 text-sm text-teal-800">
                {{ session('status') }}
            </p>
        @endif

        @error('type')
            <p role="alert" class="mt-8 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">{{ $message }}</p>
        @enderror

        {{-- Pembayaran --}}
        <section class="mt-12">
            <h2 class="font-display text-2xl font-bold text-teal-900">Pembayaran</h2>

            @if ($pembayaran->isEmpty())
                <p class="mt-4 rounded-2xl bg-mist-100 px-5 py-8 text-center text-sm text-teal-600">
                    Belum ada pembayaran tercatat.
                </p>
            @else
                <ul class="mt-5 space-y-4">
                    @foreach ($pembayaran as $bayar)
                        <li class="rounded-2xl border border-mist-200 bg-white p-5">
                            <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                                <div class="min-w-0">
                                    <p class="font-display text-lg font-bold text-teal-900">
                                        {{ $bayar->booking->schedule->trip->title }}
                                    </p>
                                    <p class="mt-1 font-mono text-sm text-teal-600">{{ $bayar->booking->code }}</p>
                                </div>

                                <div class="text-right">
                                    <p class="inline-flex items-center gap-1.5 font-display text-lg font-bold text-teal-900">
                                        <x-lucide-wallet class="size-4 text-teal-600" aria-hidden="true" />
                                        Rp{{ number_format($bayar->amount_declared, 0, ',', '.') }}
                                    </p>
                                    <p class="mt-1 text-xs text-teal-500">
                                        {{ $bayar->created_at->translatedFormat('j F Y, H:i') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-mist-200 pt-4">
                                <x-status-badge :tone="match ($bayar->status) {
                                    App\Enums\PaymentStatus::Verified => 'success',
                                    App\Enums\PaymentStatus::Pending => 'warning',
                                    App\Enums\PaymentStatus::Rejected => 'danger',
                                }">
                                    {{ match ($bayar->status) {
                                        App\Enums\PaymentStatus::Verified => 'Terverifikasi',
                                        App\Enums\PaymentStatus::Pending => 'Menunggu verifikasi',
                                        App\Enums\PaymentStatus::Rejected => 'Ditolak',
                                    } }}
                                </x-status-badge>

                                @if ($bayar->status === App\Enums\PaymentStatus::Rejected && $bayar->reject_reason)
                                    <p class="text-sm text-teal-600">Alasan: {{ $bayar->reject_reason }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6">{{ $pembayaran->links() }}</div>
            @endif
        </section>

        {{-- Refund --}}
        <section class="mt-14">
            <h2 class="font-display text-2xl font-bold text-teal-900">Pengajuan refund</h2>

            @if ($refund->isEmpty())
                <p class="mt-4 rounded-2xl bg-mist-100 px-5 py-8 text-center text-sm text-teal-600">
                    Belum ada pengajuan refund.
                </p>
            @else
                <ul class="mt-5 space-y-4">
                    @foreach ($refund as $ajuan)
                        <li class="rounded-2xl border border-mist-200 bg-white p-5">
                            <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                                <div class="min-w-0">
                                    <p class="font-display text-lg font-bold text-teal-900">
                                        {{ $ajuan->booking->schedule->trip->title }}
                                    </p>
                                    <p class="mt-1 font-mono text-sm text-teal-600">{{ $ajuan->booking->code }}</p>
                                </div>

                                <div class="text-right">
                                    <p class="text-sm font-medium text-teal-800">{{ $ajuan->type->label() }}</p>
                                    <p class="mt-1 text-xs text-teal-500">
                                        Diajukan {{ $ajuan->created_at->translatedFormat('j F Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-mist-200 pt-4">
                                <x-status-badge :tone="$ajuan->status->tone()">
                                    {{ $ajuan->status->label() }}
                                </x-status-badge>

                                @if ($ajuan->admin_note)
                                    <p class="text-sm text-teal-600">Catatan admin: {{ $ajuan->admin_note }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6">{{ $refund->links() }}</div>
            @endif
        </section>

        {{-- Form pengajuan --}}
        @if ($bisaAjukan->isNotEmpty())
            <section class="mt-14 rounded-3xl border border-mist-200 bg-white p-6 sm:p-8">
                <h2 class="font-display text-2xl font-bold text-teal-900">Ajukan refund</h2>

                <p class="mt-2 max-w-xl text-sm leading-relaxed text-teal-600">
                    Kalau tripmu dibatalkan penyelenggara, kuota minimumnya tidak tercapai, atau ada
                    force majeure, kamu berhak memilih salah satu dari tiga opsi di bawah. Pilih satu,
                    lalu admin yang memproses.
                </p>

                {{-- action statis diisi booking pertama supaya form tetap terkirim
                     ke tempat yang benar kalau JavaScript mati; Alpine hanya
                     menimpanya saat pilihan diganti. --}}
                <form method="POST" action="{{ route('refunds.store', $bisaAjukan->first()) }}" class="mt-6 space-y-5"
                      x-data="{ kode: '{{ $bisaAjukan->first()->code }}' }"
                      :action="'{{ url('/booking') }}/' + kode + '/refund'">
                    @csrf

                    <div>
                        <label for="booking" class="block text-sm font-medium text-teal-800">Booking yang mau diajukan</label>
                        <select id="booking" x-model="kode"
                                class="mt-2 w-full rounded-2xl border border-mist-300 bg-white px-4 py-2.5 text-sm text-teal-900">
                            @foreach ($bisaAjukan as $booking)
                                <option value="{{ $booking->code }}">
                                    {{ $booking->code }} &middot; {{ $booking->schedule->trip->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <fieldset>
                        <legend class="block text-sm font-medium text-teal-800">Opsi yang kamu pilih</legend>

                        <div class="mt-3 space-y-3">
                            @foreach (App\Enums\RefundType::cases() as $opsi)
                                <label class="flex cursor-pointer gap-3 rounded-2xl border border-mist-200 p-4 transition-colors hover:border-teal-400">
                                    <input type="radio" name="type" value="{{ $opsi->value }}" required
                                           @checked($loop->first)
                                           class="mt-1 size-4 shrink-0 accent-teal-700">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-teal-900">{{ $opsi->label() }}</span>
                                        <span class="mt-1 block text-sm leading-relaxed text-teal-600">{{ $opsi->description() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div>
                        <label for="catatan" class="block text-sm font-medium text-teal-800">Catatan (opsional)</label>
                        <textarea id="catatan" name="customer_note" rows="3" maxlength="1000"
                                  placeholder="Misalnya nomor rekening tujuan, atau jadwal pengganti yang kamu mau."
                                  class="mt-2 w-full rounded-2xl border border-mist-300 bg-white px-4 py-2.5 text-sm text-teal-900"></textarea>
                    </div>

                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-full bg-amber-600 px-7 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                        <x-lucide-upload class="size-4" aria-hidden="true" />
                        Kirim pengajuan
                    </button>
                </form>
            </section>
        @endif
    </div>
</x-layouts.app>
