# E-GOTO — Progress Checklist

Status terakhir: **D0 selesai (sisi kode), menunggu instalasi PHP/Composer/MySQL sebelum D1.** Centang tiap item selesai. Dokumen ini dipakai terus-menerus untuk pengembangan selanjutnya — jangan dihapus/diganti, cukup update.

## Sesi Terakhir (WAJIB diupdate tiap akhir sesi Claude Code CLI)

- **Tanggal/waktu sesi terakhir:** 2026-08-05
- **Sedang mengerjakan:** D0 selesai — git init + branch `main` + 2 commit awal, `.gitignore` Laravel 12 + `.gitattributes` (eol=lf), folder `Docs/` → `docs/` lowercase, `docs/PLAN.md` §0 disinkronkan, `docs/CHANGELOG.md` dibuat. `docs/oauth-setup-guide.md` diverifikasi sudah lengkap (tidak ditulis ulang).
- **Langkah persis berikutnya:** D1 — scaffold Laravel 12 + Filament 3 (2 panel `/admin` + `/vendor`). **Catatan scaffold:** root repo sudah tidak kosong, jadi `composer create-project laravel/laravel .` akan gagal — scaffold ke direktori sementara lalu pindahkan isinya ke root tanpa menimpa `CLAUDE.md`, `.gitignore`, `.gitattributes`, `docs/`, `.claude/`. Sebelum itu pasang Laravel Boost MCP (CLAUDE.md §8).
- **Ada blocker/perlu keputusan Anda:**
  1. **PHP 8.3, Composer, dan MySQL 8 belum terpasang** di mesin ini (Laragon/XAMPP/Herd juga tidak ada). Node v24 ✅ dan Git 2.55 ✅ sudah ada. **D1 tidak bisa dimulai sebelum tiga hal ini tersedia** — rekomendasi tercepat: install Laragon (PHP 8.3 + MySQL 8 + Composer sekaligus dalam satu installer).
  2. Pendaftaran OAuth Google + Facebook masih menunggu eksekusi manual Anda — panduannya sudah siap di `docs/oauth-setup-guide.md`. Approval Facebook bisa makan hari, mulai sedini mungkin (risiko #1 di PLAN.md §11).
  3. Hosting Hostinger + verifikasi restore backup belum dikerjakan (butuh akun Anda).

*(Sesi baru WAJIB baca bagian ini dulu sebelum mulai kerja — lihat "Ritual awal sesi" di EXECUTION_PROMPTS.md)*

## D0 — Persiapan paralel

- [ ] OAuth app Google Cloud Console didaftarkan
- [ ] OAuth app Facebook Developer didaftarkan (submit review kalau perlu)
- [ ] Hosting Hostinger Business siap (domain/subdomain, PHP 8.3, SSH aktif)
- [ ] Backup Hostinger diverifikasi BISA di-restore (bukan cuma aktif)
- [x] git init, .gitignore, branch main
- [x] CLAUDE.md ada di root repo (bukan di docs/)
- [x] docs/GUIDE.md dan docs/PLAN.md sinkron, tanpa versi ganda
- [x] docs/oauth-setup-guide.md siap dipakai (Google + Facebook, langkah bernomor)
- [ ] PHP 8.3 + Composer + MySQL 8 terpasang di mesin dev — **blocker D1**

## D1 — Scaffold & fondasi

- [ ] Laravel 12 + Filament 3, 2 panel (/admin, /vendor)
- [ ] Migration semua tabel V1
- [ ] Model + Enum + Contract (PaymentGateway, MessagingService)
- [ ] Middleware role
- [ ] Seeder 6 kategori (internasional is_active=false)
- [ ] Akun admin & vendor demo lokal
- [ ] `php artisan migrate:fresh --seed` bersih
- [ ] Login Filament admin demo berhasil

## D2 — Browsing publik

- [ ] Homepage: hero, Trip Populer, Jadwal Terdekat, grid kategori
- [ ] Halaman kategori + filter
- [ ] Detail trip: galeri, itinerary, jadwal+kuota, harga bertingkat, CTA
- [ ] Komponen Blade reusable (trip-card, price-tag, status-badge)
- [ ] Cek 3 breakpoint (mobile/tablet/desktop)
- [ ] Guest akses 3 halaman ini tanpa redirect login

## D3 — Auth & profil

- [ ] Login/register manual
- [ ] Socialite Google
- [ ] Socialite Facebook (atau flag config kalau kredensial belum turun)
- [ ] Lengkapi profil setelah daftar
- [ ] Login gate: url.intended redirect balik ke booking yang sama
- [ ] Halaman profil + kerangka "Booking Saya"

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
- [ ] Design system: 2 variasi Claude Design → pilih 1
- [ ] Skema komisi platform: flat atau tiered per kategori?

## Catatan teknis penting (jangan diabaikan)

- Queue TIDAK dipakai di V1 — expired booking pakai Scheduled Command, bukan queue job (lihat CLAUDE.md bagian 8, PLAN.md 12b)
- NIK/paspor wajib `encrypted` cast + `id_number_hash` terpisah untuk lookup
- Akun demo HANYA untuk lokal — wajib diganti sebelum ada user asli
