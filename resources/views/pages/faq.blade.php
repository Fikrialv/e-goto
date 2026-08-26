@php
    /*
     * Pertanyaan diambil dari titik yang benar-benar bikin orang berhenti di
     * alur ini (nominal unik, jeda verifikasi manual, cap 12 peserta) — bukan
     * daftar FAQ generik yang tidak ada hubungannya dengan cara kerja E-GOTO.
     */
    $maksPax = config('booking.max_pax_per_booking');
    $tenggat = (int) round(config('booking.expiry_minutes') / 60);

    $kelompok = [
        'Memesan trip' => [
            [
                'Perlu punya akun untuk melihat-lihat trip?',
                'Tidak. Seluruh halaman trip, kategori, jadwal, dan harga bisa dibuka tanpa login. Akun baru diminta tepat saat Anda menekan "Booking", karena mulai titik itu kami perlu tahu tiket ini milik siapa.',
            ],
            [
                'Berapa lama kursi ditahan setelah saya memesan?',
                "Kursi ditahan {$tenggat} jam sejak pemesanan dibuat. Kalau bukti pembayaran belum diunggah sampai batas itu, pesanan otomatis kedaluwarsa dan kursinya dilepas untuk orang lain. Anda bisa memesan ulang selama kuota masih ada.",
            ],
            [
                "Kenapa satu pemesanan dibatasi {$maksPax} peserta?",
                "Rombongan di atas {$maksPax} orang hampir selalu butuh pengaturan berbeda — transport, titik jemput, kadang harga khusus. Alur checkout normal tidak bisa mengakomodasi itu dengan jujur, jadi kami arahkan ke private trip lewat admin. Batas ini berlaku walau kursi tersisa masih banyak.",
            ],
            [
                'Kenapa saya diminta NIK di sebagian trip, tapi tidak di trip lain?',
                'Syarat identitas mengikuti kategori trip. Pendakian dan trip domestik tertentu wajib NIK karena penyelenggara memakainya untuk perizinan dan pendataan pos pendakian. Trip keliling kota atau aktivitas harian tidak memerlukannya, jadi tidak kami minta.',
            ],
        ],
        'Pembayaran' => [
            [
                'Kenapa nominal yang harus saya bayar angkanya "ganjil"?',
                'Beberapa digit terakhir adalah kode unik pemesanan Anda. Pembayaran diverifikasi manual oleh admin, dan digit itulah yang membuat satu transfer bisa dipastikan milik pemesanan yang mana. Transfer harus persis sampai digit terakhir — kalau dibulatkan, verifikasi jadi tertahan.',
            ],
            [
                'Kenapa bukti bayar harus diunggah di website, bukan dikirim lewat WhatsApp saja?',
                'Karena bukti yang masuk lewat website langsung menempel ke pemesanan Anda, sehingga begitu admin menyetujui, tiket terbit otomatis. Kalau lewat WhatsApp, ada langkah manual tambahan yang justru memperlambat dan gampang tertukar. Tombol WhatsApp tetap ada, tapi fungsinya hanya memberi tahu admin bahwa ada bukti baru.',
            ],
            [
                'Berapa lama pembayaran saya diverifikasi?',
                'Verifikasi dilakukan manual oleh admin, jadi bergantung jam kerja. Setelah disetujui, tiket dan QR terbit otomatis tanpa Anda perlu meminta. Kalau ditolak, alasannya muncul di halaman pemesanan Anda dan Anda bisa mengunggah ulang bukti yang benar.',
            ],
            [
                'Bukti bayar saya ditolak. Uang saya bagaimana?',
                'Penolakan bukti bayar berarti pembayarannya belum bisa dicocokkan — misalnya nominalnya tidak sama persis, gambarnya tidak terbaca, atau bukti yang sama sudah dipakai di pemesanan lain. Alasan spesifiknya tertulis di halaman pemesanan. Kalau dana Anda benar-benar sudah masuk, hubungi admin dengan menyertakan kode pemesanan.',
            ],
        ],
        'Tiket & keberangkatan' => [
            [
                'Di mana e-tiket saya?',
                'Di menu "Booking Saya" setelah masuk. Tiap peserta mendapat satu tiket berisi QR sendiri. Tiket bisa dicetak atau cukup ditunjukkan dari layar HP saat hari-H.',
            ],
            [
                'QR tiket saya bisa dipakai ulang atau dipindahkan ke orang lain?',
                'Tidak. Satu QR hanya sah sekali pakai dan sudah terikat ke nama peserta yang didaftarkan. Setelah dipindai saat check-in, QR yang sama akan ditolak. Kalau ada perubahan nama peserta, hubungi admin sebelum hari-H, jangan menyerahkan tiket ke orang lain.',
            ],
            [
                'HP saya hilang atau tiketnya terhapus. Bagaimana?',
                'Tiket tersimpan di akun Anda, bukan di HP. Masuk kembali dari perangkat mana pun dan buka "Booking Saya" — tiketnya masih di sana.',
            ],
        ],
        'Pembatalan' => [
            [
                'Trip yang saya ikuti dibatalkan penyelenggara. Bagaimana?',
                'Dana Anda dikembalikan penuh tanpa perlu mengajukan permintaan. Rinciannya ada di Syarat & Ketentuan bagian kebijakan pengembalian dana.',
            ],
            [
                'Saya yang batal ikut. Bisa refund?',
                'Bisa sebagian, tergantung seberapa dekat dengan tanggal keberangkatan — dan tidak ada pengembalian dana untuk pembatalan mendadak, karena biaya operasional sudah terpakai. Rincian bertingkatnya ada di Syarat & Ketentuan.',
            ],
        ],
    ];
@endphp

<x-legal-page
    eyebrow="Bantuan"
    title="Pertanyaan yang sering muncul"
    intro="Jawaban ringkas untuk hal yang paling sering ditanyakan sebelum dan sesudah memesan.">

    @foreach ($kelompok as $judul => $daftar)
        <section>
            <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-teal-900">{{ $judul }}</h2>

            <div x-data="{ terbuka: null }" class="mt-4 divide-y divide-mist-200 border-y border-mist-200">
                @foreach ($daftar as $indeks => [$tanya, $jawab])
                    @php($kunci = Str::slug($judul).'-'.$indeks)
                    <div>
                        <h3>
                            <button type="button"
                                    @click="terbuka = (terbuka === '{{ $kunci }}' ? null : '{{ $kunci }}')"
                                    class="flex w-full items-center justify-between gap-4 py-4 text-left"
                                    :aria-expanded="terbuka === '{{ $kunci }}'">
                                <span class="font-medium text-teal-900">{{ $tanya }}</span>
                                <span aria-hidden="true" class="shrink-0 text-teal-500"
                                      x-text="terbuka === '{{ $kunci }}' ? '−' : '+'">+</span>
                            </button>
                        </h3>
                        <div x-show="terbuka === '{{ $kunci }}'" x-transition.opacity x-cloak class="pb-5">
                            <p class="text-sm leading-relaxed text-teal-700">{{ $jawab }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</x-legal-page>
