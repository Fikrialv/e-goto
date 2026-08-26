# E-GOTO — Guide (Restart, versi final)

Dokumen ini menggantikan seluruh `GUIDE.md` versi sebelumnya (termasuk versi lama sebelum restart). Ini sumber kebenaran tunggal — hasil diskusi ulang penuh sebelum eksekusi dimulai.

## Tujuan produk

Memudahkan orang memesan tiket **open trip** — dari cari trip sampai punya e-tiket sah di tangan — dan berkembang jadi **wadah marketplace** yang didukung banyak mitra/vendor (E-GOTO supported by mitra/vendor), dengan customer yang dimanjakan (login mudah, profil lengkap, rating, promo) dan mitra yang punya alat kerja sendiri.

## Platform: WEBSITE responsive, bukan aplikasi native

Diakses lewat browser di semua perangkat — HP, tablet, laptop, desktop. Pendekatan desain: mobile-first, wajib responsive ke semua breakpoint (mobile <640px, tablet 640–1024px, desktop >1024px). Pola navigasi web standar, bukan ala app native.

## Stack

Laravel + Filament (panel admin/vendor) + Blade + Alpine.js (customer). Dieksekusi dengan **Claude Code CLI** — file instruksi wajib bernama `CLAUDE.md` di root repo (bukan `AGENTS.md`, itu untuk Codex CLI).

## Role & Akses

Tiga role: **Customer, Vendor/Mitra, Admin**. CS digabung ke Admin untuk sekarang (WA manual) — dipisah nanti kalau volume booking sudah butuh orang kedua.

**Catatan keamanan:** akun demo hanya untuk uji coba lokal. Sebelum ada user asli, password admin production wajib diganti kuat & unik.

## Kategori Trip

Domestik (NIK), Internasional (paspor — ditutup sementara), Pendakian (wajib NIK), Pantai, Keliling Kota, Aktivitas.

---

## ALUR UTAMA — Tujuan Awal sampai Akhir

### Alur Customer (browsing publik → login saat mau transaksi)

```
Buka web (TANPA perlu login) → Lihat homepage: Trip Populer, Jadwal Terdekat,
browse per kategori → Buka detail trip (masih tanpa login) →
Klik "Booking Sekarang" → BARU diminta login/sign up
  (isi manual ATAU Google, cepat & tidak kaku) →
Lengkapi profil (kalau baru daftar) →
Kembali otomatis ke halaman booking trip yang tadi dipilih →
Isi data peserta (leader+peserta lain, NIK domestik/pendakian, paspor internasional) →
[Opsional] pakai voucher/promo/combo → total otomatis terpotong →
Bayar QRIS (nominal unik + kode booking wajib di catatan transfer) →
Upload bukti bayar di website → [sistem auto-kirim notif WA ke admin] →
Status booking: "menunggu verifikasi" →
Admin approve → tiket + QR branded E-GOTO otomatis terbit →
Customer lihat e-tiket di profil "Booking Saya" →
Setelah trip selesai → beri rating & komentar →
[Kalau reject] lihat alasan di app → bisa upload ulang bukti bayar
```

**Prinsip penting:** browsing tidak boleh ada hambatan sama sekali. Login hanya jadi gerbang tepat sebelum transaksi (booking), bukan syarat masuk website.

### Alur Admin

```
Login → Dashboard (badge notifikasi pembayaran pending) →
Kelola trip (CRUD + kategori + harga bertingkat) →
Lihat pembayaran pending → bukti bayar vs nominal seharusnya
  ditampilkan berdampingan (flag otomatis kalau bukti terindikasi duplikat) →
Approve (+auto-generate tiket QR) / Reject (+alasan wajib) →
Kelola pengajuan mitra baru (onboarding — lihat alur mitra di bawah) →
Approve/tolak trip yang diajukan mitra
```

### Alur Vendor/Mitra — Onboarding (baru gabung E-GOTO)

```
Calon mitra baca halaman/blog "Jadi Mitra E-GOTO" (kriteria, benefit) →
Isi form pengajuan (profil bisnis, dokumen) →
Admin lihat pengajuan → jadwalkan meeting →
Review dokumen & kriteria → Admin approve/tolak →
[Approve] mitra dapat akun vendor resmi
```

### Alur Vendor/Mitra — Sudah Aktif

```
Login → Ajukan trip baru (foto, harga, kuota, jadwal) →
Admin review & approve/tolak → Trip tayang di web →
Booking masuk → Notifikasi booking baru →
Lihat daftar trip sedang/sudah berjalan + jumlah peserta →
Hari-H: input token QR customer → validasi signature →
  Valid & belum dipakai → check-in sukses
  Invalid/sudah dipakai → ditolak jelas (anti pakai-ganda) →
Setelah trip selesai → lihat rating dari customer
```

---

## Alur Pembayaran — Anti-Fraud (detail)

Bukti bayar **selalu diupload di dalam website**, tidak dipindah ke WA — WA hanya notifikasi cepat ke admin (link `wa.me` otomatis), supaya approve tetap terhubung otomatis ke proses generate tiket.

Mitigasi fraud:
1. **Nominal unik per booking** — harga asli + beberapa digit unik (mis. Rp175.000 → Rp175.017), memudahkan admin cocokkan mutasi.
2. **Kode booking wajib** di catatan transfer, ditampilkan besar di halaman pembayaran.
3. **Deteksi bukti bayar duplikat** — hash file gambar, tandai otomatis kalau dipakai di 2 booking berbeda.
4. **Batas waktu upload** — booking otomatis `expired` kalau tidak ada upload dalam waktu tertentu.
5. **Tampilan cocok-cocokan di admin** — nominal seharusnya vs bukti bayar berdampingan, besar & jelas.

QRIS statis (GoPay Merchant) dipakai sekarang — gratis, tidak perlu badan usaha. Upgrade ke Midtrans QRIS dinamis (auto-verifikasi webhook) ditunda sampai ada NIB/UMKM dan volume lebih besar.

---

## Batas Peserta per Booking — maksimal 12 orang

Satu booking maksimal **12 peserta**. Ini **cap keras**: berlaku walau sisa kuota jadwal jauh lebih besar. Bukan batas teknis, tapi keputusan operasional — rombongan di atas 12 punya kebutuhan berbeda (transport, akomodasi, negosiasi harga, titik jemput) yang tidak layak lewat alur checkout normal.

Rombongan lebih dari 12 diarahkan ke **Request Private Trip** (V1.5/D12). Selama D12 belum jalan, arahkan ke link `wa.me` admin dengan pesan terisi otomatis.

Penegakan **wajib di server** (Form Request + Action), bukan cuma di form — batas yang hanya hidup di UI bisa dilewati siapa pun yang mengirim POST sendiri. Cap ini tidak menggantikan pengecekan kuota: dua-duanya berlaku, yang lebih kecil menang.

---

## Kebijakan Refund — dua tingkat, batas tunggal di H-7

Besaran pengembalian ditentukan **siapa yang membatalkan** dan **seberapa dekat dengan tanggal keberangkatan** — biaya operasional penyelenggara sudah terpakai lebih dulu saat hari-H mendekat.

| Situasi | Pengembalian | Catatan |
|---|---|---|
| **(a)** Trip dibatalkan penyelenggara, atau kuota minimum tidak tercapai | **Customer pilih 1 dari 3 opsi** | Lihat "Opsi customer" di bawah — bukan lagi otomatis refund |
| **(b)** Peserta membatalkan **lebih dari H-7** dari tanggal trip | **50%**, dikurangi biaya administrasi flat **Rp25.000** | Angka masih `[SEMENTARA — validasi sebelum publish]` |
| **(c)** Peserta membatalkan **H-7 ke bawah** (H-7 sampai hari-H) | **Tidak ada** | Ditawarkan reschedule ke jadwal lain trip yang sama, **kalau vendor punya slot** |
| **(d)** Force majeure — bencana, larangan otoritas, cuaca ekstrem yang membuat perjalanan tidak aman | **Customer pilih 1 dari 3 opsi** | Sama seperti (a) — tetap wajib salah satu, tidak boleh hangus |

**Revisi 2026-08-24 — (a) dan (d) jadi pilihan customer, bukan otomatis.** Trip batal dari sisi penyelenggara atau force majeure tidak lagi otomatis diproses refund: customer berhak memilih salah satu dari tiga opsi:
(i) Refund 100%.
(ii) Pindah ke trip/jadwal lain — kalau harga trip pengganti berbeda, selisihnya dibicarakan case-by-case dengan admin untuk sekarang; belum ada aturan otomatis.
(iii) Masuk waitlist untuk jadwal trip yang sama berikutnya.

Untuk V1, seluruh proses ini **tetap manual lewat admin**: customer menyampaikan pilihannya (WA atau form sederhana), admin yang mengeksekusi — proses refund, buatkan booking baru untuk trip pengganti, atau catat manual di waitlist. Bukan alur self-service otomatis dulu.

Batas (b) dan (c) sengaja berdempet di angka yang sama: "lebih dari H-7" dan "H-7 ke bawah" bersambung tanpa celah dan tanpa tumpang tindih.

**Revisi 2026-08-14 (kedua).** Versi pertama memakai tingkat ">H-7" dan "H-3 s/d H-1", menyisakan H-6, H-5, dan H-4 tanpa aturan sama sekali — lubang itu sempat ditambal kalimat "diputuskan kasus per kasus". Untuk dokumen yang mengikat, tambalan seperti itu tidak bisa diterima: artinya customer baru bisa tahu haknya setelah bertanya, dan admin memutuskan tanpa pegangan. Sekarang dua tingkat saja dengan satu batas.

Pengembalian dana dikirim ke rekening pengirim yang sama dengan pembayaran awal.

---

## Keputusan Disetujui — Pelaksanaan Ditunda

Bagian ini berbeda dari Backlog. Backlog = belum diputuskan, mungkin tidak pernah dikerjakan. Bagian ini = **sudah disetujui pemilik project, pasti dikerjakan**, tapi waktunya sengaja ditunda.

### Dua opsi pembayaran: lunas ATAU DP + pelunasan — *disetujui 2026-08-14, mulai dikerjakan setelah V1 live*

Ke depan customer bisa memilih bayar lunas di muka **atau** DP dulu lalu pelunasan sebelum hari-H. **Jangan dibangun sekarang.**

Alasan penundaan: skema DP menambah state pembayaran baru (`partially_paid`), aturan tenggat pelunasan, konsekuensi kalau pelunasan lewat tenggat (hangus / refund sebagian / kuota dilepas), dan tiket yang hanya boleh terbit setelah lunas. Semua itu perlu diuji dengan pola pemakaian nyata dari V1, bukan ditebak sekarang.

Yang boleh dilakukan sekarang: **tidak mengunci** desain yang menghalangi. Jangan pernah berasumsi 1 booking = 1 pembayaran; `payments` sudah tabel terpisah dari `bookings` — pertahankan relasi itu.

Yang harus ditetapkan sebelum eksekusi: persentase DP, tenggat pelunasan (mis. H-7), dan konsekuensi kalau lewat tenggat.

---

## Login & Profil Customer

- Sign up/login manual (email+password) **atau** via Google (Laravel Socialite) — dibuat interaktif, cepat, tidak kaku. **Login Facebook dibatalkan (keputusan 2026-08-05)** — Google saja, supaya tidak menunggu App Review Meta.
- **Catatan waktu:** pendaftaran aplikasi OAuth ke Google Cloud Console dikerjakan manual oleh pemilik project (`docs/oauth-setup-guide.md`). Tanpa kredensial, kode Socialite tetap dibangun penuh dan tombolnya disembunyikan lewat config — bukan alasan menunda D3.
- Profil customer: data diri, foto, riwayat booking, e-tiket tersimpan, beri rating setelah trip selesai, teaser "Jadi Mitra E-GOTO?" (link ke halaman onboarding mitra).
- Login wajib **hanya saat mau booking**, bukan syarat browsing.

---

## FASE PENGERJAAN

### FASE V1 — Fondasi Platform (6-7 hari)

- Browsing publik tanpa login (homepage, kategori, detail trip, Trip Populer, Jadwal Terdekat)
- Login/sign up manual + Google, gerbang tepat sebelum booking
- Profil customer dasar + riwayat booking
- Booking + field adaptif NIK/paspor
- Pembayaran anti-fraud lengkap (nominal unik, kode booking, deteksi hash duplikat, expired otomatis)
- Admin: CRUD trip, verifikasi pembayaran, approve/reject
- Tiket + QR branded E-GOTO otomatis terbit
- Vendor: check-in manual + anti pakai-ganda
- PII (NIK, paspor) terenkripsi di database
- Halaman legal & bantuan: FAQ, Syarat & Ketentuan (termasuk kebijakan refund dua tingkat), Kebijakan Privasi
- Penyempurnaan sebelum rilis (D7.6): PWA installable, transparansi verifikasi pembayaran, filter level fisik, reminder H-1, checklist perlengkapan
- Backup Hostinger terverifikasi aktif
- Fondasi extensible: interface `PaymentGateway`/`MessagingService`, enum status konsisten, komponen Blade reusable, middleware role siap diperluas

**Output:** website jalan penuh untuk loop transaksi dasar, aman, siap diuji user asli terbatas.

#### Penyempurnaan Sebelum Rilis (D7.6)

Enam hal yang tidak menambah alur baru, tapi memperbaiki pengalaman di alur yang sudah ada. Disepakati 2026-08-14, dikerjakan setelah D7.5 dan sebelum D8.

- **PWA installable** — website bisa dipasang ke home screen lewat "Add to Home Screen", lengkap dengan ikon sendiri, tanpa perlu aplikasi native. Ini justru alasan awal memilih website responsive; tanpa manifest, keunggulan itu tidak pernah benar-benar sampai ke tangan pengguna.
- **Badge "Verified Payment" + estimasi waktu verifikasi** — customer bisa melihat pembayarannya sudah terverifikasi, dan selama masih menunggu, tahu perkiraan lamanya. Verifikasi V1 dikerjakan manusia, jadi jeda diam tanpa keterangan adalah titik paling bikin was-was di seluruh alur. Halaman pembayaran juga menyediakan tombol unduh gambar QRIS, supaya customer bisa membayar dari aplikasi mobile banking terpisah tanpa harus scan langsung dari layar HP yang sama.
- **Filter level fisik / cocok untuk pemula** — halaman kategori bisa disaring berdasarkan berat ringannya trip: **pemula, menengah, atau lanjutan**. Levelnya melekat pada masing-masing trip, bukan pada kategorinya, karena dua trip pendakian dalam kategori yang sama bisa sangat berbeda beratnya. Paling penting untuk Pendakian, tempat salah pilih trip bukan sekadar kecewa, tapi berbahaya.
- **Reminder H-1** — admin punya antrean berisi booking yang tripnya berangkat besok, tiap baris menyiapkan pesan WhatsApp berisi itinerary ringkas dan checklist perlengkapan. Mengurangi peserta yang datang tanpa persiapan, memakai jalur `wa.me` yang sudah ada.
- **Checklist perlengkapan per kategori** — daftar bawaan yang berbeda tiap kategori (pendakian jelas lain dari pantai), tampil di detail trip dan e-tiket. Berbeda dari level fisik di atas, daftar ini cukup ditulis sekali per kategori karena bawaannya relatif sama antar trip sejenis. Salah bawa perlengkapan adalah keluhan yang paling bisa dicegah sejak awal.
- **Konfirmasi metode pembayaran sebelum lanjut** — sebelum kode QRIS dan form upload bukti tampil, customer melihat panel ringkas berisi alur unggah bukti → verifikasi manual admin → tiket terbit lengkap estimasi waktunya, peringatan eksplisit bahwa verifikasi dilakukan manusia (bukan instan), tautan langsung ke Kebijakan Privasi & Syarat-Ketentuan, dan tombol konfirmasi sebelum lanjut. Titik paling rawan salah paham di alur pembayaran — tanpa ini customer bisa mengira QRIS di sini otomatis seperti e-wallet pada umumnya.

### FASE V1.5 — Marketplace & Interaktivitas (5-7 hari, lanjut langsung)

- Onboarding mitra/vendor baru (blog info, form pengajuan, kriteria, jadwal meeting, review dokumen, approval admin) — ini yang dimaksud "affiliate": kerja sama mitra, BUKAN referral berkomisi customer
- Loop mitra aktif: ajukan trip → admin approve/tolak → tayang
- Voucher, promo, combo package di checkout
- Rating & komentar customer setelah trip selesai
- Request private trip via WA ke admin/CS
- Widget chat pihak ketiga (Tawk.to atau Crisp) di semua halaman customer, dengan tombol pindah ke WhatsApp — **bukan** sistem chat sendiri, dan **hanya** untuk tanya-jawab umum/CS. Dipindahkan dari "Backlog — Menunggu Giliran" ke D12 pada 2026-08-27; batasan lengkap di PLAN.md §7 blok D12
- Web Push notification **opt-in** untuk pembayaran terverifikasi & pengingat H-1 — menumpang `manifest.json` + service worker yang sudah ada dari D7.6, izin browser hanya diminta setelah customer menekan tombolnya sendiri. Dipindahkan dari "Backlog — Menunggu Giliran" ke D12 pada 2026-08-27; detail di PLAN.md §7 blok D12
- Vendor: daftar trip sedang/sudah berjalan + jumlah peserta
- Teaser "Jadi Mitra E-GOTO?" tampil di homepage

**Output:** E-GOTO jadi wadah multi-mitra, customer dapat promo & bisa rating, ada jalur private trip.

### FASE V2 — Menyusul terpisah (belum bisa diestimasi pasti)

- Dashboard pemasukan vendor interaktif (grafik, breakdown per trip) — **butuh Anda tetapkan dulu**: skema komisi platform 3-10% dari harga normal mitra, diterapkan ke perhitungan seperti apa
- Modul lanjutan lain sesuai kebutuhan nyata dari feedback V1.5

**Total estimasi V1 + V1.5: 11-14 hari kerja.** V2 menyusul setelah V1.5 terbukti stabil dipakai, bukan dipaksa masuk jendela waktu yang sama.

**Catatan kriteria deploy.** Deploy Hostinger sengaja ditunda sampai V1 + V1.5 (D0-D13) selesai — **bukan** sampai seluruh "Backlog — Menunggu Giliran" atau "Model bisnis baru" selesai, karena kedua daftar itu memang open-ended dan tidak pernah "tuntas" secara desain. Kalau nanti pemilik project mau ubah kriteria ini (misal deploy lebih awal atau justru tunggu lebih lama), itu keputusan baru yang perlu ditulis ulang di sini, bukan diasumsikan.

---

## Backlog — Menunggu Giliran

Bagian ini **bukan** daftar penolakan. Isinya ide yang **konsepnya sudah disetujui** tapi belum masuk fase mana pun — belum dijadwalkan, bukan belum diputuskan. Bandingkan dengan "Backlog — Ditunda Sadar" di bawah, yang isinya hal-hal yang sengaja **tidak** dibangun.

Aturan pakai: item di sini tidak boleh dikerjakan begitu saja. Pemilik project memindahkannya ke fase (D-sekian) lebih dulu, baru masuk antrean kerja.

### Fitur customer

- **Wishlist** — customer menyimpan trip yang diminati untuk dilihat lagi nanti, tanpa harus memesan saat itu juga.
- **Refund & reschedule self-service** — pengajuan lewat website mengikuti aturan di bagian Kebijakan Refund, bukan lewat percakapan WA yang harus diterjemahkan admin satu per satu.
- **Rating per vendor** — nilai agregat mitra lintas seluruh trip yang mereka jalankan; berbeda dari rating per-trip di D11 yang hanya menilai satu perjalanan.
- **Trip comparison** — membandingkan beberapa trip berdampingan (harga, durasi, level fisik, yang termasuk) tanpa membuka banyak tab.
- **Export itinerary ke kalender (`.ics`)** — jadwal trip masuk ke kalender HP customer, supaya tanggal keberangkatan tidak terlewat.
- **Waitlist trip** — dua skenario: (1) trip kuota penuh sejak awal, customer mengantre dan diberi tahu kalau ada pembatalan yang melepas kursi; (2) trip dibatalkan penyelenggara/force majeure (opsi (iii) di Kebijakan Refund), customer pilih menunggu jadwal trip yang sama berikutnya alih-alih refund atau pindah trip lain.
- **Ganti trip otomatis saat pembatalan** — customer pilih sendiri trip/jadwal pengganti lewat web saat tripnya dibatalkan (opsi (ii) di Kebijakan Refund), sistem pindahkan booking otomatis tanpa lewat admin. Berbeda dari "Refund & reschedule self-service" di atas (itu untuk customer membatalkan sendiri sesuai (b)/(c); ini untuk trip yang dibatalkan penyelenggara sesuai (a)/(d)). **Dependency eksplisit:** butuh Waitlist trip selesai dibangun dulu (mekanisme mencocokkan customer ke slot trip lain serupa), dan butuh aturan bisnis baru soal selisih harga (siapa menanggung kalau trip pengganti lebih mahal/murah) ditetapkan dulu — bukan sekadar UI, ada keputusan bisnis yang masih menggantung.
- **SEO & OpenGraph halaman trip** — tautan trip yang dibagikan ke WhatsApp atau media sosial menampilkan gambar dan judul yang rapi, bukan teks polos.

### Fitur mitra/vendor

- **Broadcast info ke peserta** — vendor mengirim satu pengumuman ke seluruh peserta yang sudah memesan trip tertentu (perubahan titik kumpul, cuaca, dan sejenisnya).
- **Notifikasi kuota hampir penuh** — vendor diberi tahu otomatis saat kursi tersisa menipis, supaya sempat menyiapkan jadwal tambahan.
- **Kalender ketersediaan trip** — tampilan visual bulanan jadwal vendor, bukan tabel baris demi baris.
- **Badge "Mitra Terpercaya"** — penanda reputasi berbasis rekam jejak (rating, jumlah trip selesai, tingkat pembatalan), bukan diberikan manual per selera.
- **Vendor mengajukan reschedule sendiri** — vendor mengusulkan pemindahan tanggal lewat panelnya, **tetap butuh persetujuan admin** sebelum berlaku ke peserta.
- **Unggah dokumentasi & testimoni pasca-trip** — vendor menaruh foto dan cerita perjalanan yang sudah selesai sebagai bukti nyata untuk calon peserta berikutnya.
- **Paket dokumentasi foto/video** — layanan tambahan berbayar yang dipilih customer saat memesan, memakai mekanisme `trip_options` yang sudah dirancang di V1.5.

### Operasional & admin

- **Pemecahan permission admin** — pembagian yang dimaksud: pemilik project sendiri memegang **Manajer Trip & Mitra** (CRUD trip, destinasi, kategori, mitra), admin kedua memegang **Verifikator Pembayaran digabung CS** (balas WA, approve/reject pembayaran) — cukup **2 pembagian tugas**, bukan 3 role terpisah. Ditegakkan lewat **permission granular** di Filament, bukan menambah role baru di enum; berbeda dari "role CS terpisah" di daftar Ditunda Sadar yang memang menambah role.
- **Chatbot FAQ-only** — menjawab pertanyaan umum memakai API AI pihak ketiga, **read-only**, ada toggle aktif/nonaktif, dan mengeskalasi pertanyaan sulit ke WA admin. **Dilarang keras** menyentuh approve/reject pembayaran atau penerbitan tiket — jalur uang dan tiket tetap milik manusia. Ini **bukan** pembalikan penolakan "AI Assistant" di daftar Ditunda Sadar: yang ditolak itu asisten serba bisa dengan akses luas, yang ini kotak tanya-jawab sempit. **Prinsip permanen, bukan batasan sementara:** approve/reject pembayaran dan penerbitan tiket wajib tetap lewat layar verifikasi Filament (bukti-vs-nominal berdampingan, banner duplikat) yang sudah dibangun di D5 — berlaku tetap walau chatbot ini nanti naik level ke WA Business API, tidak boleh dilewati lewat jalur chat manapun.
- **Export manifest PDF untuk trip pendakian** — daftar peserta beserta kontak darurat, siap cetak atau dikirim ke basecamp, karena pos pendakian memang memintanya dalam bentuk kertas.
- **Diskon otomatis berjenjang per jumlah pax** — **bukan pekerjaan baru dari nol**: harga bertingkat (`trip_prices`, mis. Reguler vs Rombongan) sudah berjalan sejak D2. Yang dimaksud di sini penyempurnaannya — lebih dari dua tingkat, atau potongan dihitung dari formula alih-alih tabel harga yang diisi manual tiap jadwal.

### Pembayaran & bisnis

- **Split payment / patungan** — tiap peserta membayar porsinya sendiri, tidak menumpuk pada satu ketua rombongan yang harus menalangi lebih dulu.
- **Transfer bank sebagai metode pembayaran kedua** — memakai pola yang sama persis dengan QRIS (nominal unik, upload bukti, verifikasi manual admin), bedanya cuma instruksi yang ditampilkan: nomor rekening, bukan kode QR. Numpang interface `PaymentGateway` yang sudah dirancang sejak D1 — tidak perlu bongkar fondasi pembayaran yang sudah ada.
- **Cek status check-in tanpa login** — khusus kategori pendakian, keluarga di rumah bisa memastikan pesertanya sudah check-in lewat tautan terbatas, tanpa perlu punya akun.
- **Theme Preset Switcher** — admin memilih tema musiman dari preset yang **sudah didesain** (warna aksen terbatas + banner + jadwal aktif otomatis). Ini **bukan** pembalikan penolakan "Web Builder": scope-nya sengaja sempit — admin **tidak** bisa mengubah background, layout, susunan tab, atau menambah halaman secara bebas. Yang ditolak itu kebebasan menyusun halaman; yang ini memilih dari pilihan yang sudah dikurasi.

### Model bisnis baru — dievaluasi terpisah

Kelompok ini **bukan** fitur tambahan, melainkan arah bisnis. Masing-masing butuh evaluasi sendiri karena menyangkut partner, perizinan, atau sumber pendapatan baru — bukan sekadar menambah layar di aplikasi yang sudah ada.

- **Galeri foto komunitas pasca-trip** — kumpulan foto peserta yang jadi bukti sosial sekaligus alasan orang kembali membuka website.
- **Badge & gamifikasi non-tunai** — penanda pencapaian peserta (jumlah puncak, jumlah trip) tanpa iming-iming uang.
- **Asuransi perjalanan add-on** — perlindungan opsional saat memesan; butuh kerja sama dengan penyedia asuransi.
- **Profil guide/pemandu personal** — halaman pemandu dengan pengalaman dan rekam jejaknya, karena orang sering memilih trip karena pemandunya.
- **Tag "eco-conscious / dukung lokal"** — penanda trip yang menjalankan praktik ramah lingkungan atau menggerakkan ekonomi setempat.
- **Trip request & crowdfunding komunitas** — calon peserta mengusulkan tujuan, trip berjalan setelah cukup orang bergabung.
- **Program corporate B2B outing** — jalur khusus perusahaan yang memesan untuk karyawan, dengan kebutuhan dokumen dan penagihan berbeda.
- **Gear rental** — penyewaan perlengkapan saat memesan, untuk peserta yang belum punya alat sendiri.
- **Freelance guide marketplace** — pemandu perorangan bergabung sebagai penyedia, bukan hanya lewat perusahaan mitra.
- **Homestay komunitas lokal** — penginapan warga sebagai bagian paket perjalanan.
- **Marketplace oleh-oleh UMKM** — produk lokal dijual menyambung ke perjalanan yang sudah diikuti peserta.
- **BNPL travel (cicilan)** — pembayaran bertahap lewat penyedia pihak ketiga; berbeda dari skema DP internal yang sudah disetujui di bagian "Keputusan Disetujui — Pelaksanaan Ditunda".
- **Live tracking kendaraan grup** — posisi kendaraan rombongan lewat GPS berbasis SIM. **Berbeda dari GPS satelit per pendaki** yang tetap ada di daftar Ditunda Sadar: yang ini satu perangkat per kendaraan, bukan alat satelit per orang di jalur tanpa sinyal.
- **Program "E-GOTO Kampus Jatim"** — jalur khusus mahasiswa dengan verifikasi email kampus dan leaderboard antar kampus; **validasi manual B2B dulu** sebelum membangun versi digitalnya.

---

## Backlog — Ditunda Sadar, Bukan Dihapus

Berbeda dari "Menunggu Giliran" di atas, isi bagian ini sengaja **tidak** dibangun — bukan sekadar belum dijadwalkan.

Dashboard analitik penuh, Ledger/Buku Kas, AI Assistant (asisten serba bisa berakses luas — beda dari chatbot FAQ-only di Menunggu Giliran), Web Builder (kebebasan menyusun halaman — beda dari Theme Preset Switcher di Menunggu Giliran), Ticket Designer custom, payment gateway otomatis (Midtrans — nanti setelah NIB/UMKM), WhatsApp Business API resmi (link `wa.me` manual cukup dulu — alasan lengkap di catatan bawah), GPS satelit per pendaki (perangkat satelit untuk jalur tanpa sinyal — beda dari live tracking kendaraan grup di Menunggu Giliran), audit log lengkap, 2FA staf, multi-bahasa penuh, dark mode, role CS terpisah dari Admin, sistem affiliate referral berkomisi (customer-refer-customer — **tidak ada rencana dibangun**, sudah dikonfirmasi tidak diperlukan).

*Catatan 2026-08-14, diperbarui 2026-08-27: "push notification" dikeluarkan dari daftar ini — statusnya naik jadi Web Push di "Menunggu Giliran", lalu 2026-08-27 dijadwalkan resmi ke **D12**. Satu fitur tidak boleh berada di dua daftar yang berlawanan.*

**Catatan 2026-08-24 (WhatsApp Business API):** WhatsApp Business Platform/Cloud API — beda dari aplikasi WhatsApp Business gratis yang sudah dipakai sekarang — mewajibkan **Meta Business Verification**, proses approval sejenis dengan App Review yang membuat Login Facebook dibatalkan (lihat CHANGELOG 2026-08-05). Juga **berbayar per kategori percakapan**, bukan biaya tetap. Chatbot FAQ-only yang direncanakan di "Menunggu Giliran" memakai widget web pihak ketiga (Tawk.to/Crisp) — sama sekali tidak butuh WA API ini. Evaluasi ulang baru masuk akal setelah volume percakapan riil terukur dari V1/V1.5, bukan diputuskan sejak awal.

---

## Data Uji Coba (Seeder)

10-12 trip demo, variatif: minimal 2 per kategori aktif, variasi harga bertingkat, variasi tanggal jadwal (untuk uji "Jadwal Terdekat"), minimal 1 trip kuota hampir penuh, minimal 1 kuota penuh (state disabled/waitlist). Internasional: status masih perlu dikonfirmasi — aktifkan dummy untuk uji coba atau tetap tutup.

## Design System — status: **diputuskan ulang (2026-08-14, final)**

Menggantikan arah "editorial hangat (sand/forest/terracotta)" yang diputuskan 2026-08-05. Alasan penggantian: identitas visual mengikuti **logo E-GOTO (teal)**, bukan palet netral yang dipilih sebelum logo jadi pegangan.

| Peran | Warna | Catatan |
|---|---|---|
| Permukaan / latar | **mist** (`mist-50` … `mist-400`) | Netral bertint teal — latar halaman, kartu, panel filter, garis pemisah |
| Teks & aksen | **teal** (`teal-200` … `teal-900`) | Heading, body, tautan, badge sukses, kursi tersedia |
| CTA & urgensi | **amber** (`amber-500/600/700`) | **Hanya** untuk aksi utama, state pending, dan "hampir habis" — tidak untuk dekorasi, supaya satu-satunya warna hangat di layar selalu berarti "klik ini" atau "perhatikan ini" |

Tiga warna inti diambil langsung dari logo:

| Token | Hex | Peran |
|---|---|---|
| `teal-400` | `#199FA5` (teal-light) | Aksen terang, ikon, border aktif |
| `teal-600` | `#077C82` (teal-primary) | Warna identitas utama, tautan, badge sukses |
| `teal-900` | `#044D4A` (teal-dark) | Heading & teks pekat |

Turunan lain: `teal-50 #EFF8F9`, `teal-200 #B6DEE0`, `teal-500 #0A8A90`, `teal-700 #066165`, `teal-800 #05575A`. Permukaan `mist`: `50 #F6FAFA`, `100 #EAF3F3`, `200 #D5E6E7`, `300 #B4D2D4`, `400 #8AB6B9`. CTA `amber`: `100 #FBEBD3`, `500 #E08A1E`, `600 #A8630D`, `700 #7F4A08`.

**Catatan kontras (jangan diubah tanpa mengukur ulang):** `amber-600` sengaja dibuat lebih gelap dari amber "alami" supaya tombol solid berteks putih tetap lolos WCAG AA (4,7:1). `amber-500` yang lebih terang hanya untuk badge/ikon di atas latar terang dengan teks gelap — bukan untuk tombol berteks putih.

Tipografi: **Plus Jakarta Sans Variable** (heading/display) + **Inter Variable** (body). Keduanya di-self-host lewat Vite, bukan CDN. Fraunces (serif) tidak dipakai lagi.

**Hierarki tipografi wajib dinaikkan.** Pasangan lama serif+sans mendapat kontras gratis dari perbedaan bentuk huruf; Plus Jakarta Sans + Inter sama-sama sans, jadi kontras harus dibuat sengaja lewat berat, ukuran, dan tracking — kalau tidak, hasilnya jatuh ke kesan "SaaS landing page generik" yang dilarang di bagian Standar Desain (`CLAUDE.md` §10):

| Peran teks | Aturan |
|---|---|
| Hero | `clamp(2.5rem, 6vw, 4.25rem)`, weight 800, tracking `-0.03em`, leading 0.95 |
| Heading seksi | `text-3xl sm:text-4xl`, weight 700, tracking `-0.02em` |
| Body | Inter 400/500, `leading-relaxed`, warna `teal-800` |
| Label / eyebrow | Inter 600, `text-xs`, uppercase, tracking `0.18em` |

Warna semantik: teal = sukses/kursi tersedia, amber = pending/hampir habis, `mist-200` + strikethrough = habis/nonaktif. Merah error dipakai apa adanya dari state form.

Prinsip visual: setiap elemen (3D object, animasi, ilustrasi) harus fungsional, tidak boleh terlihat "dibuat AI". Token warna & font didefinisikan di `resources/css/app.css` (`@theme`).

## Yang Masih Perlu Dikonfirmasi

- [ ] Trip internasional: aktifkan dummy untuk uji coba, atau tetap tutup?
- [x] Design system — **final 2026-08-14**: teal (permukaan `mist` + teks/aksen `teal`) + amber khusus CTA, Plus Jakarta Sans + Inter. Hex diambil dari logo
- [ ] Angka di Syarat/Privasi masih **sementara** — refund batal >H-7 = 50% dikurangi biaya admin flat Rp25.000 (H-7 ke bawah tanpa refund), retensi NIK/paspor = akun aktif + 2 tahun. Ditandai `[SEMENTARA — validasi sebelum publish]` di halamannya, wajib divalidasi sebelum website dipublikasikan
- [ ] Skema komisi platform 3-10% mitra — diterapkan flat atau tiered per kategori? (dibutuhkan sebelum mulai V2)
- [ ] Persentase DP & tenggat pelunasan — dibutuhkan sebelum eksekusi dua-opsi-pembayaran (setelah V1 live)

---
*Dibuat ulang dari nol menggantikan seluruh versi GUIDE.md sebelumnya. Update dokumen ini tiap ada keputusan baru yang mengubah scope, supaya tetap jadi sumber kebenaran tunggal untuk Claude Code CLI.*
