@php
    $maksPax = config('booking.max_pax_per_booking');
@endphp

<x-legal-page
    eyebrow="Legal"
    title="Syarat &amp; Ketentuan"
    intro="Aturan main saat memesan lewat E-GOTO: siapa bertanggung jawab atas apa, dan bagaimana perlakuan dana kalau perjalanan batal.">

    {{-- Peringatan angka sementara ditaruh di halaman, bukan cuma di dokumen
         internal — supaya angka yang belum divalidasi tidak ikut terbit diam-diam. --}}
    <aside class="rounded-2xl border border-amber-500 bg-white px-5 py-4">
        <p class="text-sm leading-relaxed text-teal-900">
            <span class="font-semibold">[SEMENTARA — validasi sebelum publish]</span>
            Persentase pengembalian dana dan biaya administrasi di halaman ini masih angka kerja
            dan belum final. Angka final ditetapkan sebelum website dibuka untuk umum.
        </p>
    </aside>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">1. Peran E-GOTO dan mitra penyelenggara</h2>
        <div class="mt-3 space-y-3 text-sm leading-relaxed text-teal-700">
            <p>
                E-GOTO adalah wadah pemesanan. Kami menyediakan halaman trip, memproses pemesanan,
                memverifikasi pembayaran, dan menerbitkan tiket.
            </p>
            <p>
                <span class="font-medium text-teal-900">Pelaksanaan trip di lapangan adalah tanggung jawab mitra penyelenggara</span>
                yang namanya tercantum pada halaman trip — termasuk transport, pemandu, akomodasi,
                perizinan, dan keselamatan selama kegiatan berlangsung. Perubahan teknis di lapangan
                (rute, urutan kunjungan, titik kumpul) adalah kewenangan penyelenggara selama tidak
                mengubah inti perjalanan yang Anda pesan.
            </p>
            <p>
                Untuk trip yang diselenggarakan langsung oleh E-GOTO, kedua peran di atas ada pada kami.
            </p>
        </div>
    </section>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">2. Pemesanan &amp; pembayaran</h2>
        <ul class="mt-3 space-y-3 text-sm leading-relaxed text-teal-700">
            <li>Satu pemesanan maksimal <span class="font-medium text-teal-900">{{ $maksPax }} peserta</span>. Rombongan lebih besar ditangani sebagai private trip lewat admin.</li>
            <li>Kursi ditahan sejak pemesanan dibuat, bukan sejak dibayar. Kalau bukti pembayaran tidak diunggah sampai batas waktu yang tertera di halaman pembayaran, pemesanan otomatis kedaluwarsa dan kursi dilepas.</li>
            <li>Nominal transfer harus <span class="font-medium text-teal-900">persis</span> sampai digit terakhir. Digit unik itu yang dipakai admin untuk mencocokkan pembayaran Anda.</li>
            <li>Pembayaran diverifikasi manual. Tiket terbit otomatis setelah pembayaran disetujui.</li>
            <li>E-GOTO berhak menolak atau membatalkan pemesanan secara sepihak apabila bukti pembayaran terindikasi dipalsukan, digunakan ulang di pemesanan lain, atau tidak dapat dicocokkan dengan dana yang benar-benar masuk.</li>
        </ul>
    </section>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">3. Kewajiban peserta</h2>
        <ul class="mt-3 space-y-3 text-sm leading-relaxed text-teal-700">
            <li>Mengisi data peserta dengan benar, termasuk NIK/paspor untuk kategori yang mensyaratkannya. Data yang salah dapat membuat peserta ditolak di titik pemeriksaan atau pos perizinan, dan hal ini bukan dasar pengembalian dana.</li>
            <li>Hadir di titik kumpul sesuai waktu yang ditentukan. Keterlambatan yang membuat peserta tertinggal tidak menimbulkan hak pengembalian dana.</li>
            <li>Menunjukkan e-tiket (QR) saat check-in. Satu QR sah untuk satu peserta dan sekali pakai; tiket tidak dapat dipindahtangankan tanpa persetujuan admin.</li>
            <li>Mengikuti arahan keselamatan dari penyelenggara selama kegiatan.</li>
        </ul>
    </section>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">4. Kebijakan pengembalian dana (refund)</h2>
        <p class="mt-3 text-sm leading-relaxed text-teal-700">
            Besaran pengembalian ditentukan oleh <span class="font-medium text-teal-900">siapa yang membatalkan</span>
            dan <span class="font-medium text-teal-900">seberapa dekat dengan tanggal keberangkatan</span>, karena
            biaya operasional penyelenggara sudah terpakai lebih dulu saat hari-H mendekat.
        </p>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[34rem] border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b border-mist-300">
                        <th scope="col" class="py-3 pr-4 font-semibold text-teal-900">Situasi</th>
                        <th scope="col" class="py-3 pr-4 font-semibold text-teal-900">Pengembalian</th>
                        <th scope="col" class="py-3 font-semibold text-teal-900">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-200 text-teal-700">
                    <tr>
                        <th scope="row" class="py-4 pr-4 font-medium text-teal-900">
                            (a) Trip dibatalkan penyelenggara, atau kuota minimum tidak tercapai
                        </th>
                        <td class="py-4 pr-4 font-medium text-teal-900">Anda pilih 1 dari 3 opsi</td>
                        <td class="py-4">Lihat penjelasan opsi di bawah tabel — bukan lagi otomatis refund.</td>
                    </tr>
                    <tr>
                        <th scope="row" class="py-4 pr-4 font-medium text-teal-900">
                            (b) Anda membatalkan lebih dari H-7 dari tanggal keberangkatan
                        </th>
                        <td class="py-4 pr-4 font-medium text-teal-900">
                            50% <span class="font-normal text-teal-600">dikurangi biaya administrasi Rp25.000</span>
                        </td>
                        <td class="py-4">
                            <span class="font-medium text-amber-700">[SEMENTARA — validasi sebelum publish]</span>
                            Diajukan lewat admin dengan menyertakan kode pemesanan.
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="py-4 pr-4 font-medium text-teal-900">
                            (c) Anda membatalkan H-7 ke bawah (H-7 sampai hari-H)
                        </th>
                        <td class="py-4 pr-4 font-medium text-teal-900">Tidak ada</td>
                        <td class="py-4">Ditawarkan penjadwalan ulang ke jadwal lain pada trip yang sama, kalau mitra penyelenggara punya slot.</td>
                    </tr>
                    <tr>
                        <th scope="row" class="py-4 pr-4 font-medium text-teal-900">
                            (d) Force majeure
                        </th>
                        <td class="py-4 pr-4 font-medium text-teal-900">Anda pilih 1 dari 3 opsi</td>
                        <td class="py-4">Sama seperti (a) — wajib salah satu, tidak hangus. Mencakup bencana alam, larangan resmi otoritas, dan cuaca ekstrem yang membuat perjalanan tidak aman.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="mt-5 text-sm leading-relaxed text-teal-700">
            Untuk situasi (a) dan (d), Anda berhak memilih salah satu dari tiga opsi berikut:
            <span class="font-medium text-teal-900">(i)</span> pengembalian dana 100%;
            <span class="font-medium text-teal-900">(ii)</span> pindah ke trip atau jadwal lain — kalau harga trip pengganti berbeda, selisihnya dibicarakan langsung dengan admin;
            <span class="font-medium text-teal-900">(iii)</span> masuk daftar tunggu (waitlist) untuk jadwal trip yang sama berikutnya.
            Sampaikan pilihan Anda ke admin lewat WhatsApp atau kontak yang tertera di halaman pemesanan — proses ini masih ditangani admin secara manual, belum otomatis lewat website.
        </p>

        <p class="mt-5 text-sm leading-relaxed text-teal-700">
            Pengembalian dana dikirim ke rekening pengirim yang sama dengan pembayaran awal.
        </p>
    </section>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">5. Perubahan ketentuan</h2>
        <p class="mt-3 text-sm leading-relaxed text-teal-700">
            Ketentuan ini dapat diperbarui. Yang berlaku untuk pemesanan Anda adalah versi yang
            tampil pada saat pemesanan dibuat. Perubahan besar akan diberitahukan di halaman ini
            beserta tanggal pembaruannya.
        </p>
    </section>
</x-legal-page>
