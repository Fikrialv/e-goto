<x-layouts.app title="Jadi Mitra E-GOTO"
               description="Buka trip Anda ke peserta E-GOTO. Ajukan diri jadi mitra penyelenggara.">
    <section class="border-b border-mist-200 bg-mist-100">
        <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <p class="text-xs font-semibold tracking-[0.18em] text-amber-600 uppercase">Untuk penyelenggara trip</p>

            <h1 class="font-display mt-4 text-4xl leading-[1.05] font-extrabold tracking-[-0.03em] text-teal-900 sm:text-5xl">
                Trip Anda, peserta kami
            </h1>

            <p class="mt-5 max-w-2xl leading-relaxed text-teal-700 sm:text-lg">
                Sudah rutin bawa rombongan tapi capek cari peserta sendiri? Buka trip Anda di E-GOTO. Kami yang urus
                halaman trip, pemesanan, dan pembayaran — Anda fokus di lapangan.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="grid gap-x-12 gap-y-10 lg:grid-cols-2">
            <section>
                <h2 class="font-display text-2xl font-bold text-teal-900">Yang Anda dapat</h2>

                <ul class="mt-5 space-y-3.5 text-sm leading-relaxed text-teal-700">
                    <li class="flex items-start gap-2.5"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />Halaman trip sendiri — foto, itinerary, jadwal, harga bertingkat.</li>
                    <li class="flex items-start gap-2.5"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />Pemesanan dan pembayaran ditangani sistem, termasuk verifikasi bukti transfer.</li>
                    <li class="flex items-start gap-2.5"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />Daftar peserta per keberangkatan, lengkap dengan check-in QR di titik kumpul.</li>
                    <li class="flex items-start gap-2.5"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />Panel mitra sendiri di <span class="font-mono text-xs">/vendor</span>, terpisah dari admin.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-display text-2xl font-bold text-teal-900">Syaratnya</h2>

                <ul class="mt-5 space-y-3.5 text-sm leading-relaxed text-teal-700">
                    <li class="flex items-start gap-2.5"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />Usaha atau komunitas yang benar-benar menjalankan trip, bukan perantara.</li>
                    <li class="flex items-start gap-2.5"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />Punya penanggung jawab yang bisa dihubungi saat trip berjalan.</li>
                    <li class="flex items-start gap-2.5"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />Siap menunjukkan dokumen usaha dan identitas penanggung jawab.</li>
                    <li class="flex items-start gap-2.5"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-teal-600" aria-hidden="true" />Bersedia ngobrol dulu dengan tim kami sebelum trip pertama tayang.</li>
                </ul>
            </section>
        </div>

        <section class="mt-14 rounded-3xl border border-mist-200 bg-white p-6 sm:p-9">
            <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Langkahnya</p>

            <ol class="mt-4 grid gap-4 text-sm leading-relaxed text-teal-700 sm:grid-cols-3">
                <li><span class="font-medium text-teal-900">1. Isi form ini.</span> Lima menit, dokumen boleh menyusul lewat WhatsApp.</li>
                <li><span class="font-medium text-teal-900">2. Kami hubungi.</span> Ngobrol soal trip yang mau dibuka dan cara kerjanya.</li>
                <li><span class="font-medium text-teal-900">3. Akun mitra dibuat.</span> Anda dapat akses panel dan bisa mulai ajukan trip.</li>
            </ol>
        </section>

        @if (session('status'))
            <p role="status" class="mt-10 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm text-teal-800">
                {{ session('status') }}
            </p>
        @endif

        <section class="mt-12">
            <h2 class="font-display text-2xl font-bold text-teal-900">Ajukan diri</h2>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-teal-600">
                Belum perlu punya akun. Isi yang Anda tahu sekarang — sisanya kita bicarakan saat ngobrol.
            </p>

            <form method="POST" action="{{ route('partners.store') }}" enctype="multipart/form-data"
                  class="mt-7 grid gap-5 sm:grid-cols-2">
                @csrf

                <div class="sm:col-span-2">
                    <x-form-field name="business_name" label="Nama usaha / komunitas" required />
                </div>

                <x-form-field name="contact_name" label="Nama penanggung jawab" required />
                <x-form-field name="contact_phone" label="Nomor WhatsApp" type="tel" required
                              help="Format bebas, yang penting bisa dihubungi." />

                <div class="sm:col-span-2">
                    <x-form-field name="contact_email" label="Email" type="email" required
                                  help="Dipakai jadi akun panel mitra kalau pengajuan Anda disetujui." />
                </div>

                <div class="sm:col-span-2">
                    <x-form-field name="experience" label="Trip apa yang biasa Anda bawa?" type="textarea"
                                  help="Tujuan, berapa sering, berapa peserta sekali jalan." />
                </div>

                <div class="sm:col-span-2">
                    <label for="dokumen" class="block text-sm font-medium text-teal-800">
                        Dokumen pendukung
                        <span class="ml-1 text-xs font-normal text-teal-500">(opsional)</span>
                    </label>

                    <input id="dokumen" name="documents[]" type="file" multiple
                           accept="image/jpeg,image/png,image/webp,application/pdf"
                           class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 file:mr-4 file:rounded-full file:border-0 file:bg-teal-700 file:px-4 file:py-2 file:text-sm file:text-mist-50">

                    <p class="mt-1.5 text-xs leading-relaxed text-teal-500">
                        Akta usaha, NPWP, atau KTP penanggung jawab. JPG/PNG/WebP/PDF, maksimal 4 MB per berkas,
                        {{ config('partner.max_documents') }} berkas sekali kirim. Disimpan tertutup — hanya admin yang memverifikasi bisa membukanya.
                    </p>

                    @error('documents')
                        <p role="alert" class="mt-2 text-xs text-amber-700">{{ $message }}</p>
                    @enderror
                    @error('documents.*')
                        <p role="alert" class="mt-2 text-xs text-amber-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <button type="submit"
                            class="rounded-full bg-amber-600 px-7 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                        Kirim pengajuan
                    </button>

                    <p class="mt-3 text-xs leading-relaxed text-teal-500">
                        Dengan mengirim, Anda setuju data di form ini kami simpan dan gunakan untuk proses seleksi mitra —
                        lihat <a href="{{ route('pages.privacy') }}" class="underline underline-offset-4 hover:text-amber-600">Kebijakan Privasi</a>.
                    </p>
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>
