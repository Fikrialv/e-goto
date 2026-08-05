# E-GOTO — Progress Checklist

Status terakhir: **D0 + D1 + D2 + D3 selesai. Browsing publik + gerbang login jalan, siap masuk D4 (booking).** Centang tiap item selesai. Dokumen ini dipakai terus-menerus untuk pengembangan selanjutnya — jangan dihapus/diganti, cukup update.

## Sesi Terakhir (WAJIB diupdate tiap akhir sesi Claude Code CLI)

- **Tanggal/waktu sesi terakhir:** 2026-08-05
- **Sedang mengerjakan:** **D3 SELESAI.** Auth customer lengkap: `/masuk` `/daftar` `/keluar` (rate limit 5/menit per email+IP), login Google lewat Socialite (tombol otomatis muncul begitu `GOOGLE_CLIENT_ID` diisi), layar "lengkapi profil" yang bisa dilewati, `/profil`, `/booking-saya`, dan `/booking/{schedule}` sebagai kerangka terkunci `auth`. Tombol booking di detail trip sekarang **per jadwal** (`route('bookings.create', $item)`), jadwal penuh tidak dapat tombol. Facebook dibuang total dari project (keputusan user 2026-08-05) — dokumen, panduan OAuth, dan komentar migration sudah dibersihkan. Verifikasi: 35 test lulus/104 assertion, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, log bersih, alur gerbang diuji lewat HTTP nyata (guest `/booking/1` → `/masuk` → login → balik ke `/booking/1`).
- **Langkah persis berikutnya:** **D4 — booking.** Isi `BookingController@create` dengan form peserta (leader + anggota), field adaptif NIK/paspor/none sesuai kategori, enkripsi `id_number` + `id_number_hash`, nominal unik 3 digit, kunci kuota lewat transaksi + `lockForUpdate()`, `expires_at` 2 jam, dan Scheduled Command `bookings:expire`. View kerangkanya sudah ada di `resources/views/pages/booking-create.blade.php` (blok komentar menandai persis di mana form masuk). Test yang tidak boleh berubah merah: `PublicBrowsingTest`, `LoginGateTest`.
- **Cara kerja di worktree (kalau sesi berikutnya juga pakai worktree):** worktree baru tidak punya `vendor/`, `node_modules/`, `.env` (semuanya gitignored). Salin `.env` dari checkout utama, lalu jalankan `composer install` **nyata** di worktree — `vendor/` jangan di-junction ke checkout utama, karena Pest ikut menghitung namespace dari lokasi `vendor` dan seluruh test langsung merah (`Target class [cache] does not exist`). `node_modules/` aman di-junction.
- **Cara menyalakan environment lokal (WAJIB tiap sesi baru):**
  1. MariaDB XAMPP harus jalan dulu — nyalakan lewat XAMPP Control Panel, atau: `Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini","--standalone" -WindowStyle Hidden`. Kalau lupa, semua perintah artisan gagal dengan error 2002.
  2. PHP tidak ada di PATH global — pakai `C:\xampp\php\php.exe` atau tambahkan `C:\xampp\php` ke PATH sesi.
  3. Akun demo lokal: `admin@egoto.test` / `vendor@egoto.test` / `customer@egoto.test`, password semuanya `password`.
  4. Database: `egoto` (aplikasi) dan `egoto_testing` (khusus test, dipakai phpunit.xml).
- **Ada blocker/perlu keputusan Anda:**
  1. ~~Keputusan design system (PLAN §1 no.2)~~ — **selesai 2026-08-05**: editorial hangat (sand/forest/terracotta, Fraunces + Inter). Detail di GUIDE.md bagian Design System.
  2. **Kredensial Google OAuth belum ditempel.** Kode Socialite sudah jalan penuh dan teruji (mock), tapi tombol "Masuk dengan Google" masih tersembunyi sampai `GOOGLE_CLIENT_ID` + `GOOGLE_CLIENT_SECRET` diisi di `.env` — slotnya sudah disiapkan di baris paling bawah. Ikuti `docs/oauth-setup-guide.md`, redirect URI lokal `http://localhost:8000/auth/google/callback`. Login manual tidak terpengaruh. Facebook dibatalkan.
  3. Hosting Hostinger + verifikasi restore backup belum dikerjakan (butuh akun Anda). Dibutuhkan di D7.
  4. Catatan beda dev vs production: dev pakai **PHP 8.2 + MariaDB 10.4**, production Hostinger **PHP 8.3 + MySQL 8**. Sudah dimitigasi (driver `mysql` dikunci, query JSON-path dilarang, test jalan di MySQL), tapi tetap perlu uji ulang saat deploy.

*(Sesi baru WAJIB baca bagian ini dulu sebelum mulai kerja — lihat "Ritual awal sesi" di EXECUTION_PROMPTS.md)*

## D0 — Persiapan paralel

- [ ] OAuth app Google Cloud Console didaftarkan (Facebook dibatalkan 2026-08-05 — Google saja)
- [ ] Hosting Hostinger Business siap (domain/subdomain, PHP 8.3, SSH aktif)
- [ ] Backup Hostinger diverifikasi BISA di-restore (bukan cuma aktif)
- [x] git init, .gitignore, branch main
- [x] CLAUDE.md ada di root repo (bukan di docs/)
- [x] docs/GUIDE.md dan docs/PLAN.md sinkron, tanpa versi ganda
- [x] docs/oauth-setup-guide.md siap dipakai (Google, langkah bernomor)
- [x] PHP + Composer + database terpasang di mesin dev (XAMPP: PHP 8.2.12 + MariaDB 10.4.32; ekstensi zip/gd/intl diaktifkan)

## D1 — Scaffold & fondasi

- [x] Laravel 12 + Filament 3, 2 panel (/admin, /vendor)
- [x] Migration semua tabel V1 (11 tabel)
- [x] Model + Enum + Contract (PaymentGateway, MessagingService, TicketSigner)
- [x] Middleware role
- [x] Seeder 6 kategori (internasional is_active=false)
- [x] Akun admin & vendor demo lokal (+ customer, untuk menguji penolakan panel)
- [x] `php artisan migrate:fresh --seed` bersih
- [x] Login Filament admin demo berhasil
- [x] Gerbang `canAccessPanel` — customer & vendor ditolak dari /admin (ada test-nya)
- [x] Tailwind 4 + Alpine.js via Vite (build produksi, bukan CDN)
- [x] Pest terpasang, test jalan di MySQL bukan SQLite (lockForUpdate no-op di SQLite)
- [x] Laravel Boost + MCP terdaftar di .mcp.json

## D2 — Browsing publik

- [x] Homepage: hero, Trip Populer, Jadwal Terdekat, grid kategori
- [x] Halaman kategori + filter (tanggal, harga, urutan) + paginasi
- [x] Detail trip: galeri, itinerary, jadwal+kuota, harga bertingkat, CTA
- [x] Komponen Blade reusable (layouts.app, trip-card, price-tag, status-badge, empty-state, trip-image, section-heading)
- [x] Cek 3 breakpoint (mobile/tablet/desktop) — layout mobile-first, grid `sm`/`lg`/`xl`, panel filter dilipat di bawah `lg`
- [x] Guest akses 3 halaman ini tanpa redirect login (dijaga test `PublicBrowsingTest`)

## D3 — Auth & profil

- [x] Login/register manual (`/masuk`, `/daftar`, `/keluar`) + rate limit 5 percobaan/menit
- [x] Socialite Google (tombol otomatis tersembunyi selama kredensial kosong)
- [x] Lengkapi profil setelah daftar (bisa dilewati lewat `/profil/lewati`)
- [x] Login gate: url.intended redirect balik ke booking yang sama
- [x] Halaman profil + kerangka "Booking Saya"

## D4 — Booking

- [ ] Form booking: jadwal, pax, leader+peserta
- [ ] Field adaptif NIK/paspor/none sesuai kategori
- [ ] Enkripsi id_number + id_number_hash terpisah
- [ ] Nominal unik (3 digit, retry kalau bentrok)
- [ ] Kunci kuota (transaksi + lockForUpdate)
- [ ] expires_at 2 jam
- [ ] Scheduled Command bookings:expire (bukan queue)
- [ ] Test: booking pendakian tanpa NIK gagal
- [ ] Test: booking lebih dari sisa kuota ditolak
- [ ] Test: booking expired otomatis, kuota kembali

## D5 — Pembayaran & verifikasi admin

- [ ] Halaman pembayaran: QRIS, kode booking besar, nominal unik jelas
- [ ] Upload bukti bayar di website (bukan WA)
- [ ] proof_hash + deteksi duplikat (flag, bukan auto-reject)
- [ ] Tombol wa.me auto-generate notifikasi ke admin
- [ ] Filament: daftar pending + badge dashboard
- [ ] Layar verifikasi: bukti vs nominal berdampingan, banner duplikat
- [ ] Approve/reject (reject wajib alasan)
- [ ] Test: hash sama di 2 booking → ter-flag
- [ ] Test: reject tanpa alasan gagal

## D6 — Tiket, e-tiket, check-in vendor

- [ ] Generate tiket otomatis saat approve (sinkron)
- [ ] Token + HMAC signature
- [ ] QR isi token (bukan URL)
- [ ] E-tiket branded E-GOTO di "Booking Saya"
- [ ] Panel vendor: input token, validasi signature+status
- [ ] Anti double check-in (transaksi+lockForUpdate)
- [ ] Test: token dipakai 2x → ditolak
- [ ] Test: signature diubah → ditolak

## D7 — Hardening & rilis terbatas

- [ ] Seeder 10-12 trip variatif (kondisi kuota beragam)
- [ ] $fillable/$guarded eksplisit semua model
- [ ] Cek N+1 query
- [ ] APP_DEBUG=false production, tidak ada stack trace bocor
- [ ] Rate limit login, upload bukti, submit booking
- [ ] `php artisan test` hijau (11 test wajib)
- [ ] Pint lulus
- [ ] Deploy Hostinger
- [ ] Backup diverifikasi restore
- [ ] Password admin/vendor demo diganti (bukan default)

**✅ V1 selesai kalau semua di atas tercentang — ini syarat sebelum lanjut V1.5.**

---

## D8 — Onboarding mitra

- [ ] Halaman publik "Jadi Mitra E-GOTO"
- [ ] Form pengajuan + upload dokumen
- [ ] Admin: daftar pengajuan, jadwal meeting, catatan
- [ ] Approve → akun vendor otomatis dibuat

## D9 — Loop mitra aktif

- [ ] Vendor ajukan trip (pending_review)
- [ ] Admin approve/tolak (alasan wajib kalau tolak)
- [ ] Vendor: daftar trip sedang/sudah berjalan
- [ ] Notifikasi booking baru ke vendor

## D10 — Voucher, promo, combo, opsi trip

- [ ] CRUD voucher admin (percent/fixed, scope, kuota, masa berlaku)
- [ ] Terapkan di checkout + validasi lengkap
- [ ] trip_options CRUD (vendor/admin)
- [ ] Opsi tampil di detail trip & checkout

## D11 — Rating & komentar

- [ ] Rating 1-5 + komentar untuk booking completed
- [ ] Tampil di detail trip (rata-rata + daftar)
- [ ] Vendor lihat rating trip miliknya
- [ ] Admin bisa sembunyikan review abusive

## D12 — Private trip & polish

- [ ] Form request private trip → wa.me terisi otomatis
- [ ] Teaser "Jadi Mitra E-GOTO?" di homepage & profil

## D13 — QA & rilis V1.5

- [ ] Regression test full alur V1+V1.5
- [ ] Cek responsive ulang semua halaman baru
- [ ] Pint lulus
- [ ] Deploy Hostinger
- [ ] CHANGELOG.md dirangkum status final V1.5

**✅ V1.5 selesai kalau semua di atas tercentang.**

---

## Belum dimulai (V2 — menunggu keputusan komisi)

- [ ] Skema komisi 3-10% ditetapkan (flat/tiered per kategori)
- [ ] Dashboard pemasukan vendor interaktif
- [ ] Modul lain sesuai feedback nyata V1.5

## Keputusan masih menggantung (lihat GUIDE.md & PLAN.md bagian 1)

- [ ] Trip internasional: dummy aktif untuk uji coba, atau tetap tutup?
- [x] Design system: **diputuskan 2026-08-05** — editorial hangat (sand/forest/terracotta, Fraunces + Inter)
- [ ] Skema komisi platform: flat atau tiered per kategori?

## Catatan teknis penting (jangan diabaikan)

- Queue TIDAK dipakai di V1 — expired booking pakai Scheduled Command, bukan queue job (lihat CLAUDE.md bagian 8, PLAN.md 12b)
- NIK/paspor wajib `encrypted` cast + `id_number_hash` terpisah untuk lookup
- Akun demo HANYA untuk lokal — wajib diganti sebelum ada user asli
