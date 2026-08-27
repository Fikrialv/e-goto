<x-layouts.app title="Private Trip"
               description="Atur trip sendiri untuk rombongan, keluarga, atau kantor.">
    <section class="border-b border-mist-200 bg-mist-100">
        <div class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <p class="text-xs font-semibold tracking-[0.18em] text-amber-600 uppercase">Rombongan</p>

            <h1 class="font-display mt-4 text-4xl leading-[1.05] font-extrabold tracking-[-0.03em] text-teal-900 sm:text-5xl">
                Bikin trip sendiri
            </h1>

            <p class="mt-5 leading-relaxed text-teal-700 sm:text-lg">
                Rombongan di atas {{ config('booking.max_pax_per_booking') }} orang, acara kantor, atau tanggal yang tidak ada di
                jadwal terbuka — kita atur terpisah. Isi garis besarnya, sisanya dibicarakan lewat WhatsApp.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        @if (session('status'))
            <div role="status" class="rounded-2xl border border-teal-200 bg-teal-50 px-5 py-5 text-sm text-teal-800">
                <p>{{ session('status') }}</p>

                @if (session('tautanWhatsApp'))
                    <a href="{{ session('tautanWhatsApp') }}" target="_blank" rel="noopener"
                       class="mt-4 inline-block rounded-full bg-amber-600 px-6 py-2.5 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                        Kirim lewat WhatsApp
                    </a>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('private-trip.store') }}" class="mt-10 grid gap-5 sm:grid-cols-2">
            @csrf

            <x-form-field name="contact_name" label="Nama Anda" required />
            <x-form-field name="destination" label="Tujuan / jenis trip" required
                          help="Misal: Bromo 2 hari, atau outing kantor di Malang." />

            <x-form-field name="depart_on" label="Perkiraan berangkat" type="date" />
            <x-form-field name="pax" label="Perkiraan jumlah peserta" type="number" />

            <div class="sm:col-span-2">
                <x-form-field name="notes" label="Hal lain yang perlu kami tahu" type="textarea"
                              help="Anggaran, titik jemput, kebutuhan khusus." />
            </div>

            <div class="sm:col-span-2">
                <button type="submit"
                        class="rounded-full bg-amber-600 px-7 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                    Siapkan pesan
                </button>

                <p class="mt-3 text-xs leading-relaxed text-teal-500">
                    Form ini tidak membuat pemesanan dan tidak menyimpan data Anda — isinya langsung jadi draf pesan
                    WhatsApp ke admin.
                </p>
            </div>
        </form>
    </div>
</x-layouts.app>
