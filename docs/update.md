# E-GOTO — Progress Checklist

Status terakhir: **D0–D7 selesai secara fungsional. Loop transaksi V1 utuh: browsing → login → booking → bayar → verifikasi admin → e-tiket → check-in vendor.** Sisa V1 hanya yang butuh akun Anda (deploy Hostinger, verifikasi restore backup, ganti password demo). Centang tiap item selesai. Dokumen ini dipakai terus-menerus untuk pengembangan selanjutnya — jangan dihapus/diganti, cukup update.

## Sesi Terakhir (WAJIB diupdate tiap akhir sesi Claude Code CLI)

- **Tanggal/waktu sesi terakhir:** 2026-08-11
- **Sedang mengerjakan:** **D4, D5, D6, D7 SELESAI dalam satu sesi.** Booking (form peserta adaptif NIK/paspor/none, kunci kuota `lockForUpdate`, nominal unik, `expires_at` 2 jam, command `bookings:expire`), pembayaran (QRIS + nominal unik, unggah bukti ke disk non-publik, `proof_hash` penanda duplikat, tombol `wa.me`), verifikasi admin di Filament (antrean + badge + widget + layar bukti-berdampingan-nominal, alasan tolak wajib), tiket (HMAC + QR SVG berisi token, e-tiket branded, check-in panel vendor anti pakai-ganda), dan hardening (seeder 13 trip, test penjaga N+1 + rate limit). Verifikasi: **73 test lulus / 281 assertion**, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, log bersih.
- **Catatan sesi ini:** D2 + D3 ternyata sudah dikerjakan di worktree `d2-browsing-publik` tapi belum pernah masuk `main` — sudah di-fast-forward, worktree-nya dihapus. Kredensial Google OAuth yang Anda tempel di `docs/oauth-setup-guide.md` sudah dipindah ke `.env` dan doc dikembalikan ke placeholder (kredensial jangan disimpan di file yang ter-track git). Tombol "Masuk dengan Google" sekarang muncul.
- **Langkah persis berikutnya:** **Deploy** (butuh akun Anda — lihat blocker di bawah), lalu **D8 (V1.5 — onboarding mitra)**. Sebelum D8, jalankan alur uang sekali secara manual di browser untuk memastikan tampilannya sesuai harapan: `php artisan serve` → pesan trip → unggah bukti → approve di `/admin` → buka e-tiket → check-in di `/vendor` (login sebagai `vendor@egoto.test`; supaya vendor bisa check-in, set dulu `trips.vendor_id` ke id user vendor, karena trip demo saat ini milik E-GOTO).
- **Cara menyalakan environment lokal (WAJIB tiap sesi baru):**
  1. MariaDB XAMPP harus jalan dulu — nyalakan lewat XAMPP Control Panel, atau: `Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini","--standalone" -WindowStyle Hidden`. Kalau lupa, semua perintah artisan gagal dengan error 2002.
  2. PHP tidak ada di PATH global — pakai `C:\xampp\php\php.exe` atau tambahkan `C:\xampp\php` ke PATH sesi.
  3. Akun demo lokal: `admin@egoto.test` / `vendor@egoto.test` / `customer@egoto.test`, password semuanya `password`.
  4. Database: `egoto` (aplikasi) dan `egoto_testing` (khusus test, dipakai phpunit.xml).
- **Ada blocker/perlu keputusan Anda:**
  1. ~~Keputusan design system (PLAN §1 no.2)~~ — **selesai 2026-08-05**: editorial hangat (sand/forest/terracotta, Fraunces + Inter). Detail di GUIDE.md bagian Design System.
  2. ~~Kredensial Google OAuth belum ditempel~~ — **selesai 2026-08-11**: sudah masuk `.env`, tombol "Masuk dengan Google" muncul. Redirect URI lokal `http://localhost:8000/auth/google/callback`; **tambahkan URI production di Google Console saat domain aktif**. Jangan tempel kredensial lagi ke file di dalam `docs/` — itu ter-track git.
  3. **Hosting Hostinger + verifikasi restore backup belum dikerjakan (butuh akun Anda).** Ini satu-satunya sisa D7. Termasuk di dalamnya: `APP_DEBUG=false` + `APP_ENV=production` di server, cron `schedule:run` tiap menit (wajib — tanpa itu booking kedaluwarsa tidak pernah melepas kuota), ganti password akun demo, dan tempel berkas QRIS merchant asli menggantikan `public/images/qris-placeholder.svg` (atau arahkan `QRIS_IMAGE_PATH` ke berkas baru). Isi juga `ADMIN_WHATSAPP` dengan nomor admin sungguhan.
  4. Catatan beda dev vs production: dev pakai **PHP 8.2 + MariaDB 10.4**, production Hostinger **PHP 8.3 + MySQL 8**. Sudah dimitigasi (driver `mysql` dikunci, query JSON-path dilarang, test jalan di MySQL), tapi tetap perlu uji ulang saat deploy.
  5. `docs/hostinger.md` yang dirujuk `CLAUDE.md` §17 **belum pernah dibuat** — langkah deploy manual belum tertulis di mana pun. Minta dibuatkan sebelum deploy.

*(Sesi baru WAJIB baca bagian ini dulu sebelum mulai kerja — lihat "Ritual awal sesi" di EXECUTION_PROMPTS.md)*

## D0 — Persiapan paralel

- [x] OAuth app Google Cloud Console didaftarkan (Facebook dibatalkan 2026-08-05 — Google saja); kredensial sudah masuk `.env` 2026-08-11
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

- [x] Form booking: jadwal, pax, leader+peserta (repeater Alpine, maksimal mengikuti sisa kuota)
- [x] Field adaptif NIK/paspor/none sesuai kategori (aturan dari kategori jadwal, bukan dari input browser)
- [x] Enkripsi id_number + id_number_hash terpisah
- [x] Nominal unik (3 digit, retry kalau bentrok, naik 4 digit kalau padat)
- [x] Kunci kuota (transaksi + lockForUpdate, kuota dibaca ulang dari baris terkunci)
- [x] expires_at 2 jam (`config/booking.php`)
- [x] Scheduled Command bookings:expire (bukan queue) — `routes/console.php`, tiap 5 menit
- [x] Test: booking pendakian tanpa NIK gagal
- [x] Test: booking lebih dari sisa kuota ditolak
- [x] Test: booking expired otomatis, kuota kembali

## D5 — Pembayaran & verifikasi admin

- [x] Halaman pembayaran: QRIS, kode booking besar, nominal unik jelas + hitung mundur
- [x] Upload bukti bayar di website (bukan WA), disk non-publik + route ber-authorize
- [x] proof_hash + deteksi duplikat (flag, bukan auto-reject)
- [x] Tombol wa.me auto-generate notifikasi ke admin
- [x] Filament: daftar pending (default tersaring) + badge navigasi + widget dashboard
- [x] Layar verifikasi: bukti vs nominal berdampingan, banner duplikat
- [x] Approve/reject (reject wajib alasan, dijaga di Action bukan cuma form)
- [x] Test: hash sama di 2 booking → ter-flag
- [x] Test: reject tanpa alasan gagal

## D6 — Tiket, e-tiket, check-in vendor

- [x] Generate tiket otomatis saat approve (sinkron, idempoten)
- [x] Token 32 karakter + HMAC signature (verifikasi `hash_equals`)
- [x] QR isi token (bukan URL), dirender SVG inline tanpa imagick
- [x] E-tiket branded E-GOTO di "Booking Saya" (siap cetak)
- [x] Panel vendor: input token, validasi signature+status
- [x] Anti double check-in (transaksi+lockForUpdate)
- [x] Test: token dipakai 2x → ditolak
- [x] Test: signature diubah → ditolak

## D7 — Hardening & rilis terbatas

- [x] Seeder 13 trip variatif (kuota penuh, hampir penuh, jadwal dekat/jauh, 1 internasional draft)
- [x] $fillable/$guarded eksplisit semua model
- [x] Cek N+1 query (dijaga test `PerformaTest`)
- [ ] APP_DEBUG=false production, tidak ada stack trace bocor — **menunggu server**
- [x] Rate limit login, upload bukti, submit booking (dijaga test `RateLimitTest`)
- [x] `php artisan test` hijau — 73 test / 281 assertion, seluruh 11 test wajib PLAN §9 tercakup
- [x] Pint lulus
- [ ] Deploy Hostinger — **butuh akun Anda**
- [ ] Backup diverifikasi restore — **butuh akun Anda**
- [ ] Password admin/vendor demo diganti (bukan default) — **dilakukan saat deploy**

**✅ V1 selesai kalau semua di atas tercentang — ini syarat sebelum lanjut V1.5.**
**Status: seluruh bagian yang bisa dikerjakan tanpa akun hosting sudah selesai; 4 item tersisa semuanya menunggu server.**

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
