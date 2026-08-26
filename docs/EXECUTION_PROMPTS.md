# E-GOTO — Prompt Eksekusi Harian (Claude Code CLI)

Urutan D0-D13 di `docs/PLAN.md` §6-8. `CLAUDE.md` di root sudah dibaca otomatis, jadi prompt di bawah singkat.

**Model:** pakai `/model opusplan` sebagai default (Opus mikir, Sonnet eksekusi — hemat token, tetap kuat di keputusan riskan).

## Ritual awal tiap sesi baru (reset ±5 jam — tempel duluan)

```
Baca docs/update.md bagian "Sesi Terakhir", lanjutkan dari situ. Kalau kosong, mulai dari item pertama belum tercentang.
```

## Template penutup (tempel di akhir tiap prompt)

```
Sebelum lapor selesai: jalankan test, perbaiki kalau merah lalu ulangi sampai hijau. Cek log/console bersih. Jawab ringkas. Update centang di update.md + isi "Sesi Terakhir" + entri CHANGELOG.md. Tutup: "Perlu diupdate:" / "Langkah selanjutnya:".
```

---

**D0-D7 sudah selesai dieksekusi (lihat `docs/update.md` baris 3).** Prompt di bawah ini arsip referensi — dipakai kalau perlu re-cek detail suatu hari, bukan untuk dijalankan ulang.

## D0 — Persiapan paralel

```
Buatkan docs/oauth-setup-guide.md: langkah bernomor detail daftar OAuth Google Cloud Console sampai dapat Client ID+Secret (saya yang eksekusi klik manual). Paralel: git init, .gitignore Laravel, branch main, commit awal. Pastikan CLAUDE.md di root (bukan docs/).

[+ template penutup]
```

Setelah dapat Client ID+Secret: `"Ini Client ID+Secret OAuth: [tempel]. Pasang ke .env + Socialite sesuai PLAN.md §2. [+ template penutup]"`

## D1 — Scaffold & fondasi

```
Sebelum mulai: pasang Laravel Boost MCP (CLAUDE.md §8) — composer require laravel/boost --dev, php artisan boost:install, claude mcp add laravel-boost -- php artisan boost:mcp. Ini supaya kode Laravel/Filament yang dihasilkan akurat ke versi terbaru, bukan tebakan.

Scaffold Laravel 12 + Filament 3, 2 panel (/admin, /vendor) sesuai PLAN.md §2-3. Migration+Model+Enum+Contract (PaymentGateway, MessagingService) semua tabel V1 §4. Middleware role. Seeder 6 kategori (internasional is_active=false). Akun admin+vendor demo.

Selesai kalau: migrate:fresh --seed bersih, login Filament admin demo bisa.
[+ template penutup]
```

## D2 — Browsing publik

```
Homepage (Trip Populer, Jadwal Terdekat, grid kategori), halaman kategori+filter, detail trip (galeri, itinerary, jadwal+kuota, harga bertingkat, CTA). Komponen Blade reusable. Responsive 3 breakpoint. WAJIB tanpa auth sama sekali di 3 halaman ini.

Selesai kalau: guest akses 3 halaman, tidak ada redirect login.
[+ template penutup]
```

## D3 — Auth & profil

```
Login/register manual + Socialite Google (kalau kredensial belum ada: kode siap, tombol disembunyikan via config flag). Lengkapi profil setelah daftar. Login gate PLAN.md §5.5: guest klik booking → session url.intended → login → redirect balik ke booking yang sama. Kerangka profil + "Booking Saya".

Selesai kalau: alur guest→booking→login→balik ke booking sama, mulus.
[+ template penutup]
```

## D4 — Booking

```
Form booking: jadwal, pax, leader+peserta. Field adaptif sesuai categories.id_requirement (nik/passport/none). Enkripsi id_number (cast encrypted) + id_number_hash terpisah untuk lookup. Nominal unik 3 digit (retry maks 10x→4 digit), kunci kuota (transaksi+lockForUpdate), expires_at 2 jam. Scheduled Command bookings:expire tiap 5 menit (BUKAN queue — lihat PLAN.md §2).

Test wajib: booking pendakian tanpa NIK gagal; kuota lebih dari sisa ditolak; expired otomatis kuota balik.
[+ template penutup]
```

## D5 — Pembayaran & verifikasi admin

```
Halaman pembayaran: QRIS, kode booking besar, nominal unik jelas. Upload bukti DI WEBSITE (bukan WA) + proof_hash sha256, flag is_duplicate_flagged kalau hash sama (jangan auto-reject). Tombol wa.me auto-generate notif admin. Filament: daftar pending+badge dashboard, layar verifikasi bukti vs nominal berdampingan+banner duplikat, approve/reject (reject wajib alasan).

Test wajib: hash sama di 2 booking→ter-flag; reject tanpa alasan→gagal.
[+ template penutup]
```

## D6 — Tiket, e-tiket, check-in vendor

```
Approve→generate tiket sinkron per peserta: token 32 char + HMAC-SHA256(token|booking_code|participant_id, APP_KEY), QR isi token. E-tiket branded E-GOTO di "Booking Saya". Panel vendor: input token→validasi signature+status=issued→set used (transaksi+lockForUpdate), tolak jelas (invalid vs sudah dipakai).

Test wajib: token dipakai 2x→ditolak; signature diubah→ditolak.
[+ template penutup]
```

## D7 — Hardening & rilis terbatas

```
Seeder 10-12 trip (≥2/kategori, variasi harga/tanggal/kuota). Audit: $fillable eksplisit, cek N+1, APP_DEBUG=false, tidak ada leak .env/stack trace. php artisan test (11 test PLAN.md §9) + Pint, laporkan hasil.

[+ template penutup]

Lanjut: deploy Hostinger (docs/hostinger.md), verifikasi backup BISA restore, ganti password admin/vendor demo.
```

**Setelah D7 lolos:** `"V1 selesai. Laporan akhir fase sesuai CLAUDE.md §14. [+ template penutup]"`

---

## Sesi 0 — Cek Status

```
Baca docs/update.md bagian Sesi Terakhir dan jalankan php artisan test. Laporkan: fase mana yang sudah selesai, fase mana yang berikutnya, ada test merah atau tidak. jika ada yang error langsung diperbaiki ya. jika sudah kasih tahu hasilnya!
```

## Sesi 1 — D7.7 (rencana)

```
D7.7 belum punya blok resmi di PLAN.md. Tulis blok baru "D7.7" di PLAN.md §6 (setelah D7.6, sebelum "## 7. FASE V1.5"): TripResource (hub, form lengkap field trip) + RelationManagers\SchedulesRelationManager (jadwal, dengan TripPrice bersarang lewat Repeater::relationship()) + RelationManagers\ImagesRelationManager (galeri). Termasuk migration trips.difficulty_level + Enum TripDifficulty kalau belum selesai lewat D7.6 (Sesi 2). Rujuk CategoryResource (app/Filament/Resources/CategoryResource.php, sudah selesai — lihat update.md "Gap ditemukan 2026-08-24") sebagai pola Filament Resource yang sudah tervalidasi di project ini, jangan desain ulang dari nol.

Blok wajib memuat: file yang akan disentuh, kriteria selesai, dan test wajib (minimal: admin create trip lengkap — 1 kategori + 1 jadwal + 1 tingkat harga — dari nol lewat panel; trip published muncul di halaman publik; trip draft tetap 404).

Tulis ke PLAN.md SAJA, jangan sentuh kode. Update docs/update.md bagian "Gap ditemukan 2026-08-24" untuk menandai D7.7 sudah punya rencana resmi.

[+ template penutup — ganti "jalankan test" jadi "baca ulang blok D7.7 yang baru ditulis, pastikan konsisten dengan §4/§9"]
```

## Sesi 2 — D7.6

```
Enam perbaikan pengalaman di alur yang sudah ada — TIDAK ada alur baru (PLAN.md §6 blok D7.6, deskripsi produk di GUIDE.md "Penyempurnaan Sebelum Rilis"). PENGECUALIAN MIGRATION yang sudah disetujui eksplisit: 2 kolom baru di luar tabel fase berjalan — trips.difficulty_level dan categories.gear_checklist (skema lengkap PLAN.md §4).

(a) PWA installable — public/manifest.json (ikon 192/512, display: standalone, theme_color #077C82, background_color #F6FAFA). Service worker minimal: cache aset statis Vite SAJA — JANGAN cache halaman booking/pembayaran/tiket. Registrasi di resources/js/app.js, berkas SW di root domain.

(b) Badge "Verified Payment" + estimasi waktu verifikasi + tombol download QRIS — badge pakai PaymentStatus + komponen status-badge yang SUDAH ADA (jangan tambah state baru). Estimasi dari config('booking.verification_eta'), bukan angka di Blade. Tambah tombol unduh gambar QRIS di halaman pembayaran, supaya customer bisa bayar dari app mobile banking terpisah tanpa scan langsung dari layar yang sama.

(c) Filter level fisik — kolom difficulty_level di trips (BUKAN categories) + Enum PHP native TripDifficulty (pemula/menengah/lanjutan). Opsi filter menempel ke panel filter kategori yang sudah ada, ditonjolkan di kategori Pendakian. Label UI kalimat manusia ("Cocok untuk pemula"), bukan nama enum.

(d) Reminder H-1 — halaman Filament panel admin: antrean booking confirmed yang berangkat besok, tiap baris tombol wa.me terisi otomatis. Tambah method MessagingService::remindDayBefore(Booking): string — JANGAN bikin service baru. Isi pesan: kode booking, titik kumpul, tanggal & jam, itinerary ringkas, checklist perlengkapan kategori. NIK/paspor TIDAK BOLEH ikut. Tanpa cron dan tanpa queue — tombolnya diklik admin manual.

(e) Checklist perlengkapan per kategori — kolom JSON categories.gear_checklist, cast array, DILARANG query JSON-path (whereJsonContains/->>, beda perilaku MariaDB dev vs MySQL production). CATATAN STATUS: migration + editor Filament (Repeater, ->defaultItems(0)) SUDAH DIBANGUN di CategoryResource (sesi 2026-08-24) — sisa kerja di sini cuma tampil di halaman detail trip & e-tiket, dan dipakai ulang oleh reminder H-1 di (d).

(f) Konfirmasi metode pembayaran sebelum lanjut — sebelum kode QRIS & form upload bukti tampil, panel ringkas berisi: alur unggah bukti→verifikasi manual admin→tiket terbit + estimasi waktu (config('booking.verification_eta') yang sama dipakai (b)); peringatan eksplisit QRIS ini diverifikasi manusia bukan instan; tautan langsung ke /kebijakan-privasi dan /syarat-ketentuan (bukan cuma via footer); tombol "Saya paham, lanjutkan ke pembayaran". QRIS & form upload baru dirender setelah tombol diklik, booking baru wajib konfirmasi ulang.

Test wajib (PLAN.md §9 nomor 15-20): SW aktif→halaman pembayaran tetap ambil data segar; verified→badge tampil, pending→estimasi tampil; filter pemula→hanya trip pemula, tanpa filter hasil sama; antrean H-1→booking besok saja (bukan lusa), pesan tanpa NIK/paspor; kategori dengan checklist→tampil di detail trip, tanpa checklist→tidak ada blok kosong; guest belum konfirmasi→kode QRIS tidak terlihat, setelah konfirmasi→QRIS+form tampil normal.

[+ template penutup]
```

## Sesi 3 — D7.7 (eksekusi)

```
Eksekusi blok D7.7 di PLAN.md §6 (ditulis di Sesi 1) — baca dulu bloknya sebelum mulai, jangan andalkan ingatan sesi lama. TripResource (hub) + RelationManagers\SchedulesRelationManager (jadwal, TripPrice bersarang lewat Repeater::relationship()) + RelationManagers\ImagesRelationManager (galeri). Kalau migration trips.difficulty_level/Enum TripDifficulty belum ada dari Sesi 2 (D7.6), buat sekarang sesuai skema PLAN.md §4. Pola ikuti CategoryResource (app/Filament/Resources/CategoryResource.php) yang sudah jadi.

Test wajib (dari blok D7.7 di PLAN.md, Sesi 1): admin create trip lengkap (1 kategori + 1 jadwal + 1 tingkat harga) dari nol lewat panel; trip published muncul di halaman publik; trip draft tetap 404.

[+ template penutup]
```

---

## MILESTONE V1.5

## Sesi 4 — D8

```
Halaman publik "Jadi Mitra E-GOTO" + form pengajuan+dokumen. Admin: daftar pengajuan, jadwal meeting, catatan, approve/tolak→approve buat akun vendor+akses panel.

[+ template penutup]
```

## Sesi 5 — D9

```
Vendor ajukan trip (pending_review). Admin approve/tolak (alasan wajib). Vendor lihat daftar trip berjalan/selesai+jumlah peserta, notif booking baru.

[+ template penutup]
```

## Sesi 6 — D10

```
CRUD voucher admin (percent/fixed, min_spend, kuota, masa berlaku, scope). Checkout: validasi kadaluarsa/kuota/min_spend/dobel pakai, catat voucher_usages. trip_options (Camping/Tubing/dll): CRUD vendor/admin, tampil detail+checkout, masuk subtotal.

[+ template penutup]
```

## Sesi 7 — D11 + D12

```
D11 — Rating & komentar: booking completed→rating 1-5+komentar (1/booking). Tampil di detail trip (rata-rata+daftar). Vendor lihat rating trip-nya. Admin bisa hide review abusive.

D12 — Private trip & polish: form request private trip→wa.me terisi otomatis. Teaser "Jadi Mitra E-GOTO?" di homepage+profil. Widget chat pihak ketiga (pilih SATU: Tawk.to atau Crisp, yang free-tier-nya cukup) dipasang di layout utama customer + tombol "Lanjut ke WhatsApp" di dalamnya. JANGAN bangun sistem chat sendiri (shared hosting tidak bisa jalankan proses persisten WebSocket). Widget HANYA untuk chat umum/CS — approve/reject pembayaran & penerbitan tiket tetap wajib lewat layar verifikasi Filament D5. Nama penyedia widget wajib ditambahkan ke `/kebijakan-privasi` bagian pihak ketiga penerima data. Batasan lengkap di PLAN.md §7 blok D12.

[+ template penutup]
```

## Sesi 8 — D13

```
Regression test full V1+V1.5. Cek responsive semua halaman baru. Pint. Deploy Hostinger.

[+ template penutup]
```

**Setelah Sesi 8 (D13) lolos:** `"V1.5 selesai. Laporan akhir fase sesuai CLAUDE.md §14. [+ template penutup]"`

---

## Cara pakai

Ganti `[+ template penutup]` dengan blok lengkap di atas. Berurutan **Sesi 0 → Sesi 8** (D0-D7 sudah lewat, arsip di atas — jangan dijalankan ulang). Jangan lompat ke Sesi 4 (D8) sebelum Sesi 1-3 (D7.7 rencana → D7.6 → D7.7 eksekusi) lolos.

**Kenapa D7.7 muncul dua kali (Sesi 1 dan Sesi 3).** D7.7 belum pernah direncanakan resmi di `PLAN.md` (beda dari D7.6 yang sudah lengkap kriterianya). Sesi 1 menulis rencananya ke `PLAN.md` dulu (dokumen saja), Sesi 3 baru mengeksekusinya ke kode — sesuai urutan "GUIDE/PLAN dulu baru eksekusi" di CLAUDE.md §0. Sesi 2 (D7.6) sengaja disisipkan di antara keduanya karena isinya sudah siap dieksekusi sekarang, tidak perlu menunggu rencana D7.7 selesai lebih dulu.
