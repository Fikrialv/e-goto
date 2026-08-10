# E-GOTO — CHANGELOG

Entri ringkas tiap iterasi selesai (aturan `CLAUDE.md` §6). Terbaru di atas.

---

## 2026-08-11 — D4–D7: booking, pembayaran, tiket, hardening (V1 fungsional selesai)

**Fondasi (dipakai D4–D7).** Tiga interface yang disiapkan sejak D1 akhirnya punya implementasi V1 dan binding di `AppServiceProvider`: `ManualQrisGateway` (QRIS statis + nominal unik, keabsahan ditentukan admin), `WhatsAppLinkService` (URL `wa.me`, tanpa API/worker), `HmacTicketSigner` (HMAC-SHA256, verifikasi `hash_equals`). Angka alur bayar dipusatkan di `config/booking.php` (batas 2 jam, percobaan nominal unik, path QRIS, nomor WA admin, disk bukti). Logika transaksional tinggal di `app/Actions/` — controller dan Filament hanya pemanggil.

**D4 — Booking.** Form peserta (ketua + anggota) dengan repeater Alpine; aturan identitas ditentukan kategori jadwal, **bukan** nilai yang dikirim browser — kalau `id_type` ikut dipercaya dari form, peserta pendakian tinggal mengirim "none" untuk melewati kewajiban NIK. `CreateBooking` menjalankan semuanya dalam satu transaksi: baris `trip_schedules` dikunci `lockForUpdate` dan kuotanya **dibaca ulang dari baris terkunci** (angka yang dibaca saat halaman dibuka sudah basi), harga diambil ulang dari `trip_prices` sesuai jumlah peserta (kalau beberapa tingkat cocok, yang termurah dipakai), `id_number` lewat cast `encrypted` + `id_number_hash` untuk lookup. Nominal unik dijamin tidak bentrok di antara booking bersubtotal sama yang masih menunggu bayar; 3 digit padat naik ke 4 digit. Kode booking menghindari karakter yang mudah tertukar saat ditulis di catatan transfer (0/O, 1/I/L). `bookings:expire` melepas kursi lewat cron `schedule:run` — bukan queue worker.

**D5 — Pembayaran & verifikasi.** Halaman bayar: kode booking besar, nominal unik ditonjolkan beserta rinciannya, hitung mundur batas waktu, instruksi QRIS. Bukti diunggah **di website** dan disimpan di disk non-publik — berkasnya memuat nama dan nomor rekening pengirim, jadi hanya keluar lewat route yang memeriksa peminta: pemilik booking, atau admin lewat route terpisah ber-`role:admin`. `proof_hash` sha256 menandai bukti kembar (`is_duplicate_flagged`) tapi **tidak menolak otomatis** — bisa jadi salah unggah, keputusan tetap milik manusia. Filament: antrean `PaymentResource` (default tersaring "menunggu", badge navigasi, tanpa create/edit), layar verifikasi menaruh bukti berdampingan dengan nominal seharusnya + banner duplikat, widget dashboard 3 angka. Alasan penolakan wajib, dijaga di Action bukan cuma di form, dan booking kembali ke `pending_payment` dengan batas waktu disegarkan supaya customer sempat unggah ulang.

**D6 — Tiket & check-in.** Approve menerbitkan satu tiket per peserta secara sinkron; penerbitan idempoten (approve dari dua tab tidak menggandakan tiket). Token 32 karakter + HMAC atas `token|kode_booking|participant_id`. **QR berisi token saja, bukan URL** — tangkapan layar tiket yang tersebar tidak membuka alamat apa pun. QR dirender SVG inline (`simplesoftwareio/simple-qrcode`), bukan PNG, supaya tidak bergantung ekstensi `imagick` yang tidak ada di XAMPP maupun shared hosting. Halaman check-in panel vendor: transisi `issued → used` dikunci `lockForUpdate` di dalam transaksi, pesan tolak dibedakan tegas ("tidak valid" vs "sudah dipakai jam X"), sementara token tak dikenal dan tanda tangan salah sengaja memberi pesan **sama** — membedakannya memberi tahu penyerang bahwa tokennya sudah benar. Vendor hanya bisa men-check-in trip miliknya; diperiksa di Action, bukan di UI.

**D7 — Hardening.** Seeder demo jadi 13 trip di 6 kategori (12 published + 1 internasional berstatus draft karena kategorinya masih ditutup), dengan variasi kuota penuh, tinggal sedikit, dan jadwal dekat/jauh. Test penjaga N+1 (`PerformaTest`) dan rate limit (`RateLimitTest`) ditambahkan supaya eager load atau rem yang terhapus gagal keras, bukan diam-diam melambat. Semua model sudah punya `$fillable` eksplisit.

**Hasil verifikasi:** `php artisan test` **73 lulus / 281 assertion**, `migrate:fresh --seed` bersih (13 trip, 22 jadwal), `npm run build` sukses, Pint lulus, `storage/logs` bersih.

**Belum dikerjakan (butuh akun pemilik project):** deploy Hostinger, verifikasi restore backup, penggantian password akun demo. Kredensial Google OAuth sudah dipasang ke `.env` (tombol "Masuk dengan Google" kini muncul).

---

## 2026-08-05 — D3: Auth, login Google, profil & gerbang booking

- **Login Facebook dibatalkan** (keputusan pemilik project). Google jadi satu-satunya login pihak ketiga; `docs/oauth-setup-guide.md`, `GUIDE.md`, `PLAN.md`, `CLAUDE.md` §11, dan komentar migration dibersihkan. Kolom `provider`/`provider_id` tetap (generik), jadi tidak ada migration baru — dan risiko "approval Facebook lama" hilang dari daftar risiko PLAN §11.
- `laravel/socialite` ^5.29 terpasang. Blok `google` di `config/services.php`; `.env`/`.env.example` dapat slot `GOOGLE_CLIENT_ID`/`SECRET` kosong. Selama kosong, tombolnya tidak dirender dan `/auth/google/*` menjawab 404 — lebih baik fiturnya tak terlihat daripada memajang tombol yang pasti error.
- Route auth berbahasa Indonesia: `/masuk`, `/daftar`, `/keluar`, `/profil`, `/profil/lengkapi`, `/profil/lewati`, `/booking-saya`, `/booking/{schedule}`. Nama route internal tetap `login`/`register` karena middleware `auth` bawaan Laravel bergantung pada nama `login`.
- **Gerbang login (PLAN §5.5)** memakai `redirect()->guest()` bawaan Laravel — `url.intended` terisi sendiri, tidak ada kode penyimpan tujuan buatan sendiri. `intended()` sengaja baru dikonsumsi setelah layar "lengkapi profil" selesai atau dilewati; kalau dikonsumsi di controller register, tujuan booking-nya hilang di tengah jalan.
- Keamanan yang ditutup: field register diisi eksplisit (bukan sebar `validated()`) karena `role` ada di `$fillable` — request yang menyelipkan `role=admin` tidak lagi jadi jalur naik hak akses; pesan login gagal seragam untuk semua sebab (anti-enumerasi email); `session()->regenerate()` sesudah login/daftar dan `invalidate()` saat keluar; rate limit 5 percobaan/menit per **email+IP** (bukan IP saja, supaya satu jaringan ber-NAT tidak saling mengunci).
- Login Google: pencarian berurutan `provider_id` → email → buat baru, dibungkus transaksi + `lockForUpdate` karena pasangan provider tidak punya unique constraint. Penautan lewat email menaruh kepercayaan pada verifikasi email Google — risikonya searah dan disebut eksplisit di doc-block. Callback tanpa email ditolak, kegagalan Socialite di-`report()` lalu diarahkan balik dengan pesan netral (bukan stack trace). Driver dipakai stateful supaya parameter `state` OAuth tetap jadi proteksi CSRF-nya.
- Profil: `full_name` wajib, sisanya opsional dan bisa dilewati — data yang benar-benar mengikat (NIK/paspor peserta) baru dikumpulkan di D4, jadi memaksa semuanya di sini hanya menambah gesekan tepat di titik konversi. `phone` disimpan di `users`, sisanya di `customer_profiles`.
- Detail trip: tombol "Booking sekarang" yang sebelumnya disabled diganti CTA **per jadwal** (booking selalu terikat satu `trip_schedule`, dan D4 butuh id-nya). Jadwal penuh tidak dapat tombol; halaman detailnya sendiri tetap publik.
- Komponen baru: `form-field` (label + error + `aria-describedby`), `auth-card`, `google-button` (logo SVG inline — nol request ke domain Google). Header layout dapat blok akun untuk desktop & mobile.
- `BookingController@create` masih kerangka: ringkasan jadwal + kuota + harga, sudah terkunci `auth` supaya gerbang bisa diuji utuh sebelum form pemesanan dibangun. Jadwal lewat/penuh/trip non-published ditolak 404.

**Hasil verifikasi:** `php artisan test` 35 lulus/104 assertion (9 di antaranya D3), `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, `storage/logs` tetap kosong sesudah alur dijalankan. Diuji lewat HTTP nyata: guest `/booking/1` → 302 `/masuk` → POST login → 302 kembali ke `/booking/1`, lalu `/booking/1` `/profil` `/booking-saya` semuanya 200.

---

## 2026-08-05 — D2: Browsing publik (homepage, kategori, detail trip)

- **Design system diputuskan** (blocker PLAN §1 no.2 tertutup): editorial hangat — sand (permukaan), forest (teks/aksen), terracotta (**khusus CTA**), tipografi Fraunces + Inter. Token di `resources/css/app.css` (`@theme`); GUIDE.md dan PLAN.md disesuaikan.
- Font di-self-host lewat npm `@fontsource-variable/*` + Vite, bukan CDN Google Fonts — halaman tidak menunggu domain pihak ketiga.
- 3 route publik **tanpa middleware auth** (PLAN §5.5): `/` (`home`), `/kategori/{category:slug}` (`categories.show`), `/trip/{trip:slug}` (`trips.show`).
- Homepage: hero editorial, Trip Populer (kartu), Jadwal Terdekat (daftar, tanggal ditonjolkan), grid kategori. Tiga blok datanya di-cache 5 menit; menu kategori header/footer lewat view composer + cache 1 jam.
- Halaman kategori: filter tanggal/harga/urutan lewat `TripFilterRequest` (Form Request — seluruh input berasal dari query string), paginasi 12 + `withQueryString()`. Aturan `gte`/`after_or_equal` hanya dipasang kalau field lawannya diisi, supaya filter satu sisi tidak ikut ditolak.
- Detail trip: galeri Alpine (state pakai indeks, path gambar tidak masuk ekspresi JS), akordeon itinerary/termasuk/belum termasuk, daftar jadwal + sisa kuota + harga bertingkat, blok trip terkait. CTA booking sengaja nonaktif dan menyebut statusnya — alurnya baru dibangun D3/D4, tidak menaut ke route yang belum ada.
- Kebocoran ditutup: trip non-`published` → 404, kategori `is_active=false` → 404, jadwal yang sudah lewat tidak dirender. Semua teks dari database dirender lewat escaping Blade `{{ }}` (deskripsi/itinerary nanti diisi mitra — permukaan XSS tersimpan).
- Komponen Blade reusable: `layouts.app`, `trip-card`, `price-tag`, `status-badge`, `empty-state`, `trip-image`, `section-heading`. `trip-image` memakai `loading="lazy"` + fallback gradient lokal (nol request eksternal saat foto belum ada).
- Anti N+1: semua daftar eager-load `category`/`schedules`/`prices`; `trip-card` mengecek `relationLoaded()` sebelum menghitung, jadi tidak memicu query per kartu.
- Factory `Category`/`Trip`/`TripSchedule`/`TripPrice`/`TripImage` + `DemoTripSeeder` (6 trip, ada featured, ada sisa 1 kursi, ada kuota habis). Seeder variatif 10–12 trip tetap milik D7.

**Hasil verifikasi:** `php artisan test` 17 lulus/30 assertion, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, log Laravel bersih setelah dihapus & ketiga halaman diakses ulang, `/` `/kategori/pantai` `/trip/{slug}` → 200 sebagai guest, `/kategori/internasional` → 404.

---

## 2026-08-05 — D1: Scaffold & fondasi

- Laravel 12.64 di-scaffold ke root repo (lewat direktori sementara, karena root sudah berisi dokumen). Database `egoto` di MariaDB, driver Laravel dikunci ke `mysql` agar SQL lokal identik dengan production.
- `QUEUE_CONNECTION=sync` — shared hosting tidak punya worker daemon, job ke driver `database` akan mengantre selamanya tanpa pernah jalan.
- 11 tabel V1 + model dengan `$fillable` eksplisit. `booking_participants.id_number` cast `encrypted` (kolom `text`) dengan `id_number_hash` sha256 terpisah untuk lookup; keduanya masuk `$hidden`. Nominal uang disimpan sebagai rupiah utuh, bukan desimal.
- `trips.vendor_id` sengaja tanpa foreign key — tabel `vendors` baru dibuat di D8, constraint menyusul lewat migration terpisah.
- 7 enum backed-string + 3 interface (`PaymentGateway`, `MessagingService`, `TicketSigner`) tanpa implementasi; implementasi menyusul di D5/D6.
- Middleware `role` (tolak 403, bukan redirect login) terdaftar di `bootstrap/app.php`.
- Filament 3.3: panel `/admin` dan `/vendor`. `User::canAccessPanel()` mencocokkan role dengan panel — tanpa ini Filament mengizinkan setiap user terautentikasi masuk `/admin`.
- Test dijalankan di MySQL (`egoto_testing`), bukan SQLite in-memory bawaan: `lockForUpdate()` adalah no-op di SQLite, sehingga test kuota D4 dan anti double check-in D6 akan lulus palsu.
- Tailwind 4 (bawaan Laravel 12) + Alpine.js via Vite. Build produksi 34,7 kB gzip.
- Seeder 6 kategori (Internasional `is_active=false`) + 3 akun demo lokal.
- Laravel Boost terpasang, MCP terdaftar di `.mcp.json`; Boost menambahkan blok panduannya sendiri ke `CLAUDE.md`.

**Hasil verifikasi:** `migrate:fresh --seed` bersih, `php artisan test` 8 lulus/11 assertion, Pint lulus, log Laravel bersih, `/`, `/admin/login`, `/vendor/login` semua 200.

---

## 2026-08-05 — D0: Persiapan paralel

- Repo di-`git init`, branch `main`, commit awal berisi dokumen perencanaan + `CLAUDE.md` + `.claude/settings.json`.
- `.gitignore` Laravel 12 dipasang di root (siap untuk scaffold D1), plus ignore `.claude/settings.local.json`.
- Folder `Docs/` di-rename jadi `docs/` (lowercase) — menyamakan dengan seluruh rujukan di `CLAUDE.md`/`PLAN.md` dan mencegah path gagal resolve di Hostinger (Linux, case-sensitive).
- `docs/PLAN.md` §0 diperbarui ke kondisi faktual; rujukan `GUIDE (3).md` yang sudah tidak ada diganti `docs/GUIDE.md`.
- `docs/CHANGELOG.md` dibuat (sebelumnya diwajibkan `CLAUDE.md` §6 tapi belum pernah ada).
- `docs/oauth-setup-guide.md` diverifikasi sudah lengkap — tidak ditulis ulang.

**Blocker tercatat:** PHP 8.3, Composer, dan MySQL 8 belum terpasang di mesin dev — D1 (scaffold Laravel) tidak bisa dimulai sebelum itu tersedia.
