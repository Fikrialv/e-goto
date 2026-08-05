<x-layouts.app :title="'Pesan '.$schedule->trip->title">
    <div class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <a href="{{ route('trips.show', $schedule->trip) }}" class="text-sm text-forest-600 hover:text-terracotta-600">
            &larr; Kembali ke detail trip
        </a>

        <p class="mt-6 text-xs font-semibold tracking-widest text-forest-500 uppercase">
            {{ $schedule->trip->category->name }}
        </p>

        <h1 class="mt-2 font-display text-3xl leading-tight font-semibold text-forest-900 sm:text-4xl">
            {{ $schedule->trip->title }}
        </h1>

        <div class="mt-8 rounded-3xl border border-sand-200 bg-white/70 p-6 sm:p-8">
            <dl class="grid gap-5 sm:grid-cols-3">
                <div>
                    <dt class="text-xs tracking-wide text-forest-500 uppercase">Tanggal berangkat</dt>
                    <dd class="mt-1 font-display text-lg font-semibold text-forest-900">
                        {{ $schedule->start_date->translatedFormat('j F Y') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs tracking-wide text-forest-500 uppercase">Sisa kuota</dt>
                    <dd class="mt-1 font-display text-lg font-semibold text-forest-900">
                        {{ $schedule->remainingQuota() }} kursi
                    </dd>
                </div>

                <div>
                    <dt class="text-xs tracking-wide text-forest-500 uppercase">Mulai dari</dt>
                    <dd class="mt-1">
                        <x-price-tag :amount="$schedule->prices->min('price')" />
                    </dd>
                </div>
            </dl>

            {{-- Form peserta (leader + anggota), field adaptif NIK/paspor, kunci
                 kuota, dan nominal unik dibangun di D4. Halaman ini sudah
                 terkunci `auth` sejak sekarang supaya gerbang login bisa diuji
                 utuh tanpa menunggu alur pemesanan jadi. --}}
            <p class="mt-8 rounded-2xl bg-sand-100 px-5 py-4 text-sm leading-relaxed text-forest-700">
                Halo {{ auth()->user()->name }} — Anda sudah masuk, jadi tinggal satu langkah lagi.
                Form data peserta dan pembayaran sedang disiapkan dan akan aktif di halaman ini.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('bookings.index') }}"
                   class="rounded-full border border-sand-300 px-5 py-2.5 text-sm text-forest-700 hover:border-sand-400">
                    Booking Saya
                </a>
                <a href="{{ route('profile.edit') }}"
                   class="rounded-full border border-sand-300 px-5 py-2.5 text-sm text-forest-700 hover:border-sand-400">
                    Lengkapi profil dulu
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
