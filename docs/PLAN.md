# E-GOTO — PLAN.md (Rencana Eksekusi Teknis)

Turunan teknis dari `docs/GUIDE.md`. GUIDE = sumber kebenaran *scope*. PLAN = urutan kerja, skema data, kriteria selesai. Kalau bentrok → GUIDE menang, PLAN diperbaiki.

---

## 0. Status Repo Saat Ini (FAKTA — hasil scan, per 2026-08-05 setelah D0)

| Item | Kondisi |
|---|---|
| Isi repo | `docs/` (7 dokumen) + `CLAUDE.md` + `.gitignore` + `.claude/settings.json` |
| Kode Laravel | **Belum ada** — belum di-scaffold (D1) |
| Git | ✅ Sudah init, branch `main`, commit awal ada |
| `CLAUDE.md` | ✅ Ada di root repo, isi lengkap (bukan placeholder lagi) |
| Folder dokumen | ✅ `docs/` lowercase — cocok dengan semua rujukan & aman di Hostinger (Linux case-sensitive) |
| `docs/oauth-setup-guide.md` | ✅ Lengkap (Google saja — Facebook dibatalkan 2026-08-05) |
| Plugin aktif (project) | `frontend-design`, `security-guidance`, `commit-commands` |

**Blocker nyata sebelum D1:** **PHP 8.3, Composer, dan MySQL 8 belum terpasang** di mesin dev (Laragon/XAMPP/Herd juga tidak ada). Node v24 dan Git 2.55 sudah ada. Scaffold Laravel tidak bisa dijalankan sampai tiga hal itu tersedia.

**Catatan scaffold D1:** root repo sudah tidak kosong (`docs/`, `CLAUDE.md`, `.gitignore`, `.claude/`), sedangkan `composer create-project laravel/laravel .` menolak direktori tidak kosong. Solusinya: scaffold ke direktori sementara lalu pindahkan isinya ke root (jangan timpa `.gitignore`/`CLAUDE.md` yang sudah ada).

---

## 1. Keputusan Yang Masih Menggantung

Tidak memblok start. Diberi **default sementara** supaya kerja jalan; ganti kalau ada keputusan resmi.

| # | Pertanyaan | Default sementara dipakai PLAN | Deadline keputusan |
|---|---|---|---|
| 1 | Trip internasional: dummy atau tutup? | Kategori dibuat, `is_active = false`. Seeder isi 1 trip dummy status hidden | Sebelum D7 (seeder) |
| 2 | ~~Design system: 2 varian → pilih 1~~ **SUDAH DIPUTUSKAN 2026-08-05** | **Editorial hangat: sand (permukaan) + forest (teks/aksen) + terracotta (khusus CTA); Fraunces + Inter, self-host lewat Vite.** Token ada di `resources/css/app.css`, detail di GUIDE.md | ✅ selesai sebelum D2 |
| 3 | Komisi platform 3–10%: flat atau tiered? | Kolom `commission_percent` disiapkan di `vendors`, **tidak dipakai** di V1/V1.5 | Sebelum V2 |

---

## 2. Stack Final

- Laravel 12.x. **PHP 8.2.12 (dev, XAMPP) → PHP 8.3 (production Hostinger)**. **MariaDB 10.4.32 (dev, XAMPP) → MySQL 8 (production Hostinger)**. Driver Laravel dikunci ke `mysql` (bukan `mariadb`) supaya SQL yang dihasilkan di lokal identik dengan yang jalan di production. Konsekuensi yang harus diingat: kolom JSON tetap pakai `$table->json()` + cast `array` (aman di keduanya, di MariaDB tersimpan sebagai LONGTEXT), tapi **jangan pakai query JSON-path** (`whereJsonContains`, operator `->>`) — implementasinya berbeda antara MariaDB 10.4 dan MySQL 8. V1 tidak butuh query JSON sama sekali.
- Filament 3 — panel `/admin` (Admin) + panel `/vendor` (Vendor). Dua panel terpisah, bukan satu panel dengan filter role.
- Blade + Alpine.js + Tailwind — sisi customer (publik + booking + profil)
- Laravel Socialite — Google saja (Facebook dibatalkan 2026-08-05)
- Pest — testing
- Pint — formatter
- `simple-qrcode` (endroid/bacon) — generate QR tiket
- Queue: **tidak dipakai di V1**. Generate tiket & notifikasi WA berjalan sinkron (dalam request-response, bukan job). Expired booking pakai **Scheduled Artisan Command** (`bookings:expire`) via `schedule:run`, bukan queue table — menghindari kebutuhan cron kedua (`queue:work`) di shared hosting. Kalau V1.5/V2 nanti butuh job async sungguhan (kirim WA API massal, dsb), baru introduce `database` queue + cron `queue:work --stop-when-empty` terpisah, didokumentasikan eksplisit saat itu.

---

## 3. Arsitektur

```
app/
  Actions/          # BookingCreate, PaymentVerify, TicketIssue, TicketCheckIn
  Contracts/        # PaymentGateway, MessagingService, TicketSigner
  Enums/            # BookingStatus, PaymentStatus, TripStatus, TicketStatus, VendorStatus, IdType
  Http/Requests/    # Form Request semua input >2 field
  Models/
  Services/
    Payment/        # ManualQrisGateway (V1) → MidtransGateway (backlog)
    Messaging/      # WhatsAppLinkService (V1) → WhatsAppApiService (backlog)
    Ticket/         # HmacTicketSigner
  Filament/
    Admin/Resources/
    Vendor/Resources/
resources/views/
  components/       # Blade reusable: trip-card, price-tag, status-badge, empty-state
  pages/            # home, category, trip-detail, booking, payment, my-bookings
```

**Fondasi extensible (wajib V1, dipakai V2):**
- `interface PaymentGateway { createCharge(Booking): PaymentInstruction; verify(Payment): bool; }` — V1 diisi `ManualQrisGateway` (nominal unik + verifikasi manual admin). Midtrans nanti tinggal swap binding.
- `interface MessagingService { notifyAdminNewPayment(Booking): string; }` — V1 balikin URL `wa.me`. WhatsApp Business API nanti swap.
- Enum PHP native untuk semua status, jangan string bebas.
- Middleware `role:admin|vendor|customer` sejak awal walau role masih 3.

---

## 4. Skema Database (V1 + V1.5)

**V1**

| Tabel | Kolom penting |
|---|---|
| `users` | name, email, password (nullable — OAuth), provider, provider_id, avatar, phone, role, email_verified_at |
| `customer_profiles` | user_id, full_name, dob, gender, address, emergency_contact_name/phone |
| `categories` | name, slug, `id_requirement` (none/nik/passport), is_active, sort_order, icon |
| `trips` | vendor_id (nullable = milik E-GOTO), category_id, title, slug, description, itinerary, includes, excludes, meeting_point, cover_image, status, is_featured, published_at |
| `trip_images` | trip_id, path, sort_order |
| `trip_schedules` | trip_id, start_date, end_date, quota, booked_count, status |
| `trip_prices` | trip_schedule_id, label, price, min_pax, max_pax (harga bertingkat) |
| `bookings` | code (unik, tampil besar), user_id, trip_schedule_id, pax_count, subtotal, discount_amount, unique_code (3 digit), total_amount, status, expires_at, notes |
| `booking_participants` | booking_id, is_leader, full_name, phone, id_type, **id_number (encrypted)**, dob, emergency_contact |
| `payments` | booking_id, method, amount_declared, proof_path, **proof_hash (sha256)**, status, verified_by, verified_at, reject_reason, is_duplicate_flagged |
| `tickets` | booking_id, participant_id, token (unik), signature, qr_path, status, checked_in_at, checked_in_by |

**V1.5 tambahan**

| Tabel | Kolom penting |
|---|---|
| `vendors` | user_id, business_name, slug, logo, description, phone, address, status, approved_at, commission_percent (siap V2) |
| `vendor_applications` | business_name, contact, documents (json path), status, meeting_at, admin_note, reviewed_by |
| `vouchers` | code, type (percent/fixed), value, min_spend, quota, used_count, valid_from, valid_until, scope (global/category/trip), scope_id |
| `voucher_usages` | voucher_id, booking_id, user_id, amount_cut |
| `reviews` | booking_id (unik), trip_id, user_id, rating 1–5, comment, status (published/hidden) |
| `trip_options` | trip_id, name (mis. Camping/Tubing/Playground), extra_price, is_active — opsi aktivitas tambahan per trip, dipilih customer saat booking |
| `booking_options` | booking_id, trip_option_id, qty — opsi yang dipilih per booking |

**Enum status**

```
BookingStatus:  pending_payment → awaiting_verification → confirmed | rejected | expired | cancelled → completed
PaymentStatus:  pending | verified | rejected
TripStatus:     draft | pending_review | published | rejected | archived
TicketStatus:   issued | used | void
VendorStatus:   pending | approved | rejected | suspended
```

**Aturan kolom JSON (dev MariaDB vs prod MySQL):** kolom bertipe JSON (`vendor_applications.documents` di V1.5) dibuat dengan `$table->json()` + cast `array` di model. Ini aman di kedua sisi. Yang **dilarang**: query JSON-path (`whereJsonContains()`, operator `->>`, index fungsional atas JSON) — perilakunya berbeda antara MariaDB 10.4 dan MySQL 8, dan bug seperti ini baru ketahuan saat deploy. Kalau suatu saat butuh mencari di dalam JSON, buat kolom terpisah, jangan query ke dalam JSON.

**Aturan enkripsi PII:** `booking_participants.id_number` pakai cast `encrypted`. Kolom `text`, bukan `varchar(16)` — ciphertext lebih panjang. Konsekuensi: **tidak bisa di-`where` langsung**. Kalau butuh cari NIK, tambah kolom `id_number_hash` (sha256) khusus lookup. Diputuskan sekarang, bukan pas migrasi sudah jalan.

---

## 5. Mekanisme Kunci

### 5.1 Nominal unik
`total_amount = subtotal - discount + unique_code`, `unique_code` = 3 digit acak 1–999, **dikunci unik** terhadap booking lain yang masih `pending_payment`/`awaiting_verification` pada rentang nominal sama. Kalau bentrok → generate ulang (maks 10x, lalu naikkan ke 4 digit).

### 5.2 Deteksi bukti bayar duplikat
Saat upload: hitung sha256 file → cek `payments.proof_hash`. Ketemu di booking lain → set `is_duplicate_flagged = true`, tetap tersimpan, admin lihat banner merah. **Tidak auto-reject** — bisa jadi kesalahan user, keputusan tetap di admin.

### 5.3 Expired otomatis
`bookings.expires_at = created_at + 2 jam` (konfigurabel di config). Scheduled Artisan Command `bookings:expire` didaftarkan di `routes/console.php` (`Schedule::command('bookings:expire')->everyFiveMinutes()`), dijalankan lewat cron `schedule:run` — bukan queue job. Command: booking `pending_payment` lewat `expires_at` → `expired`, `trip_schedules.booked_count` dikembalikan (transaksi + `lockForUpdate()`). Kuota ditahan sejak booking dibuat, bukan sejak bayar.

### 5.4 Tiket + QR anti pakai-ganda
- Token = `random 32 char`. Signature = `hmac_sha256(token|booking_code|participant_id, APP_KEY)`.
- QR isi `token`, bukan URL — vendor input/scan token di panel.
- Validasi check-in: signature cocok **AND** `status = issued` → set `used`, catat `checked_in_at` + `checked_in_by`. Selain itu → tolak dengan alasan jelas ("sudah dipakai jam X" / "tiket tidak valid").
- Update status pakai transaksi + `lockForUpdate()` — cegah double check-in barengan.

### 5.5 Login gate
Tombol "Booking Sekarang" pada guest → simpan `session('url.intended')` → login/daftar → (kalau baru) lengkapi profil → **redirect balik otomatis** ke halaman booking trip yang tadi. Browsing publik nol hambatan, tidak ada middleware `auth` di route publik.

---

## 6. FASE V1 — Fondasi (6–7 hari)

**D0 — paralel, mulai hari pertama, jangan ditunda**
- Daftar OAuth app Google Cloud Console (app External mulai di mode Testing — email penguji awal didaftarkan manual di Audience → Test users)
- Siapkan hosting Hostinger + **verifikasi backup benar-benar jalan** (bukan cuma "ada menunya")
- `git init`, `.gitignore`, branch `main`

**D1 — Scaffold & fondasi**
- Laravel 12 + Filament 3 (2 panel), Tailwind, Alpine
- `CLAUDE.md` ke root repo, placeholder diisi
- Migration + Model + Enum + Contracts semua tabel V1
- Seeder kategori (6 kategori, internasional `is_active=false`)
- Middleware role, factory dasar
- ✅ Selesai kalau: `php artisan migrate:fresh --seed` bersih, login Filament admin demo bisa

**D2 — Browsing publik (tanpa login)**
- Homepage: hero, Trip Populer, Jadwal Terdekat, grid kategori
- Halaman kategori + filter (tanggal, harga, kategori)
- Detail trip: galeri, itinerary, jadwal + sisa kuota, harga bertingkat, CTA booking
- Komponen Blade reusable, mobile-first, cek 3 breakpoint (<640 / 640–1024 / >1024)
- ✅ Selesai kalau: 3 halaman ini jalan tanpa auth sama sekali, tidak ada redirect login

**D3 — Auth & profil**
- Login/register manual + Socialite Google (kalau kredensial belum turun: kode siap, tombol disembunyikan lewat config flag — bukan alasan berhenti)
- Lengkapi profil setelah daftar
- `url.intended` redirect balik ke booking
- Halaman profil + "Booking Saya" (kerangka)
- ✅ Selesai kalau: alur guest → klik booking → login → balik ke booking yang sama, mulus

**D4 — Booking**
- Form booking: pilih jadwal, jumlah pax, data leader + peserta
- Field adaptif: kategori `nik` → wajib NIK 16 digit; `passport` → paspor; `none` → tanpa ID
- Enkripsi `id_number` saat simpan
- Hitung total + nominal unik, generate kode booking, kunci kuota, set `expires_at`
- Job expired otomatis
- ✅ Selesai kalau: test — booking domestik wajib NIK, booking kuota penuh ditolak, booking lewat 2 jam jadi `expired` + kuota balik

**D5 — Pembayaran & verifikasi admin**
- Halaman pembayaran: QRIS statis, **kode booking besar & jelas**, nominal unik ditonjolkan, instruksi catatan transfer
- Upload bukti bayar **di dalam website** (bukan lempar ke WA) + hash duplikat
- Tombol WA `wa.me` auto-generate notif ke admin
- Filament admin: daftar pembayaran pending + badge notifikasi di dashboard, tampilan **berdampingan** (nominal seharusnya vs bukti bayar besar), flag duplikat merah
- Approve / Reject (alasan **wajib**) → alasan tampil di sisi customer, customer bisa upload ulang
- ✅ Selesai kalau: test — upload bukti sama di 2 booking → yang kedua ter-flag; reject tanpa alasan → gagal

**D6 — Tiket, e-tiket, check-in vendor**
- Approve pembayaran → auto-generate tiket per peserta + QR branded E-GOTO
- "Booking Saya": lihat/unduh e-tiket
- Panel vendor: input token → validasi signature → check-in sukses / tolak jelas
- ✅ Selesai kalau: test — token dipakai 2x → tolak; token diubah 1 karakter → signature invalid → tolak

**D7 — Hardening & rilis terbatas**
- Seeder demo: 10–12 trip, ≥2 per kategori aktif, variasi harga bertingkat, variasi tanggal (uji "Jadwal Terdekat"), ≥1 kuota hampir penuh, ≥1 kuota penuh (state disabled/waitlist)
- Audit: `$fillable`/`$guarded` eksplisit semua model, eager load anti N+1, tidak ada `.env`/stack trace bocor, `APP_DEBUG=false` di production
- `php artisan test` hijau + `./vendor/bin/pint`
- Deploy Hostinger, backup diverifikasi restore-nya
- Ganti password admin production (akun demo **hanya lokal**)
- ✅ **Output V1:** loop transaksi penuh jalan, aman, siap user asli terbatas

---

## 7. FASE V1.5 — Marketplace & Interaktivitas (5–7 hari, lanjut langsung)

**D8 — Onboarding mitra**
- Halaman/blog publik "Jadi Mitra E-GOTO" (kriteria, benefit)
- Form pengajuan + upload dokumen
- Admin: review pengajuan, jadwalkan meeting, approve/tolak → approve bikin akun vendor + akses panel

**D9 — Loop mitra aktif**
- Vendor ajukan trip (foto, harga, kuota, jadwal) → status `pending_review`
- Admin approve/tolak → `published`/`rejected` (alasan wajib)
- Vendor: daftar trip sedang/sudah berjalan + jumlah peserta, notifikasi booking baru

**D10 — Voucher, promo, combo, opsi trip**
- CRUD voucher di admin (percent/fixed, min spend, kuota, masa berlaku, scope)
- Terapkan di checkout: total terpotong otomatis, `voucher_usages` tercatat
- Validasi: kadaluarsa, kuota habis, min spend tidak terpenuhi, dobel pakai
- `trip_options` (opsi aktivitas tambahan per trip, mis. Camping/Tubing/Playground): CRUD vendor/admin, tampil di detail trip & checkout, harga tambahan otomatis masuk `subtotal`

**D11 — Rating & komentar**
- Booking `completed` (lewat tanggal trip) → customer bisa rating 1–5 + komentar, 1 review per booking
- Tampil di detail trip (rata-rata + daftar), vendor lihat rating trip-nya
- Admin bisa sembunyikan review abusive

**D12 — Private trip & polish**
- Request private trip → form ringkas → link `wa.me` ke admin/CS dengan pesan terisi otomatis
- Teaser "Jadi Mitra E-GOTO?" di homepage + profil customer

**D13 — QA & rilis**
- Regression test full alur, cek responsive ulang semua halaman baru, Pint, deploy
- ✅ **Output V1.5:** multi-mitra jalan, promo aktif, rating aktif, jalur private trip ada

---

## 8. FASE V2 — Setelah V1.5 Stabil (tidak diestimasi)

**Diblok sampai skema komisi ditetapkan.** Dashboard pemasukan vendor (grafik, breakdown per trip) butuh angka komisi 3–10% + aturan flat/tiered. Modul lain menyusul dari feedback nyata V1.5, bukan ditebak sekarang.

---

## 9. Testing

Setiap fitur: minimal happy-path + 1 edge case (aturan `CLAUDE.md`). Pest, cek konvensi `tests/` sebelum bikin file baru.

Test wajib V1:
1. Guest buka homepage/kategori/detail trip → 200, tanpa redirect login
2. Guest klik booking → redirect login → setelah login balik ke booking yang sama
3. Booking kategori pendakian tanpa NIK → gagal validasi
4. Booking melebihi sisa kuota → ditolak
5. Booking lewat `expires_at` → job set `expired`, kuota kembali
6. Upload bukti dengan hash sama → booking kedua ter-flag duplikat
7. Reject pembayaran tanpa alasan → gagal
8. Approve pembayaran → tiket terbit sejumlah peserta
9. Check-in token valid → sukses; ulang → ditolak
10. Token dengan signature dipalsukan → ditolak
11. NIK tersimpan terenkripsi (raw DB tidak berisi angka NIK)

---

## 10. Checklist Keamanan (gate sebelum production)

- [ ] NIK & paspor terenkripsi, tidak muncul di log/response API
- [ ] `$fillable`/`$guarded` eksplisit semua model
- [ ] Semua input lewat Form Request, tidak ada `$request->all()` mentah ke `create()`
- [ ] Upload bukti bayar: validasi mime + ukuran, disimpan di disk non-publik, akses lewat route ber-authorize
- [ ] Rate limit di login, upload bukti, dan submit booking
- [ ] `APP_DEBUG=false`, stack trace tidak bocor
- [ ] Password admin production diganti kuat & unik, akun demo dihapus/dinonaktifkan
- [ ] Backup Hostinger diverifikasi bisa **restore**, bukan cuma aktif

---

## 11. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| App Google OAuth masih mode Testing | User di luar daftar Test users gagal login Google | Daftarkan email penguji di Audience → Test users (maks 100). Publish app sebelum rilis publik — scope email/profil dasar tidak butuh review manual Google |
| QRIS statis = verifikasi manual | Admin jadi bottleneck saat volume naik | Nominal unik + kode booking + tampilan cocok-cocokan. Midtrans nanti tinggal swap `PaymentGateway` |
| Kuota balapan (2 orang booking sisa 1) | Overbooking | Transaksi + `lockForUpdate()` pada `trip_schedules` saat kunci kuota |
| Enkripsi PII bikin query mati | Ketahuan telat, migrasi ulang | Kolom `id_number_hash` diputuskan sekarang di D1, bukan nanti |
| Scope V2 merembes ke V1 | Estimasi 11–14 hari meleset | Backlog GUIDE dikunci. Item baru masuk backlog, bukan sprint berjalan |

---

## 12b. Koreksi Setelah Review (diterapkan ke dokumen ini)

1. **Queue dihapus dari V1** — awalnya `database` driver, disederhanakan jadi Scheduled Command langsung (`bookings:expire`). Alasan: generate tiket & notifikasi WA sudah sinkron, cuma expired-check yang butuh periodik — pakai queue table di sini cuma menambah 1 cron lagi (`queue:work`) tanpa manfaat, dan bertentangan dengan prinsip awal "tidak ada worker daemon di shared hosting".
2. **Penamaan file dokumen dikunci ke `GUIDE.md`** (tanpa suffix versi seperti "(3)") — supaya Claude Code CLI dan siapa pun yang baca `CLAUDE.md` tidak salah rujuk ke file lama.
3. **`trip_options` ditambahkan eksplisit** ke skema V1.5 (D10) — fitur ini pernah dirancang di iterasi sebelumnya tapi hilang dari draf PLAN ini; sekarang tercatat sebagai bagian resmi V1.5, bukan backlog tersembunyi.

## 12. Aturan Update Dokumen

Keputusan baru yang mengubah scope → update `docs/GUIDE.md` dulu, lalu sesuaikan PLAN. Progres harian dicatat sebagai centang di bagian FASE. PLAN tidak boleh jadi sumber kebenaran scope — itu tugas GUIDE.
