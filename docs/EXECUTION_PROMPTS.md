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

## D0 — Persiapan paralel

```
Buatkan docs/oauth-setup-guide.md: langkah bernomor detail daftar OAuth Google Cloud Console + Facebook Developer sampai dapat Client ID+Secret (saya yang eksekusi klik manual). Paralel: git init, .gitignore Laravel, branch main, commit awal. Pastikan CLAUDE.md di root (bukan docs/).

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
Login/register manual + Socialite Google+Facebook (kalau kredensial FB belum ada: kode siap, tombol disembunyikan via config flag). Lengkapi profil setelah daftar. Login gate PLAN.md §5.5: guest klik booking → session url.intended → login → redirect balik ke booking yang sama. Kerangka profil + "Booking Saya".

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

## MILESTONE V1.5

## D8 — Onboarding mitra

```
Halaman publik "Jadi Mitra E-GOTO" + form pengajuan+dokumen. Admin: daftar pengajuan, jadwal meeting, catatan, approve/tolak→approve buat akun vendor+akses panel.

[+ template penutup]
```

## D9 — Loop mitra aktif

```
Vendor ajukan trip (pending_review). Admin approve/tolak (alasan wajib). Vendor lihat daftar trip berjalan/selesai+jumlah peserta, notif booking baru.

[+ template penutup]
```

## D10 — Voucher, promo, combo, opsi trip

```
CRUD voucher admin (percent/fixed, min_spend, kuota, masa berlaku, scope). Checkout: validasi kadaluarsa/kuota/min_spend/dobel pakai, catat voucher_usages. trip_options (Camping/Tubing/dll): CRUD vendor/admin, tampil detail+checkout, masuk subtotal.

[+ template penutup]
```

## D11 — Rating & komentar

```
Booking completed→rating 1-5+komentar (1/booking). Tampil di detail trip (rata-rata+daftar). Vendor lihat rating trip-nya. Admin bisa hide review abusive.

[+ template penutup]
```

## D12 — Private trip & polish

```
Form request private trip→wa.me terisi otomatis. Teaser "Jadi Mitra E-GOTO?" di homepage+profil.

[+ template penutup]
```

## D13 — QA & rilis V1.5

```
Regression test full V1+V1.5. Cek responsive semua halaman baru. Pint. Deploy Hostinger.

[+ template penutup]
```

**Setelah D13 lolos:** `"V1.5 selesai. Laporan akhir fase sesuai CLAUDE.md §14. [+ template penutup]"`

---

## Cara pakai

Ganti `[+ template penutup]` dengan blok lengkap di atas. Berurutan D0→D13, jangan lompat ke D8 sebelum D7 lolos.
