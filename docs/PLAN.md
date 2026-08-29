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
| 2 | ~~Design system~~ **DIPUTUSKAN ULANG 2026-08-14 (final)** | **Teal: `mist` (permukaan) + `teal` (teks/aksen) + `amber` khusus CTA; Plus Jakarta Sans + Inter, self-host lewat Vite.** Hex inti dari logo: `#199FA5` / `#077C82` / `#044D4A`. Menggantikan sand/forest/terracotta + Fraunces. Token di `resources/css/app.css`, detail di GUIDE.md | ✅ final |
| 3 | Komisi platform 3–10%: flat atau tiered? | Kolom `commission_percent` disiapkan di `vendors`, **tidak dipakai** di V1/V1.5 | Sebelum V2 |
| 4 | Angka refund, biaya admin, retensi data | **Sementara**: batal >H-7 = 50% dikurangi biaya admin flat Rp25.000; H-7 ke bawah = tanpa refund; retensi NIK/paspor = akun aktif + 2 tahun. Ditandai `[SEMENTARA — validasi sebelum publish]` di halaman S&K/Privasi | Sebelum website dipublikasikan |
| 5 | DP: persentase & tenggat pelunasan | — (skema belum dibangun; lihat GUIDE "Keputusan Disetujui — Pelaksanaan Ditunda") | Setelah V1 live |

---

## 2. Stack Final

- Laravel 12.x. **PHP 8.2.12 (dev, XAMPP) → PHP 8.3 (production Hostinger)**. **MariaDB 10.4.32 (dev, XAMPP) → MySQL 8 (production Hostinger)**. Driver Laravel dikunci ke `mysql` (bukan `mariadb`) supaya SQL yang dihasilkan di lokal identik dengan yang jalan di production. Konsekuensi yang harus diingat: kolom JSON tetap pakai `$table->json()` + cast `array` (aman di keduanya, di MariaDB tersimpan sebagai LONGTEXT), tapi **jangan pakai query JSON-path** (`whereJsonContains`, operator `->>`) — implementasinya berbeda antara MariaDB 10.4 dan MySQL 8. V1 tidak butuh query JSON sama sekali.
- Filament 3 — panel `/admin` (Admin) + panel `/vendor` (Vendor). Dua panel terpisah, bukan satu panel dengan filter role.
- Blade + Alpine.js + Tailwind — sisi customer (publik + booking + profil)
- Laravel Socialite — Google saja (Facebook dibatalkan 2026-08-05)
- Pest — testing
- Pint — formatter
- `simple-qrcode` (endroid/bacon) — generate QR tiket
- Font: `@fontsource-variable/plus-jakarta-sans` (heading/display) + `@fontsource-variable/inter` (body), self-host lewat Vite. `@fontsource-variable/fraunces` **dihapus** dari `package.json` per 2026-08-14
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
    Resources/        # panel admin — AdminPanelProvider discoverResources(app_path('Filament/Resources'))
    Vendor/Resources/ # panel vendor — VendorPanelProvider discoverResources(app_path('Filament/Vendor/Resources'))
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
| `categories` | name, slug, `id_requirement` (none/nik/passport), is_active, sort_order, icon, `gear_checklist` (json, nullable — checklist perlengkapan per kategori, D7.6) |
| `trips` | vendor_id (nullable = milik E-GOTO), category_id, title, slug, description, itinerary, includes, excludes, meeting_point, cover_image, status, is_featured, published_at, `difficulty_level` (enum pemula/menengah/lanjutan, nullable, D7.6) |
| `trip_images` | trip_id, path, sort_order |
| `trip_schedules` | trip_id, start_date, end_date, quota, booked_count, status |
| `trip_prices` | trip_schedule_id, label, price, min_pax, max_pax (harga bertingkat) |
| `bookings` | code (unik, tampil besar), user_id, trip_schedule_id, pax_count, subtotal, discount_amount, unique_code (3 digit), total_amount, status, expires_at, notes |
| `booking_participants` | booking_id, is_leader, full_name, phone, id_type, **id_number (encrypted)**, dob, emergency_contact |
| `payments` | booking_id, method, amount_declared, proof_path, **proof_hash (sha256)**, status, verified_by, verified_at, reject_reason, is_duplicate_flagged |
| `tickets` | booking_id, participant_id, token (unik), signature, qr_path, status, checked_in_at, checked_in_by |

**Kenapa `difficulty_level` di `trips`, bukan `categories`.** Tingkat kesulitan bervariasi antar-trip di dalam satu kategori — dua trip pendakian bisa jauh berbeda beratnya, satu cocok untuk pemula dan satunya jelas tidak. Menaruhnya di kategori akan memaksa seluruh trip pendakian memakai label yang sama, dan label yang salah pada konteks fisik bukan sekadar bikin kecewa. Sebaliknya `gear_checklist` memang generik per kategori (bawaan pendakian relatif sama antar trip pendakian), jadi cukup ditulis sekali di `categories`. Kalau suatu saat ada trip yang butuh daftar bawaan khusus, itu penambahan terpisah — bukan alasan memindahkan kolom ini sekarang.

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
TripDifficulty: pemula | menengah | lanjutan
```

**Aturan kolom JSON (dev MariaDB vs prod MySQL):** kolom bertipe JSON (`categories.gear_checklist` di D7.6, `vendor_applications.documents` di V1.5) dibuat dengan `$table->json()` + cast `array` di model. Ini aman di kedua sisi. Yang **dilarang**: query JSON-path (`whereJsonContains()`, operator `->>`, index fungsional atas JSON) — perilakunya berbeda antara MariaDB 10.4 dan MySQL 8, dan bug seperti ini baru ketahuan saat deploy. Kalau suatu saat butuh mencari di dalam JSON, buat kolom terpisah, jangan query ke dalam JSON.

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

### 5.6 Batas 12 peserta per booking

Cap keras dari `config('booking.max_pax_per_booking')` (default 12). Penegakan berlapis:

1. **Form Request** — `participants` → `max:` dibaca dari config (bukan angka diketik ulang di beberapa tempat). Menggantikan `max:20` yang dipakai `StoreBookingRequest` sebelum 2026-08-14.
2. **UI** — jumlah kursi yang bisa dipilih = `min(12, sisa kuota)`. Kalau sisa kuota lebih besar dari 12, tampilkan ajakan Request Private Trip.
3. **Pesan validasi** — harus menyebut jalur private trip, bukan sekadar "maksimal 12 peserta"; kalau tidak, user mentok tanpa tahu harus ke mana.

Cap ini **tidak menggantikan** pengecekan kuota di `CreateBooking` — dua-duanya berlaku, yang lebih kecil menang.

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
- ✅ **Output V1:** loop transaksi penuh jalan, aman, halaman legal lengkap (D7.5), siap user asli terbatas

**D7.5 — Halaman legal & bantuan (masuk V1, dikerjakan sebelum D8)**

Dinamai D7.5, bukan D8, supaya penomoran D8–D13 di dokumen ini, `update.md`, dan `CHANGELOG.md` tidak perlu digeser.

Tiga halaman statis publik, tanpa login, ditaruh di blok route publik `routes/web.php` (tidak boleh kena middleware `auth`).

- `/faq` — pertanyaan yang benar-benar muncul di alur ini: cara pesan, cara bayar QRIS + kenapa nominalnya unik, berapa lama verifikasi, e-tiket hilang/HP hilang, batas 12 peserta & jalur private trip, ubah data peserta setelah booking, trip dibatalkan.
- `/syarat-ketentuan` — wajib memuat **kebijakan refund** (sumbernya GUIDE bagian "Kebijakan Refund"; kalau beda, GUIDE yang menang):
  - (a) Trip dibatalkan vendor / kuota minimum tidak tercapai, dan (d) **force majeure** (bencana, larangan otoritas, cuaca ekstrem yang membuat trip tidak aman) → **customer pilih 1 dari 3 opsi**: (i) refund 100%, (ii) pindah ke trip/jadwal lain (selisih harga dibicarakan case-by-case dengan admin, belum ada aturan otomatis), (iii) waitlist jadwal trip yang sama berikutnya. **Untuk V1 proses ini manual lewat admin** (customer sampaikan pilihan via WA/form sederhana, admin yang eksekusi) — bukan self-service.
  - (b) Customer batal **lebih dari H-7** → refund **50%** dikurangi biaya admin flat **Rp25.000**. *(angka sementara — lihat §1 no.4)*
  - (c) Customer batal **H-7 ke bawah** (H-7 sampai hari-H) → **tidak ada refund**; ditawarkan reschedule ke jadwal lain trip yang sama kalau vendor punya slot.
  - **Direvisi 2026-08-14 (kedua)** untuk (b)/(c): versi pertama memakai "H-3 s/d H-1" dan menyisakan H-6/H-5/H-4 tanpa aturan, sekarang satu batas di H-7. **Direvisi 2026-08-24** untuk (a)/(d): dari otomatis refund 100%/reschedule jadi 3 opsi pilihan customer di atas. **Halaman `resources/views/pages/syarat-ketentuan.blade.php` sudah disinkronkan ke kedua revisi ini** (lihat `CHANGELOG.md`).
  - Ditambah: tanggung jawab platform vs mitra (E-GOTO wadah pemesanan; pelaksanaan trip tanggung jawab mitra penyelenggara), kewajiban customer (data peserta benar, hadir tepat waktu), dan hak E-GOTO membatalkan sepihak kalau bukti bayar terindikasi palsu.
- `/kebijakan-privasi` — wajib menyebut eksplisit:
  - Data yang dikumpulkan: nama, email, telepon, **NIK/paspor**, kontak darurat, bukti transfer.
  - **NIK/paspor disimpan terenkripsi**, tidak pernah ditampilkan utuh di antarmuka publik, tidak masuk log.
  - Retensi: selama akun aktif + **2 tahun** setelah transaksi terakhir. *(angka sementara — lihat §1 no.4)*
  - Pihak ketiga: **mitra penyelenggara** (nama, kontak, nomor identitas peserta trip yang mereka jalankan — dibutuhkan untuk perizinan & asuransi); **Google** kalau login lewat Google (nama, email, foto profil); penyedia hosting. Tidak ada penjualan data ke pengiklan.
  - Hak customer: minta koreksi/penghapusan data lewat kontak admin.
- Angka yang belum final ditandai `[SEMENTARA — validasi sebelum publish]` **di dalam teks halaman**, bukan cuma di dokumen — supaya tidak ikut terbit diam-diam.
- Tautan ketiganya masuk kolom "Bantuan" di footer `resources/views/components/layouts/app.blade.php` (kolom itu sebelumnya hanya berisi kalimat placeholder).
- ✅ Selesai kalau: test — tamu (tanpa login) buka ketiga URL → 200 tanpa redirect, dan ketiganya tertaut dari footer.

**D7.6 — Penyempurnaan sebelum rilis (masuk V1, setelah D7.5, sebelum D8)**

Enam perbaikan pengalaman di alur yang sudah ada — tidak ada alur baru. Deskripsi produknya di GUIDE bagian "Penyempurnaan Sebelum Rilis (D7.6)".

**Pengecualian migration yang disetujui.** §4 melarang migration untuk tabel/kolom di luar fase berjalan. D7.6 butuh dua kolom baru — **`trips.difficulty_level`** dan **`categories.gear_checklist`** — dan pengecualian ini **disetujui eksplisit 2026-08-14**, dicatat di sini supaya tidak terbaca sebagai aturan yang diterobos diam-diam. Keduanya sudah terdaftar di tabel skema §4 beserta alasan penempatannya, jadi §4 tetap jadi satu-satunya tempat membaca bentuk skema utuh. Aturan JSON §4 berlaku penuh untuk `gear_checklist`: `$table->json()` + cast `array`, dan **dilarang** query JSON-path (`whereJsonContains`, operator `->>`) karena perilakunya berbeda antara MariaDB 10.4 dev dan MySQL 8 production. Checklist hanya dibaca utuh lalu dirender — tidak pernah dicari isinya.

**(a) PWA installable**
- `public/manifest.json`: nama, nama pendek, ikon 192px & 512px, `display: standalone`, `theme_color` `#077C82` (teal logo), `background_color` `#F6FAFA` (`mist-50`), `start_url` `/`.
- Service worker minimal: cache aset statis hasil build Vite saja. **Jangan** cache halaman booking, pembayaran, dan tiket — kuota, hitung mundur, dan status pembayaran yang basi jauh lebih berbahaya daripada halaman yang tidak bisa dibuka offline.
- Registrasi service worker menyusul di `resources/js/app.js`; berkas SW-nya sendiri harus di root domain supaya cakupannya seluruh situs.
- ✅ Selesai kalau: Lighthouse menandai "installable"; prompt install muncul di Chrome Android; halaman pembayaran tetap mengambil data segar sesudah SW aktif.

**(b) Badge "Verified Payment" + estimasi waktu verifikasi**
- Badge status di halaman pembayaran dan "Booking Saya", memakai `PaymentStatus` yang **sudah ada** — jangan tambah state baru; komponen `status-badge` juga sudah tersedia.
- Estimasi waktu diambil dari config (`config('booking.verification_eta')`), bukan angka yang diketik di Blade — supaya bisa dilonggarkan saat ramai tanpa menyentuh view.
- ✅ Selesai kalau: test — pembayaran `verified` menampilkan badge terverifikasi; `pending` menampilkan estimasi waktu.

**(c) Filter level fisik**
- Kolom `difficulty_level` di **`trips`** (bukan `categories`) + Enum PHP native `TripDifficulty` (`pemula` / `menengah` / `lanjutan`) — bukan string bebas, mengikuti aturan §3. Menempel di trip karena dua trip pendakian dalam kategori yang sama bisa berbeda jauh beratnya; lihat catatan penempatan di §4.
- Opsi filter menempel ke panel filter halaman kategori yang sudah ada (tanggal, harga, urutan); ditampilkan menonjol di kategori Pendakian.
- Label di UI berbentuk kalimat manusia ("Cocok untuk pemula"), bukan nama enumnya.
- ✅ Selesai kalau: test — filter `pemula` hanya mengembalikan trip berlevel pemula; tanpa filter, hasilnya sama persis seperti sebelum fitur ini ada.

**(d) Reminder H-1**
- Halaman Filament di panel **admin**: daftar booking berstatus `confirmed` yang jadwal berangkatnya besok, tiap baris punya tombol `wa.me` terisi otomatis.
- Pesan dibangun lewat `MessagingService` — tambah method `remindDayBefore(Booking): string`, **jangan** bikin service baru (WhatsAppLinkService sudah jadi tempatnya, dan swap ke WhatsApp Business API nanti tetap satu pintu).
- Isi pesan: kode booking, titik kumpul, tanggal & jam, itinerary ringkas, checklist perlengkapan kategori. **NIK/paspor tidak boleh ikut** — kanal ini di luar kendali kita begitu pesannya terkirim.
- **Tanpa cron dan tanpa queue.** Server tidak bisa mengirim WhatsApp sendiri lewat `wa.me`; tombolnya diklik admin. Ini konsisten dengan D5 dan dengan prinsip "tidak ada worker daemon di shared hosting".
- ✅ Selesai kalau: test — booking yang berangkat besok muncul di antrean, yang lusa tidak; pesan memuat kode booking & titik kumpul; pesan **tidak** memuat nomor identitas peserta.

**(e) Checklist perlengkapan per kategori**
- Kolom JSON `gear_checklist` di `categories`, cast `array`, disunting admin lewat Filament (repeater sederhana). Tetap di kategori — daftar bawaan relatif sama antar trip dalam satu kategori, tidak seperti tingkat kesulitan di (c).
- Ditampilkan di halaman detail trip dan di e-tiket — dua titik tempat orang benar-benar bersiap.
- Dipakai juga oleh pesan reminder H-1 di (d), jadi isinya cukup ditulis sekali.
- ✅ Selesai kalau: test — kategori dengan checklist menampilkannya di detail trip; kategori tanpa checklist tidak meninggalkan blok kosong atau judul menggantung.

**(f) Konfirmasi metode pembayaran sebelum lanjut**
- Panel ringkas tampil di halaman pembayaran yang sudah ada, **sebelum** kode QRIS & form upload bukti dirender — bukan halaman terpisah.
- Isi wajib: (a) alur singkat unggah bukti → verifikasi manual admin → tiket terbit, estimasi waktu dari `config('booking.verification_eta')` yang sama dipakai badge (b); (b) peringatan eksplisit QRIS ini diverifikasi manusia, bukan instan; (c) tautan langsung ke `/kebijakan-privasi` dan `/syarat-ketentuan` (bukan cuma lewat footer); (d) tombol "Saya paham, lanjutkan ke pembayaran".
- Kode QRIS & form upload baru dirender setelah tombol konfirmasi diklik; booking baru tetap wajib konfirmasi ulang (bukan sekali untuk selamanya per user).
- ✅ Selesai kalau: test — guest yang belum konfirmasi tidak bisa melihat kode QRIS; setelah konfirmasi, kode QRIS & form upload tampil normal.

**D7.7 — Filament Resource Trip (masuk V1, setelah D7.6, sebelum D8)**

Menutup gap yang ditemukan 2026-08-24: GUIDE FASE V1 menjanjikan "Admin: CRUD trip", tapi D1-D7 tidak pernah membangun Filament Resource-nya. `CategoryResource` sudah selesai (2026-08-24); sisa yang belum ada: Trip beserta jadwal, tingkat harga, dan galeri. Tanpa ini, menambah trip baru cuma bisa lewat seeder/tinker — operasional mustahil diserahkan ke admin non-developer, dan D8 (mitra ajukan trip) tidak punya fondasi layar untuk ditiru.

Pola wajib mengikuti `app/Filament/Resources/CategoryResource.php` yang sudah tervalidasi di project ini (label Bahasa Indonesia via `$modelLabel`/`$pluralModelLabel`, `navigationIcon` heroicon, slug otomatis dari judul lewat `live(onBlur: true)` + `afterStateUpdated`, `Pages\List/Create/Edit`) — jangan desain ulang dari nol. Panel admin sudah `discoverResources()` di `app/Providers/Filament/AdminPanelProvider.php`, jadi resource baru langsung terdaftar tanpa registrasi manual.

**Berkas yang disentuh**
- `app/Filament/Resources/TripResource.php` + `TripResource/Pages/{ListTrips,CreateTrip,EditTrip}.php` — hub.
- `app/Filament/Resources/TripResource/RelationManagers/SchedulesRelationManager.php` — jadwal, dengan `TripPrice` bersarang lewat `Repeater::relationship('prices')`.
- `app/Filament/Resources/TripResource/RelationManagers/ImagesRelationManager.php` — galeri `trip_images`.
- `database/migrations/*_add_difficulty_level_to_trips_table.php` + `app/Enums/TripDifficulty.php` — kalau belum ada dari D7.6 (c). Skema di §4; enum `pemula`/`menengah`/`lanjutan`, nullable.
- `app/Models/Trip.php` — tambah `difficulty_level` ke `$fillable` + cast enum (kalau migration dibuat di sini).
- `tests/Feature/TripResourceTest.php` — konvensi ikut `tests/Feature/CategoryResourceTest.php` (`Livewire::actingAs(admin())->test(CreateTrip::class)->fillForm([...])->call('create')`).

**Bentuk form Trip (hub)**
- Identitas: `title` (slug otomatis), `slug` (`unique(ignoreRecord: true)`), `category_id` (`Select::relationship('category','name')`, wajib), `vendor_id` (`Select::relationship`, **nullable = milik E-GOTO** — jangan dijadikan wajib, seluruh trip demo sekarang tanpa vendor).
- Isi: `description`, `itinerary`, `includes`, `excludes`, `meeting_point`.
- Publikasi: `status` (Enum `TripStatus`, default `draft`), `is_featured`, `published_at`, `difficulty_level` (Enum `TripDifficulty`, label kalimat manusia "Cocok untuk pemula" sesuai D7.6 (c), boleh kosong).
- `cover_image`: `FileUpload` ke `disk('public')` direktori `trips` — komponen `x-trip-image` merender lewat `Storage::url()` di disk default, jadi jalur yang disimpan harus relatif terhadap disk itu, bukan URL penuh.

**Jadwal + harga bertingkat (`SchedulesRelationManager`)**
- Kolom tabel: `start_date`, `end_date`, `quota`, `booked_count`, `status`, jumlah tingkat harga.
- `booked_count` **read-only di form** (`disabled()`), bukan field bebas — angka itu dikunci `lockForUpdate()` saat booking (§5) dan admin yang mengetiknya manual bisa membuat overbooking senyap.
- `TripPrice` bersarang di form jadwal lewat `Repeater::relationship('prices')` (bukan relation manager terpisah — harga tidak berarti tanpa jadwal induknya): `label`, `price` (numeric, prefix Rp), `min_pax`, `max_pax`.
- Pola `Repeater::relationship()` belum pernah dipakai di project ini (`CategoryResource` cuma pakai `Repeater::simple()`), jadi bentuk state-nya di test wajib dicek langsung, jangan diasumsikan sama dengan repeater simple.

**Galeri (`ImagesRelationManager`)**
- `path` (`FileUpload` disk `public`, direktori `trips`), `sort_order`. Tabel diurutkan `sort_order` — relasi `Trip::images()` memang sudah `orderBy('sort_order')`.

**Batas scope**
- Hanya panel **admin**. Layar mitra mengajukan trip (`pending_review` → approve/tolak) tetap milik D9 — jangan dikerjakan di sini.
- Tidak ada perubahan pada halaman publik selain efek data: trip `published` yang dibuat lewat panel harus tampil, `draft` tetap 404 lewat `scopePublished` yang sudah ada.

✅ Selesai kalau: test — (1) admin membuat trip lengkap dari nol lewat panel (1 kategori + 1 jadwal + 1 tingkat harga) dan datanya tersimpan berikut relasinya; (2) trip berstatus `published` muncul di halaman publik; (3) trip berstatus `draft` tetap 404 bagi tamu. Test ini masuk §9 sebagai nomor 21-23.

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

**Widget chat pihak ketiga** (dipindahkan dari GUIDE "Backlog — Menunggu Giliran" ke fase ini, 2026-08-27)

- Pasang **satu** layanan embed: **Tawk.to atau Crisp** — pilih yang free-tier-nya cukup untuk volume awal. Keputusan penyedia diambil saat blok ini dieksekusi, setelah kuota free-tier keduanya dicek terhadap trafik nyata; memilihnya sekarang berarti menebak.
- Skrip embed dipasang di layout utama customer (`resources/views/components/layouts/app.blade.php`) supaya muncul di semua halaman customer sekaligus, bukan ditempel per halaman.
- **Tidak membangun sistem chat sendiri.** Bukan soal selera: shared hosting tidak bisa menjalankan proses persisten untuk WebSocket (`CLAUDE.md` §1), jadi chat realtime buatan sendiri berarti daemon yang tidak akan pernah hidup di sana.
- Di dalam widget ada tombol **"Lanjut ke WhatsApp"** untuk customer yang ingin pindah kanal. Kalau tautannya perlu dibangun dari sisi aplikasi, lewat `MessagingService` yang sudah ada — jangan menulis ulang URL `wa.me` di view.
- **Batas keras: chat umum/CS saja.** Widget ini **dilarang** dipakai untuk approve/reject pembayaran, penerbitan tiket, atau proses apa pun yang menyentuh uang dan tiket. Semuanya tetap lewat layar verifikasi Filament dari D5 (bukti berdampingan nominal, banner duplikat). Ini **prinsip permanen**, sama persis dengan yang dipegang chatbot FAQ-only di GUIDE — bukan batasan sementara sampai widget-nya matang.
- **Kewajiban privasi.** Isi percakapan, nama, dan kontak yang diketik customer mengalir ke server penyedia widget. Penyedia yang dipilih **wajib** dicantumkan di `/kebijakan-privasi` pada bagian pihak ketiga penerima data (bagian itu sudah ada sejak D7.5). Teksnya ditambahkan saat blok ini dieksekusi.
- ✅ Selesai kalau: widget tampil di semua halaman customer tanpa merusak layout di 3 breakpoint; tombol lanjut ke WhatsApp berfungsi; nama penyedia sudah tercantum di `/kebijakan-privasi`; tidak ada satu pun jalur approve/reject pembayaran atau penerbitan tiket yang bisa dipicu dari dalam widget.

**Web Push notification** (dipindahkan dari GUIDE "Backlog — Menunggu Giliran" ke fase ini, 2026-08-27)

- Dua pemicu saja: **pembayaran berubah jadi `verified`** dan **pengingat H-1**. Bukan kanal baru — dua kabar ini sudah punya jalurnya (halaman Booking Saya, tombol wa.me di antrean admin D7.6 d); push hanya mempercepat sampainya, jadi kalau push gagal tidak ada informasi yang hilang.
- **Menumpang fondasi D7.6.** `public/manifest.json` dan `public/sw.js` sudah terpasang; yang ditambahkan cuma listener `push` + `notificationclick` di service worker yang sama. **Aturan cache D7.6 tidak boleh dilonggarkan** — SW tetap tidak menyentuh dokumen HTML, hanya `/build/` dan `/icons/`.
- **Opt-in, bukan otomatis.** Izin browser diminta lewat aksi sadar customer (tombol "Nyalakan notifikasi" di Booking Saya atau profil), **bukan** `Notification.requestPermission()` yang jalan sendiri saat halaman dibuka. Prompt yang muncul tanpa diminta hampir selalu ditolak, dan penolakan itu permanen per browser — sekali ditolak, kanalnya mati dan tidak bisa diminta ulang lewat kode.
- **Tanpa worker daemon** (`CLAUDE.md` §1). Push dikirim di dalam request yang memang sedang terjadi (admin menekan approve) atau lewat Artisan command yang dipanggil cron `schedule:run` — sama seperti `bookings:expire`. Jangan usulkan queue worker persisten.
- **Batas isi notifikasi:** kode booking, judul trip, waktu. **NIK/paspor tidak boleh ikut** — aturan yang sama dengan pesan reminder H-1 di D7.6 (d). Isi notifikasi melewati server push browser (FCM/Mozilla/Apple), jadi diperlakukan sebagai kanal luar.
- ✅ Selesai kalau: customer yang belum menekan tombol izin tidak pernah melihat prompt notifikasi; sesudah opt-in, pembayaran yang disetujui admin memunculkan notifikasi di perangkat itu; pengingat H-1 sampai lewat push tanpa memuat nomor identitas; test D7.6 #15 tetap hijau (halaman pembayaran masih mengambil data segar).

**D13 — QA & rilis**
- Regression test full alur, cek responsive ulang semua halaman baru, Pint, deploy
- ✅ **Output V1.5:** multi-mitra jalan, promo aktif, rating aktif, jalur private trip ada

**D14 — Riwayat Transaksi & Refund** (2026-08-29)

Menjalankan kebijakan refund yang **sudah tertulis** di `GUIDE.md` bagian "Kebijakan Refund" — bukan membuat kebijakan baru. Tiga opsi saat trip dibatalkan penyelenggara, kuota minimum tidak tercapai, atau force majeure: refund 100%, pindah trip/jadwal, atau masuk waitlist.

- **Halaman customer "Riwayat Transaksi"** (`/riwayat-transaksi`), terpisah dari Booking Saya dengan sengaja: Booking Saya menjawab "trip saya apa saja dan statusnya bagaimana", halaman ini menjawab "uang saya ke mana saja". Dua pertanyaan berbeda — yang pertama dibuka sebelum berangkat, yang kedua saat ada yang perlu dicocokkan atau disengketakan.
- **Tabel `refund_requests`**: `booking_id`, `type` (Enum `RefundType`: `refund_100`/`ganti_trip`/`waitlist`), `status` (Enum `RefundStatus`: `diajukan`/`disetujui`/`ditolak`/`selesai`), `customer_note`, `admin_note`, `processed_by`, `processed_at`, timestamps.
  - **`disetujui` terpisah dari `selesai`** dengan sengaja: menyetujui adalah keputusan, mengirim uang adalah pekerjaan. Menggabungkannya menghapus daftar "sudah disetujui tapi uangnya belum ditransfer" — persis daftar yang paling mahal kalau terlewat.
  - **Tanpa unique index pada `booking_id`**: booking yang pengajuan pertamanya ditolak harus tetap bisa mengajukan ulang dengan opsi berbeda. Pencegahan pengajuan ganda dilakukan di Action dengan `lockForUpdate()`, bukan di skema.
- **`RefundRequestResource` di panel admin** — aksi Setujui / Tolak / Tandai selesai. Alasan **wajib** saat menolak (penjaga yang sama dengan penolakan bukti bayar D5 dan penolakan trip mitra D9). Tidak ada tombol "buat baru": pengajuan datang dari customer, bukan dikarang admin.
- **MITRA TIDAK TERLIBAT sama sekali.** Uang masuk ke rekening E-GOTO, jadi yang mengembalikannya juga E-GOTO. Tidak ada Resource kembar untuk model ini di panel vendor, dan tidak boleh ditambahkan.
- **Halaman admin "Riwayat Customer"** — pembayaran + refund satu customer berdampingan dalam satu layar, read-only. Gunanya sempit dan tajam: saat ada sengketa ("saya sudah transfer", "refund saya belum masuk"), mencarinya lewat dua daftar terpisah yang difilter manual adalah cara paling mudah melewatkan satu baris. Keputusan tetap diambil di antrean masing-masing, supaya tiap perubahan status melewati penjaga yang sama.
- Kursi dilepas saat pengajuan **refund 100% disetujui**, bukan saat uang terkirim — begitu keputusan diambil, peserta itu sudah pasti tidak berangkat. Opsi `ganti_trip` dan `waitlist` **tidak** melepas kursi lewat jalur ini: pemindahannya manual (selisih harga case-by-case per GUIDE), dan booking lama baru dibatalkan setelah penggantinya ada.
- ✅ **Selesai kalau:** customer melihat seluruh pembayaran & refund-nya sendiri dan tidak pernah melihat milik orang lain; opsi karangan ditolak validasi server; pengajuan ganda ditolak selama yang pertama berjalan; penolakan tanpa alasan gagal; `disetujui` tidak bisa dilompati ke `selesai`; mitra dan Manajer Trip sama-sama ditolak 403 dari antrean refund. **Status: selesai, 21 test.**

**D15 — Metode pembayaran kedua: Transfer Bank** (dipindahkan dari GUIDE "Backlog — Menunggu Giliran" ke fase resmi, 2026-08-29)

- **Sejajar QRIS, bukan pengganti.** Customer memilih salah satu di panel konfirmasi metode yang **sudah ada** dari D7.6 (f) — menambah pilihan di layar yang sudah dibaca orang, bukan membuat layar baru yang harus dipelajari lagi.
- Yang berbeda **cuma instruksi yang tampil**: nomor rekening, nama pemilik, nama bank. Nominal unik, kode booking di berita transfer, unggah bukti, `proof_hash` penanda duplikat, dan verifikasi manual admin **identik** dengan jalur QRIS. Tidak ada cabang logika kedua untuk uang — hanya cabang tampilan.
- **Bukan Virtual Account.** VA butuh payment gateway atau sambungan host-to-host ke bank; ini transfer biasa ke satu rekening tetap, dicocokkan manusia lewat mutasi. Tidak butuh NIB, tidak butuh API pihak ketiga — batas yang sama dengan QRIS manual di D5.
- **Tidak ada migration.** Kolom `payments.method` sudah ada sejak D5 (varchar, default `qris`). Yang ditambahkan: Enum PHP `PaymentMethod` (`Qris`, `BankTransfer`) + cast di model `Payment`, pengisi di `StorePaymentProof`, dan kolom metode di antrean verifikasi admin supaya admin tahu mutasi mana yang harus dibuka.
- Config baru di `config/booking.php`: `bank_account_number`, `bank_account_name`, `bank_name` (env `BANK_ACCOUNT_NUMBER`, `BANK_ACCOUNT_NAME`, `BANK_NAME`). **Selama nomor rekening kosong, opsi transfer bank tidak dirender sama sekali** — pola yang sama dengan tombol Google di D3 dan widget chat: kode disiapkan penuh, dinyalakan oleh config.
- Ancaman yang ditutup: metode dikirim dari form, jadi nilainya **wajib** divalidasi terhadap Enum (`Rule::enum`), bukan disimpan apa adanya — kalau tidak, metode karangan masuk ke database dan antrean verifikasi menampilkan instruksi yang tidak pernah ada. Nominal tetap dibaca dari `booking.total_amount`, tidak pernah dari request.
- ✅ Selesai kalau: customer bisa memilih dua metode dan instruksi yang tampil sesuai pilihan; nominal unik sama persis di kedua jalur; admin melihat metode yang dipakai di antrean verifikasi; opsi transfer hilang total saat `BANK_ACCOUNT_NUMBER` kosong; metode karangan ditolak validasi; test pembayaran lama tidak ada yang merah.

**D16 — Operasional & keamanan platform** (2026-08-29, dikerjakan bersamaan dengan D14)

Bukan fitur produk; ini yang membuat produknya tetap bisa dijalankan dan tidak jadi pintu masuk.

- **Header keamanan** lewat satu middleware global (`SecurityHeaders`), bukan per route — satu route yang terlupa adalah satu route tanpa perlindungan. Terpasang `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`; `X-Powered-By` dibuang lewat `header_remove()` **dan** HeaderBag karena `expose_php=On` menambahkannya di level SAPI, di luar jangkauan Symfony.
- **HSTS hanya di koneksi HTTPS** (`$request->secure()`). Terkirim dari `127.0.0.1`, browser mengunci host itu ke HTTPS selama `max-age` dan dev lokal tidak bisa dibuka lagi — penguncian yang tidak bisa dibatalkan dari sisi server. `preload` tidak dipasang karena satu arah.
- **CSP dimulai Report-Only**, ditegakkan lewat `SECURITY_CSP_ENFORCE`. CSP yang ditegakkan sebelum diuji per halaman mematikan panel Filament tanpa suara — pelanggarannya muncul di console browser, bukan di log Laravel. `'unsafe-eval'`/`'unsafe-inline'` terpaksa diizinkan (Alpine mengevaluasi `x-data` saat runtime); yang tetap tertutup dan paling berharga adalah `default-src 'self'`.
- **Verifikasi dua langkah (TOTP) untuk akun staf** — `pragmarx/google2fa` + `bacon/bacon-qr-code`, pustaka yang sama yang dipakai Laravel Fortify di baliknya. Fortify penuh **tidak** dipasang: ia membawa route, view, dan tumpukan autentikasi kedua yang berdampingan dengan alur `/masuk` sejak D3.
  - Opsional per akun. Memaksanya ke seluruh akun staf sekaligus akan mengunci pemilik project keluar begitu migration jalan.
  - Rahasia & kode pemulihan di-cast `encrypted` — aturan yang sama dengan NIK peserta. Rahasia polos membuat dump database setara kunci ke seluruh akun staf.
  - Rahasia baru **disimpan setelah kodenya terbukti cocok**, bukan sebelum: orang yang gagal memindai QR tidak boleh terkunci oleh rahasia yang tidak pernah ada di HP-nya.
  - Delapan kode pemulihan sekali pakai; yang terpakai langsung dibuang. Percobaan dibatasi 5 per 5 menit **per user** (penyerang bisa berganti IP, tidak bisa berganti akun sasaran). Sesi diputar ulang setelah kode benar.
  - Tantangannya route web biasa, bukan halaman Filament — di dalam panel ia ikut tertahan middleware yang sedang menahan orangnya, dan hasilnya pengalihan tanpa henti.
- **Mode maintenance bermerek** (`resources/views/errors/503.blade.php`), dipakai `php artisan down --render="errors::503" --retry=60`. Ditulis berdiri sendiri tanpa `@vite` dan tanpa query: halaman ini harus tampil justru saat aplikasinya tidak bisa diandalkan. Pertanyaan paling panik — "sudah bayar tapi belum diverifikasi" — dijawab di halaman itu sendiri.
- **Backup mandiri** `db:backup`, terjadwal harian 03:15, `.sql.gz` di `storage/app/backups` (di luar `public/` dan di luar repo), retensi 14 hari. Password **tidak pernah** masuk argumen perintah — argumen proses terbaca semua pengguna lewat `ps aux`, yang di shared hosting berarti terbaca tetangga; kredensial lewat berkas sementara 0600 yang dihapus di `finally`. Pelengkap backup Hostinger, bukan pengganti: backup yang hanya ada di panel penyedia punya titik gagal yang sama dengan servernya.
- **Rotasi log** `LOG_STACK=daily`, 14 hari. Sebelumnya stack jatuh ke `single` dan `laravel.log` tumbuh tanpa batas sampai kuota shared hosting habis.
- **Health check** `/up` bawaan Laravel 12 sudah aktif lewat `withRouting(health: '/up')` — dikonfirmasi cukup, tidak perlu endpoint custom.
- ✅ **Selesai kalau:** empat header tampil di halaman publik DAN di panel admin; HSTS tidak pernah terkirim lewat HTTP; CSP masih Report-Only sampai dinyalakan sadar; admin ber-2FA ditahan di `/admin` sampai kodenya benar; admin tanpa 2FA tidak terganggu; `db:backup` menghasilkan `.sql.gz` yang memuat `CREATE TABLE` dan membuang dump lewat retensi. **Status: selesai, 28 test (8 header + 7 maintenance + 13 dua langkah).**

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
12. Booking 13 peserta → ditolak validasi, walau sisa kuota jauh lebih besar
13. Sisa kuota >12 → form tetap membatasi 12 kursi & ajakan private trip tampil
14. Tamu buka `/faq`, `/syarat-ketentuan`, `/kebijakan-privasi` → 200, tanpa redirect login

Test wajib D7.6:

15. Service worker aktif → halaman pembayaran tetap mengambil data segar (tidak tersaji dari cache)
16. Pembayaran `verified` → badge terverifikasi tampil; `pending` → estimasi waktu verifikasi tampil
17. Filter level fisik `pemula` → hanya trip berlevel pemula; tanpa filter → hasil tidak berubah
18. Antrean reminder H-1 → memuat booking yang berangkat besok, tidak memuat yang lusa; pesannya **tidak** memuat NIK/paspor
19. Kategori dengan checklist → tampil di detail trip; kategori tanpa checklist → tidak ada blok kosong
20. Guest/booking baru tidak bisa melihat kode QRIS sebelum melewati layar konfirmasi metode pembayaran; setelah konfirmasi, kode QRIS & form upload tampil

Test wajib D7.7:

21. Admin membuat trip lengkap dari nol lewat panel (1 kategori + 1 jadwal + 1 tingkat harga) → tersimpan berikut relasi jadwal & harganya
22. Trip berstatus `published` yang dibuat lewat panel → muncul di halaman publik
23. Trip berstatus `draft` → tetap 404 bagi tamu

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
4. **Design system diganti 2026-08-14** dari sand/forest/terracotta + Fraunces ke mist/teal/amber + Plus Jakarta Sans, mengikuti logo E-GOTO. Sifat pekerjaannya **rename token warna 1:1**, bukan redesain layout — kelas Tailwind lain tidak disentuh. Yang ikut berubah secara sengaja: hierarki heading dinaikkan (berat + ukuran + tracking), karena kontras serif-vs-sans yang dulu gratis sekarang harus dibuat manual.
5. **Batas 12 peserta per booking & blok D7.5** ditambahkan 2026-08-14 (§5.6 dan §6). D7.5 sengaja bukan D8 supaya penomoran fase V1.5 yang sudah tersebar di dokumen lain tidak ikut bergeser.
6. **Kebijakan refund disederhanakan jadi dua tingkat** 2026-08-14 (revisi kedua hari itu). Versi pertama melompat dari ">H-7" ke "H-3 s/d H-1" sehingga H-6, H-5, dan H-4 tidak diatur, lalu ditambal kalimat "kasus per kasus" — untuk dokumen yang mengikat, itu berarti customer tidak bisa tahu haknya tanpa bertanya dan admin memutuskan tanpa pegangan. Sekarang satu batas di H-7, tanpa zona abu-abu.
7. **Blok D7.6 ditambahkan** 2026-08-14 — lima penyempurnaan yang masuk V1 (PWA, transparansi verifikasi, filter level fisik, reminder H-1, checklist perlengkapan). Nomornya mengikuti pola D7.5 dengan alasan yang sama: menjaga D8–D13 tetap di tempatnya. Blok ini membawa satu pengecualian migration yang disetujui eksplisit, tercatat di dalam bloknya. **Kedua kolomnya didaftarkan ke §4**, bukan cuma hidup di blok fase — supaya pembaca skema tidak perlu tahu nomor fase untuk tahu bentuk tabelnya. Penamaan difinalkan 2026-08-14: `trips.difficulty_level` (pemula/menengah/lanjutan) sengaja per trip, `categories.gear_checklist` sengaja per kategori.

## 12. Aturan Update Dokumen

Keputusan baru yang mengubah scope → update `docs/GUIDE.md` dulu, lalu sesuaikan PLAN. Progres harian dicatat sebagai centang di bagian FASE. PLAN tidak boleh jadi sumber kebenaran scope — itu tugas GUIDE.
