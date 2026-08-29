<x-layouts.app title="Booking Saya">
    <div class="mx-auto w-full max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <h1 class="font-display text-3xl leading-tight font-bold text-teal-900 sm:text-4xl">Booking Saya</h1>
        <p class="mt-3 max-w-lg text-sm leading-relaxed text-teal-600">
            Riwayat pemesanan dan e-tiket Anda akan muncul di sini.
        </p>

        @if (filled(config('push.public_key')))
            {{--
                Opt-in Web Push (D12). Izin browser HANYA diminta setelah tombol
                ini ditekan — prompt yang muncul sendiri saat halaman dibuka
                hampir selalu ditolak, dan penolakan itu permanen per browser.
            --}}
            <div x-data="{
                    status: 'siap',
                    async nyalakan() {
                        this.status = 'proses';

                        try {
                            const izin = await Notification.requestPermission();

                            if (izin !== 'granted') {
                                this.status = 'ditolak';
                                return;
                            }

                            const registrasi = await navigator.serviceWorker.ready;
                            const langganan = await registrasi.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: @js(config('push.public_key')),
                            });

                            await fetch(@js(route('push.store')), {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': @js(csrf_token()),
                                },
                                body: JSON.stringify(langganan.toJSON()),
                            });

                            this.status = 'aktif';
                        } catch (e) {
                            this.status = 'gagal';
                        }
                    },
                 }"
                 class="mt-6 rounded-2xl border border-mist-200 bg-white px-5 py-4">
                <p class="text-sm text-teal-700">
                    Mau diberi tahu saat pembayaran diverifikasi dan sehari sebelum berangkat?
                </p>

                <button type="button" @click="nyalakan()" x-show="status === 'siap' || status === 'gagal'"
                        class="mt-3 rounded-full border border-mist-300 px-5 py-2 text-sm text-teal-700 transition-colors hover:border-teal-400">
                    Nyalakan notifikasi
                </button>

                <p x-show="status === 'proses'" x-cloak class="mt-3 text-sm text-teal-600">Menunggu izin browser…</p>
                <p x-show="status === 'aktif'" x-cloak class="mt-3 text-sm text-teal-700">Notifikasi aktif di perangkat ini.</p>
                <p x-show="status === 'ditolak'" x-cloak class="mt-3 text-sm text-teal-600">
                    Izin notifikasi ditolak browser. Ubah lewat pengaturan situs kalau berubah pikiran.
                </p>
                <p x-show="status === 'gagal'" x-cloak class="mt-3 text-sm text-amber-700">
                    Gagal mengaktifkan. Coba lagi dari browser yang mendukung notifikasi.
                </p>
            </div>
        @endif
        @if ($bookings->isEmpty())
            <x-empty-state class="mt-10"
                           title="Belum ada pemesanan"
                           message="Pilih trip yang Anda suka, pesan, dan e-tiketnya tersimpan di halaman ini.">
                <x-slot:icon><x-lucide-ticket class="size-6" /></x-slot:icon>
                <a href="{{ route('home') }}"
                   class="inline-block rounded-full bg-amber-600 px-6 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                    Lihat trip
                </a>
            </x-empty-state>
        @else
            <ul class="mt-10 space-y-4">
                @foreach ($bookings as $booking)
                    <li class="rounded-2xl border border-mist-200 bg-white p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-display text-lg font-bold text-teal-900">
                                    {{ $booking->schedule->trip->title }}
                                </p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-teal-600">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-lucide-calendar class="size-4 text-teal-500" aria-hidden="true" />
                                        {{ $booking->schedule->start_date->translatedFormat('j F Y') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-lucide-users class="size-4 text-teal-500" aria-hidden="true" />
                                        {{ $booking->pax_count }} peserta
                                    </span>
                                </p>
                                <p class="mt-1 font-mono text-xs text-teal-500">{{ $booking->code }}</p>
                            </div>

                            <div class="flex flex-col items-end gap-1.5">
                                <x-status-badge>{{ $booking->status->label() }}</x-status-badge>

                                @if ($booking->latestPayment?->status === App\Enums\PaymentStatus::Verified)
                                    <x-status-badge tone="success">Pembayaran terverifikasi</x-status-badge>
                                @elseif ($booking->latestPayment?->status === App\Enums\PaymentStatus::Pending)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-teal-500">
                                        <x-lucide-clock class="size-3.5" aria-hidden="true" />
                                        Diperiksa admin {{ config('booking.verification_eta') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @php($aksi = match ($booking->status) {
                            App\Enums\BookingStatus::PendingPayment, App\Enums\BookingStatus::AwaitingVerification => ['label' => 'Lihat pembayaran', 'url' => route('payments.show', $booking)],
                            App\Enums\BookingStatus::Confirmed, App\Enums\BookingStatus::Completed => ['label' => 'Lihat e-tiket', 'url' => route('tickets.show', $booking)],
                            default => null,
                        })

                        @if ($booking->status === App\Enums\BookingStatus::Completed && ! $booking->review)
                            <form method="POST" action="{{ route('reviews.store', $booking) }}" class="mt-5 rounded-2xl bg-mist-100 p-4">
                                @csrf
                                <p class="text-sm font-medium text-teal-800">Bagaimana tripnya?</p>

                                <div class="mt-3 flex flex-wrap items-center gap-4">
                                    <label class="flex items-center gap-2 text-sm text-teal-700">
                                        <span>Rating</span>
                                        <select name="rating" required
                                                class="rounded-lg border border-mist-300 bg-mist-50 px-3 py-2 text-sm text-teal-900">
                                            @for ($bintang = 5; $bintang >= 1; $bintang--)
                                                <option value="{{ $bintang }}">{{ $bintang }} dari 5</option>
                                            @endfor
                                        </select>
                                    </label>
                                </div>

                                <textarea name="comment" rows="2" maxlength="1000"
                                          placeholder="Ceritakan singkat — yang enak, yang perlu diperbaiki."
                                          class="mt-3 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 placeholder:text-teal-400"></textarea>

                                <button type="submit"
                                        class="mt-3 rounded-full bg-teal-700 px-5 py-2 text-sm font-medium text-mist-50 transition-colors hover:bg-teal-800">
                                    Kirim penilaian
                                </button>
                            </form>
                        @elseif ($booking->review)
                            <p class="mt-4 text-sm text-teal-600">Penilaian Anda: {{ $booking->review->rating }} dari 5.</p>
                        @endif

                        @if ($aksi)
                            <a href="{{ $aksi['url'] }}"
                               class="mt-4 inline-flex items-center gap-2 rounded-full border border-mist-300 px-5 py-2 text-sm text-teal-700 transition-colors hover:border-teal-500">
                                @if (str_contains($aksi['label'], 'tiket'))
                                    <x-lucide-ticket class="size-4" aria-hidden="true" />
                                @else
                                    <x-lucide-wallet class="size-4" aria-hidden="true" />
                                @endif
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
