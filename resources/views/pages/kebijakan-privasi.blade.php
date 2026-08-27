<x-legal-page
    eyebrow="Legal"
    title="Kebijakan Privasi"
    intro="Data apa yang kami kumpulkan, kenapa, berapa lama disimpan, dan siapa saja yang bisa melihatnya.">

    <aside class="rounded-2xl border border-amber-500 bg-amber-100 px-5 py-4">
        <p class="text-sm leading-relaxed text-teal-900">
            <span class="font-semibold">[SEMENTARA — validasi sebelum publish]</span>
            Jangka waktu penyimpanan data di halaman ini masih angka kerja dan belum final.
            Angka final ditetapkan sebelum website dibuka untuk umum.
        </p>
    </aside>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">1. Data yang kami kumpulkan</h2>
        <ul class="mt-3 space-y-3 text-sm leading-relaxed text-teal-700">
            <li><span class="font-medium text-teal-900">Data akun:</span> nama, alamat email, nomor telepon, dan kata sandi. Kata sandi disimpan dalam bentuk hash, tidak pernah sebagai teks terbaca — kami sendiri tidak bisa membacanya.</li>
            <li><span class="font-medium text-teal-900">Data peserta trip:</span> nama lengkap, tanggal lahir, nomor telepon, kontak darurat, dan <span class="font-medium text-teal-900">NIK atau nomor paspor</span> untuk kategori trip yang mensyaratkannya.</li>
            <li><span class="font-medium text-teal-900">Data transaksi:</span> kode pemesanan, nominal, dan gambar bukti transfer yang Anda unggah.</li>
            <li><span class="font-medium text-teal-900">Data dari Google</span> apabila Anda memilih masuk lewat Google: nama, alamat email, dan foto profil. Kami tidak menerima kata sandi Google Anda.</li>
        </ul>
    </section>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">2. Kenapa data itu diminta</h2>
        <ul class="mt-3 space-y-3 text-sm leading-relaxed text-teal-700">
            <li>NIK/paspor diminta karena penyelenggara membutuhkannya untuk perizinan kegiatan (misalnya pendaftaran pos pendakian) dan pendataan asuransi peserta — bukan untuk keperluan pemasaran.</li>
            <li>Kontak darurat dipakai hanya bila terjadi keadaan darurat selama perjalanan.</li>
            <li>Bukti transfer dipakai untuk mencocokkan pembayaran dan mendeteksi bukti yang digunakan ulang di pemesanan berbeda.</li>
        </ul>
    </section>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">3. Bagaimana data identitas dilindungi</h2>
        <div class="mt-3 space-y-3 text-sm leading-relaxed text-teal-700">
            <p>
                <span class="font-medium text-teal-900">NIK dan nomor paspor disimpan dalam keadaan terenkripsi</span> di basis data
                kami. Nomor tersebut tidak pernah ditampilkan utuh di antarmuka publik dan tidak ikut tercatat
                di log aplikasi.
            </p>
            <p>
                Bukti transfer disimpan di penyimpanan non-publik — berkasnya tidak bisa dibuka hanya
                dengan menebak alamat URL, dan hanya dapat diakses lewat jalur yang memeriksa hak akses
                terlebih dahulu.
            </p>
        </div>
    </section>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">4. Siapa saja yang menerima data Anda</h2>
        <ul class="mt-3 space-y-3 text-sm leading-relaxed text-teal-700">
            <li>
                <span class="font-medium text-teal-900">Mitra penyelenggara trip yang Anda ikuti</span> — menerima nama, kontak,
                dan nomor identitas peserta pada trip yang mereka jalankan. Ini dibutuhkan untuk perizinan
                dan asuransi kegiatan. Penyelenggara lain tidak bisa melihat data pemesanan Anda.
            </li>
            <li><span class="font-medium text-teal-900">Google</span> — hanya apabila Anda memilih masuk lewat Google, sebatas proses autentikasi.</li>
            <li><span class="font-medium text-teal-900">Penyedia layanan hosting</span> tempat aplikasi dan basis data ini berjalan.</li>
            @if (filled(config('partner.chat_widget_id')))
                <li>
                    <span class="font-medium text-teal-900">{{ config('partner.chat_widget_provider') === 'crisp' ? 'Crisp' : 'Tawk.to' }}</span>
                    — penyedia widget chat bantuan di situs ini. Isi percakapan, nama, dan kontak yang Anda ketik di
                    kotak chat tersimpan di server mereka. Jangan mengirim NIK, nomor paspor, atau data pembayaran
                    lewat kotak chat; urusan pembayaran dan tiket selalu ditangani lewat halaman pemesanan Anda.
                </li>
            @endif
            <li><span class="font-medium text-teal-900">Aparat penegak hukum</span>, apabila ada permintaan resmi yang sah secara hukum.</li>
        </ul>
        <p class="mt-3 text-sm leading-relaxed text-teal-700">
            Kami tidak menjual data Anda dan tidak membagikannya ke jaringan periklanan.
        </p>
    </section>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">5. Berapa lama data disimpan</h2>
        <div class="mt-3 space-y-3 text-sm leading-relaxed text-teal-700">
            <p>
                Data akun dan data peserta disimpan <span class="font-medium text-teal-900">selama akun Anda aktif ditambah 2 tahun
                setelah transaksi terakhir</span> — jangka itu diperlukan untuk pembukuan dan penyelesaian sengketa
                yang mungkin muncul belakangan.
                <span class="font-medium text-amber-700">[SEMENTARA — validasi sebelum publish]</span>
            </p>
            <p>Setelah jangka tersebut lewat, data identitas dihapus atau dianonimkan.</p>
        </div>
    </section>

    <section>
        <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">6. Hak Anda</h2>
        <ul class="mt-3 space-y-3 text-sm leading-relaxed text-teal-700">
            <li>Melihat dan memperbaiki data profil Anda kapan saja lewat halaman profil.</li>
            <li>Meminta koreksi data peserta pada pemesanan yang sudah dibuat, lewat admin.</li>
            <li>Meminta penghapusan akun beserta datanya, dengan catatan data transaksi yang masih berada dalam jangka penyimpanan wajib di atas tetap kami simpan sampai jangkanya berakhir.</li>
        </ul>
        <p class="mt-3 text-sm leading-relaxed text-teal-700">
            Permintaan terkait data pribadi diajukan lewat kontak admin dengan menyertakan alamat email akun Anda.
        </p>
    </section>
</x-legal-page>
