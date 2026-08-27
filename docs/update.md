# E-GOTO — Progress Checklist

Status terakhir: **V1 (D0-D7.7) dan V1.5 (D8-D13) selesai secara fungsional.** Loop transaksi utuh (browsing → booking → bayar → verifikasi → e-tiket → check-in), marketplace mitra jalan (onboarding, pengajuan trip, tinjauan admin, daftar peserta, rating), promo aktif (voucher + opsi tambahan), dan jalur private trip ada. Sisa yang belum selesai semuanya butuh akun/mata pemilik project: deploy Hostinger, verifikasi restore backup, cek responsive manual, ikon PWA & QRIS asli, kredensial widget chat + Web Push. Centang tiap item selesai. Dokumen ini dipakai terus-menerus — jangan dihapus/diganti, cukup update.

## Sesi Terakhir (WAJIB diupdate tiap akhir sesi Claude Code CLI)

- **Tanggal/waktu sesi terakhir:** 2026-08-27
- **Sesi keenam 2026-08-27 (Sesi 0 + Sesi 1 EXECUTION_PROMPTS):** (1) Environment: database `egoto` + `egoto_testing` dibuat ulang dari kosong (dump database lama `e_goto` disimpan di luar repo — lihat "Cara menyalakan environment lokal" poin 5-6), `migrate:fresh --seed` bersih, **83 test lulus / 314 assertion**. (2) `CLAUDE.md` §10 dapat pola keempat: larangan ikon dekoratif di header/section title + rujukan pola header yang benar (label kapital kecil di atas judul tebal). (3) Dokumen: blok **D7.7 — Filament Resource Trip** ditulis ke `PLAN.md` §6 (setelah D7.6, sebelum FASE V1.5) berisi berkas yang disentuh, bentuk form hub/jadwal/galeri, batas scope (panel admin saja, layar mitra tetap D9), kriteria selesai; test wajib 21-23 masuk §9. Kode belum disentuh — eksekusinya Sesi 3.
- **Sesi ketujuh 2026-08-27 (Sesi 2 — D7.6 dieksekusi ke kode):** Enam item selesai. (a) PWA: `public/manifest.json`, `public/sw.js` (cache **hanya** `/build/` + `/icons/`, navigasi selalu lewat jaringan), registrasi di `resources/js/app.js`, ikon 192/512 dibuat lewat encoder PNG minimal karena GD tidak aktif di XAMPP laptop ini — **ganti dengan artwork logo asli sebelum publish**. (b) Badge status pembayaran + estimasi `config('booking.verification_eta')` di halaman bayar & Booking Saya, plus tombol unduh gambar QRIS. (c) Migration `trips.difficulty_level` + Enum `TripDifficulty` + filter radio di panel filter kategori (label kalimat manusia). (d) Halaman admin **Pengingat H-1** (`app/Filament/Pages/ReminderKeberangkatan.php`) + `MessagingService::remindDayBefore()` di `WhatsAppLinkService` — tujuan wa.me nomor pemesan, pesan tanpa NIK/paspor. (e) Checklist perlengkapan tampil di detail trip & e-tiket, dan ikut di pesan reminder. (f) Panel konfirmasi metode pembayaran: QRIS & form unggah baru dirender setelah tombol ditekan, per kode booking (booking baru wajib konfirmasi ulang). Seeder ikut diisi level & checklist demo. Verifikasi: **101 test lulus / 387 assertion**, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, tidak ada entri log baru.
- **Sesi kedelapan 2026-08-27 (Sesi 3 — D7.7 dieksekusi ke kode):** `TripResource` (form 3 seksi: identitas, isi halaman, publikasi) + `SchedulesRelationManager` (jadwal, `TripPrice` bersarang lewat `Repeater::relationship()`) + `ImagesRelationManager` (galeri) jadi — gap CRUD trip yang ditemukan 2026-08-24 tertutup. Dua keputusan yang perlu diingat: `booked_count` `disabled()` + `dehydrated(false)` (angka itu hanya boleh bergerak lewat alur booking yang mengunci baris jadwal — ada test yang mengirim 12 dan memastikan tersimpan 0), dan `vendor_id` diisi dari daftar user berperan vendor lewat `->options()`, **bukan** `->relationship()`, karena tabel `vendors` baru lahir di V1.5 dan kolom ini akan berpindah induk. **Temuan pola:** `Repeater::relationship()` menerima data test berbentuk list berindeks angka **hanya kalau** repeater-nya `defaultItems(0)`; dengan baris bawaan, state-nya berkunci uuid dan data test justru menambah baris kedua sehingga baris bawaan yang kosong gagal validasi. Karena itu repeater harga dipasang `defaultItems(0)` + `minItems(1)` — jadwal tanpa tingkat harga ditolak. Verifikasi: **106 test lulus / 430 assertion**, `migrate:fresh --seed` bersih, Pint lulus, tidak ada entri log baru. **Koreksi 2026-08-27 (sesudah review):** field `vendor_id` di form `TripResource` disembunyikan (`Hidden` + `dehydrated(false)`) — dropdown user berperan vendor dicabut karena kolom ini nanti merujuk `vendors.id`, bukan `users.id`; lihat bagian "TODO menunggu fase berikutnya" di bawah. Test jadi **107 lulus / 434 assertion**.
- **Sesi kesembilan 2026-08-27 (dokumen saja, kode tidak disentuh):** Item **In-app chat widget** dipindahkan dari GUIDE "Backlog — Menunggu Giliran" ke fase resmi **D12** (gabung dengan private trip & polish). PLAN §7 blok D12 dapat sub-blok lengkap: pilih satu penyedia embed (Tawk.to/Crisp) saat eksekusi, dipasang di layout utama customer, tombol "Lanjut ke WhatsApp" di dalam widget, dan larangan membangun sistem chat sendiri (shared hosting tidak bisa jalankan proses persisten WebSocket). Dua batasan ditulis eksplisit karena inilah yang menentukan aman-tidaknya: (1) widget **hanya** chat umum/CS — approve/reject pembayaran & penerbitan tiket tetap wajib lewat layar verifikasi Filament D5, prinsip permanen yang sama dengan chatbot FAQ-only; (2) data yang lewat widget mengalir ke server pihak ketiga, jadi nama penyedia **wajib** dicantumkan di `/kebijakan-privasi` bagian pihak ketiga penerima data saat D12 dikerjakan. Checklist D12 di dokumen ini + prompt Sesi 7 di EXECUTION_PROMPTS ikut disesuaikan.
- **Sesi kesepuluh 2026-08-27 (dokumen saja + audit, kode tidak disentuh):** (1) **Web Push notification** dipindahkan dari GUIDE "Backlog — Menunggu Giliran" ke **D12** — menumpang `manifest.json` + `sw.js` dari D7.6, dua pemicu saja (pembayaran `verified` & pengingat H-1), **opt-in** lewat tombol customer (bukan prompt otomatis — penolakan browser bersifat permanen), tanpa worker daemon, isi notifikasi tanpa NIK/paspor. (2) `CLAUDE.md` §6 dapat aturan **commit & push berkala**: CLI tidak commit sendiri, tapi wajib menyebutkan jumlah berkas belum ter-commit di bagian "Perlu diupdate:"/"Langkah selanjutnya:" begitu tumpukan lewat ~15 berkas atau satu sesi penuh. (3) **Audit form sejak D7.7 — laporan saja, belum diperbaiki.** Tiga temuan menunggu keputusan Anda, lihat "Temuan audit form (belum diputuskan)" di bawah.
- **Sesi kesebelas 2026-08-27 (perbaikan hasil audit + sesi login):** Empat perbaikan. (1) `min_pax` di repeater harga jadi `required()` — kolomnya NOT NULL, sebelumnya bisa dikosongkan saat edit dan berakhir jadi galat database. (2) `trip_schedules.status` pindah dari TextInput teks bebas ke `Select` berbasis Enum `TripStatus` + `Rule::enum()` + cast di model `TripSchedule`; migration `normalize_trip_schedules_status` merapikan nilai lama yang di luar enum. Catatan pola: daftar `->options()` saja **tidak** menolak nilai asing yang dikirim langsung ke Livewire — tanpa `Rule::enum()`, yang meledak justru cast Enum saat baris dibaca, jauh dari asal masalahnya. (3) Trip tidak bisa naik ke `published` tanpa minimal satu jadwal — dijaga di `CreateTrip::beforeCreate()` dan `EditTrip::beforeSave()` dengan notifikasi Filament persistent yang menyebut langkah berikutnya, lalu `halt()`. Konsekuensi alur: trip baru **wajib** disimpan sebagai draf dulu (jadwal memang baru bisa diisi setelah record induk ada), baru dipublikasikan. (4) `SESSION_LIFETIME` 120 → 240 menit supaya tidak lagi persis sama dengan `BOOKING_EXPIRY_MINUTES` (120) — customer tidak lagi ter-logout tepat saat kembali membayar mendekati batas booking. Verifikasi: **112 test lulus / 468 assertion**, `migrate:fresh --seed` bersih, Pint lulus.
- **Sesi kedua belas 2026-08-27 (Sesi 4 — D8 onboarding mitra):** Halaman publik `/jadi-mitra` (kriteria, benefit, langkah, form pengajuan; tertaut dari footer) + `PartnerController` + `StoreVendorApplicationRequest` (dokumen dibatasi jpg/png/webp/pdf 4 MB, maks 5 berkas, disimpan di disk **non-publik** lewat `config/partner.php`) + rate limit `pengajuan-mitra` (3/menit per email+IP — form ini terbuka tanpa login dan menerima unggahan). Migration `vendors` + `vendor_applications` beserta model, factory, dan Enum `VendorStatus` yang sudah ada. Admin: `VendorApplicationResource` (list-only, tanpa form create/edit karena barisnya lahir dari form publik) dengan aksi **Lihat** (infolist + tautan dokumen lewat route ber-`role:admin`), **Jadwalkan** (meeting_at + catatan), **Setujui**, **Tolak** (alasan wajib). `ApproveVendorApplication` membuat profil `vendors` + akun panel dalam satu transaksi: akun baru dapat password acak yang ditampilkan sekali ke admin (tidak ada email transaksional di V1.5), sedangkan email yang sudah punya akun dinaikkan perannya jadi vendor — bukan digandakan. Verifikasi: **122 test lulus / 524 assertion** (10 test baru di `PartnerOnboardingTest`), `migrate:fresh --seed` bersih, Pint lulus. **Sesi 5-8 (D9-D13) belum dikerjakan** — berhenti di sini karena batas token.
- **Sesi ketiga belas 2026-08-27 (Sesi 5 — D9 loop mitra aktif):** Panel mitra dapat `TripResource` sendiri (form tanpa field publikasi; `getEloquentQuery()` disaring ke `vendor_id` milik user, jadi trip mitra lain tidak pernah ditemukan — bukan ditemukan lalu ditolak) + `BookingResource` read-only berisi booking trip miliknya (NIK/paspor sengaja tidak ditampilkan; mitra butuh nama & kontak, bukan nomor identitas). Status yang boleh disentuh mitra dibatasi `draft` ↔ `pending_review`, ditegakkan `Rule::in` — daftar `->options()` saja tidak menolak nilai yang dikirim langsung ke Livewire, pola yang sudah ketahuan di D7.7. Mitra juga tidak bisa mengajukan trip tanpa jadwal. Admin: aksi **Setujui** (menolak kalau trip belum punya jadwal) dan **Tolak** dengan `review_note` wajib di `TripResource`, plus badge jumlah pengajuan menunggu; catatan penolakan tampil kembali di form mitra. Migration `trips.review_note` + `reviewed_by` + `reviewed_at`. Verifikasi: **131 test lulus / 575 assertion** (9 test baru di `VendorTripTest`), Pint lulus.
- **Sesi keempat belas 2026-08-27 (Sesi 6 — D10 voucher & opsi trip):** Empat tabel baru (`vouchers`, `voucher_usages`, `trip_options`, `booking_options`) + Enum `VoucherType`/`VoucherScope`. Validasi voucher dikumpulkan di satu Action `ApplyVoucher` supaya checkout dan layar lain tidak pernah punya versi aturan berbeda; yang dikirim form cuma kode, nilai potongan dihitung ulang dari database. **Urutan hitung yang dikunci:** harga opsi masuk `subtotal` dulu, potongan voucher dihitung dari subtotal itu, nominal unik ditempel paling akhir — supaya nominal unik tetap jadi pembeda terakhir yang dicocokkan admin dengan mutasi bank (PLAN §5.1). `booking_options.unit_price` membekukan harga opsi saat booking dibuat: mitra menaikkan harga besok tidak mengubah total yang sudah disepakati. Pemakaian voucher dicatat di transaksi yang sama dengan booking-nya, plus unique key `(voucher_id, booking_id)` sebagai penjaga terakhir kalau dua request checkout tiba bersamaan. Booking yang kedaluwarsa/dibatalkan tidak menghanguskan kesempatan pakai voucher. Verifikasi: **15 test baru di `VoucherOptionTest`**, Pint lulus.
- **Sesi kelima belas 2026-08-27 (Sesi 7 — D11 + D12):** **D11:** tabel `reviews`; rating 1-5 + komentar dari booking `completed`, satu per booking dengan unique key di database (validasi controller saja kalah balapan kalau tombol ditekan dua kali). Tampil di detail trip (rata-rata + daftar dipaginasi); panel mitra read-only — moderasi hanya di admin supaya penilaian buruk tidak bisa dihapus oleh yang dinilai; admin bisa sembunyikan/tampilkan ulang tapi **tidak bisa menyunting isi review**. **D12:** halaman `/private-trip` (form ringkas → `wa.me` lewat `MessagingService`, tidak menyimpan data), teaser mitra di homepage + profil, widget chat pihak ketiga di layout utama yang **tidak dirender sama sekali** selama `CHAT_WIDGET_ID` kosong (pola tombol Google), tombol "Lanjut ke WhatsApp" di dekatnya, penyedia widget otomatis muncul di `/kebijakan-privasi` saat aktif. Web Push: tabel `push_subscriptions`, endpoint daftar/berhenti, tombol opt-in di Booking Saya yang **hanya** meminta izin browser setelah ditekan, listener `push` + `notificationclick` di `sw.js` tanpa melonggarkan aturan cache D7.6. **Pengiriman push belum aktif** — `composer require minishlink/web-push` gagal di mesin ini karena `ext-gd` belum diaktifkan di php.ini; sesudah itu tinggal isi kunci VAPID. Verifikasi: **170 test lulus / 706 assertion**, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus.
- **Sesi keenam belas 2026-08-27 (Sesi 8 — D13 QA & rilis):** Regression penuh V1+V1.5 hijau, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, test penjaga N+1 (`PerformaTest`) tetap lolos. `docs/hostinger.md` dibuat: langkah deploy manual bernomor dari PHP/ekstensi, database, penempatan kode, document root (`app/public` — bukan disiasati dengan memindah isi `public/`), `.env` production, izin folder, cron `schedule:run` tiap menit, redirect URI Google production, cache production, sampai daftar periksa manual sesudah rilis dan catatan backup. Catatan log: ada satu `testing.ERROR` deadlock MySQL saat dua proses test berjalan bersamaan di database test yang sama — bukan bug aplikasi, hilang di run tunggal. **Sisa V1.5 semuanya butuh akun/mata Anda:** deploy Hostinger, verifikasi restore backup, cek responsive manual, ganti ikon PWA & berkas QRIS asli, dan (opsional) aktifkan pengiriman Web Push + widget chat lewat kredensial masing-masing.
- **Sesi kelima 2026-08-24:** (1) Dokumen: klarifikasi kriteria deploy di GUIDE.md+update.md (V1+V1.5 selesai, bukan seluruh Backlog); D7.6 dapat item ke-6 "Konfirmasi metode pembayaran sebelum lanjut" (+ test #20 di PLAN §9); Backlog Menunggu Giliran dapat item baru "Transfer bank sebagai metode pembayaran kedua" dan bullet "Pemecahan permission admin" diperjelas jadi 2 pembagian tugas (bukan 3 role); Backlog Ditunda Sadar dapat alasan konkret kenapa WhatsApp Business API ditunda (Meta Business Verification setara App Review yang membatalkan Login Facebook); bullet "Chatbot FAQ-only" dapat prinsip permanen (approve/reject & tiket wajib tetap di layar verifikasi Filament D5). (2) Kode: **gap CRUD trip ditemukan** — GUIDE menjanjikan "Admin: CRUD trip" tapi Filament Resource-nya nihil total (Category, Trip, TripSchedule, TripPrice, TripImage). `CategoryResource` dibangun sebagai bagian pertama (migration `gear_checklist`, form+table lengkap, `Repeater::simple()` dengan `->defaultItems(0)`), test hijau. `TripResource` + RelationManagers **sengaja ditunda ke sesi berikutnya** — lihat bagian "Gap ditemukan 2026-08-24" di bawah untuk detail sisa kerjanya. Verifikasi: **83 test lulus / 314 assertion**, `migrate:fresh --seed` bersih, Pint lulus, log bersih.
- **Sedang mengerjakan (arsip, sebelum sesi kelima):** **Selesai: 4 keputusan baru pemilik project + D7.5.** (1) Design system diganti ke identitas teal logo (`mist`/`teal`/`amber`, Plus Jakarta Sans + Inter) — rename token 1:1 di 20 berkas, panel Filament ikut teal, hierarki heading dinaikkan karena kontras serif hilang. (2) Cap keras 12 peserta per booking, ditegakkan di server + UI + pesan yang mengarahkan ke private trip. (3) Dua opsi pembayaran (lunas/DP) dicatat di GUIDE sebagai keputusan disetujui tapi ditunda sampai V1 live — belum dibangun. (4) D7.5: halaman `/faq`, `/syarat-ketentuan`, `/kebijakan-privasi` + tautan footer. Verifikasi: **81 test lulus / 302 assertion**, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, log bersih.
- **Sesi kedua 2026-08-14 (dokumen saja, kode tidak disentuh):** kebijakan refund direvisi jadi **dua tingkat** (celah H-6/H-5/H-4 di versi pertama ditutup, batas tunggal di H-7) dan masuk GUIDE sebagai bagian "Kebijakan Refund"; **blok D7.6** ditambahkan ke PLAN berisi 5 penyempurnaan (PWA, badge verifikasi + ETA, filter level fisik, reminder H-1, checklist perlengkapan) beserta kriteria selesai + test 15–19. Dua item menunggu eksekusi ke kode — lihat bagian "⚠ Perlu dieksekusi ulang ke kode" di bawah.
- **Sesi ketiga 2026-08-14 (dokumen saja):** penamaan kolom D7.6 difinalkan sebelum ada kode yang menyentuhnya — `trips.difficulty_level` (enum pemula/menengah/lanjutan, melekat per trip) dan `categories.gear_checklist` (json, per kategori). Keduanya didaftarkan ke tabel skema PLAN §4 lengkap dengan catatan kenapa yang satu di `trips` dan satunya di `categories`, jadi tidak perlu membaca blok fase untuk tahu bentuk skemanya. Belum ada migration — kedua kolom masih murni dokumen.
- **Sesi keempat 2026-08-14 (dokumen saja):** `CHANGELOG.md` menyusul dua sesi yang belum tercatat (revisi refund dua tingkat + blok D7.6; finalisasi nama kolom D7.6). GUIDE dapat bagian baru **"Backlog — Menunggu Giliran"** sebagai rumah ~35 ide yang sudah disetujui konsepnya tapi belum dijadwalkan — sebelumnya cuma hidup di percakapan dan berisiko dikira sudah ditolak. Sekalian membereskan tiga bentrokan dengan daftar "Ditunda Sadar": push notification dipindah ke Menunggu Giliran (tidak boleh ada di dua daftar berlawanan), AI Assistant vs chatbot FAQ-only dan Web Builder vs Theme Preset Switcher dibedakan eksplisit di kedua tempat, dan GPS satelit pendaki ditambahkan ke Ditunda Sadar supaya rujukan "beda dari live tracking kendaraan" punya acuan nyata.
- **Angka yang masih sementara (WAJIB divalidasi sebelum publish):** refund batal >H-7 = 50% dikurangi biaya admin flat Rp25.000; H-7 ke bawah tanpa refund; retensi NIK/paspor = akun aktif + 2 tahun. Sudah ditandai `[SEMENTARA — validasi sebelum publish]` di dalam teks halaman S&K/Privasi, dan ada test yang gagal kalau penandanya hilang sebelum angkanya final.
- **Sesi 2026-08-11 (sebelumnya):** **D4, D5, D6, D7 SELESAI dalam satu sesi.** Booking (form peserta adaptif NIK/paspor/none, kunci kuota `lockForUpdate`, nominal unik, `expires_at` 2 jam, command `bookings:expire`), pembayaran (QRIS + nominal unik, unggah bukti ke disk non-publik, `proof_hash` penanda duplikat, tombol `wa.me`), verifikasi admin di Filament (antrean + badge + widget + layar bukti-berdampingan-nominal, alasan tolak wajib), tiket (HMAC + QR SVG berisi token, e-tiket branded, check-in panel vendor anti pakai-ganda), dan hardening (seeder 13 trip, test penjaga N+1 + rate limit). Verifikasi: **73 test lulus / 281 assertion**, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, log bersih.
- **Catatan sesi ini:** D2 + D3 ternyata sudah dikerjakan di worktree `d2-browsing-publik` tapi belum pernah masuk `main` — sudah di-fast-forward, worktree-nya dihapus. Kredensial Google OAuth yang Anda tempel di `docs/oauth-setup-guide.md` sudah dipindah ke `.env` dan doc dikembalikan ke placeholder (kredensial jangan disimpan di file yang ter-track git). Tombol "Masuk dengan Google" sekarang muncul.
- **Langkah persis berikutnya:** D7.6 dan D7.7 sudah selesai — sisa V1 tinggal **deploy Hostinger** (butuh akun Anda, lihat blocker di bawah) lalu **D8 (V1.5 — onboarding mitra, Sesi 4 di EXECUTION_PROMPTS)**. Sebelum D8, sempatkan dua hal yang tidak bisa dijamin test: (1) buka `php artisan serve`, lihat halaman customer di 3 breakpoint — termasuk panel konfirmasi pembayaran & filter level fisik yang baru; (2) jalankan alur uang sekali manual: pesan trip → konfirmasi metode → unggah bukti → approve di `/admin` → buka e-tiket → check-in di `/vendor` (login `vendor@egoto.test`; set dulu `trips.vendor_id` ke id user vendor karena trip demo milik E-GOTO). Sekalian cek layar baru: `/admin/trips` (CRUD trip) dan `/admin/reminder-keberangkatan` (pengingat H-1). Ikon PWA masih bentuk sementara — ganti dengan artwork logo asli sebelum publish.
- **Cara menyalakan environment lokal (WAJIB tiap sesi baru):**
  1. MariaDB XAMPP harus jalan dulu — nyalakan lewat XAMPP Control Panel, atau: `Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini","--standalone" -WindowStyle Hidden`. Kalau lupa, semua perintah artisan gagal dengan error 2002.
  2. PHP tidak ada di PATH global — pakai `C:\xampp\php\php.exe` atau tambahkan `C:\xampp\php` ke PATH sesi.
  3. Akun demo lokal: `admin@egoto.test` / `vendor@egoto.test` / `customer@egoto.test`, password semuanya `password`.
  4. Database: `egoto` (aplikasi) dan `egoto_testing` (khusus test, dipakai phpunit.xml).
  5. Klien MySQL/mysqldump juga tidak ada di PATH — pakai `C:\xampp\mysql\bin\mysql.exe` dan `C:\xampp\mysql\bin\mysqldump.exe`. Tidak ada instalasi MariaDB terpisah di laptop ini (bukan `C:\Program Files\MariaDB\...`), semuanya lewat XAMPP. MySQL tidak dipasang sebagai Windows service — startup tetap manual seperti poin 1.
  6. **2026-08-27:** database `egoto` + `egoto_testing` dibuat ulang dari kosong lalu `migrate:fresh --seed`. Database lama `e_goto` (skema pra-D4, 7 tabel) sudah di-dump ke `C:\Users\fikri\db-backups\e_goto_2026-08-27.sql` — di luar repo, sengaja tidak ikut git.
- **Ada blocker/perlu keputusan Anda:**
  1. ~~Keputusan design system (PLAN §1 no.2)~~ — **diputuskan ulang & final 2026-08-14**: identitas teal logo — `mist` (permukaan) + `teal` (teks/aksen) + `amber` (khusus CTA), Plus Jakarta Sans + Inter. Menggantikan keputusan 2026-08-05 (editorial hangat sand/forest/terracotta + Fraunces) yang sudah dieksekusi lalu dimigrasikan. Detail di GUIDE.md bagian Design System.
  2. ~~Kredensial Google OAuth belum ditempel~~ — **selesai 2026-08-11**: sudah masuk `.env`, tombol "Masuk dengan Google" muncul. Redirect URI lokal `http://localhost:8000/auth/google/callback`; **tambahkan URI production di Google Console saat domain aktif**. Jangan tempel kredensial lagi ke file di dalam `docs/` — itu ter-track git.
  3. **Hosting Hostinger + verifikasi restore backup belum dikerjakan (butuh akun Anda).** Ini satu-satunya sisa D7. Termasuk di dalamnya: `APP_DEBUG=false` + `APP_ENV=production` di server, cron `schedule:run` tiap menit (wajib — tanpa itu booking kedaluwarsa tidak pernah melepas kuota), ganti password akun demo, dan tempel berkas QRIS merchant asli menggantikan `public/images/qris-placeholder.svg` (atau arahkan `QRIS_IMAGE_PATH` ke berkas baru). Isi juga `ADMIN_WHATSAPP` dengan nomor admin sungguhan. Kriteria mulai deploy: **V1 + V1.5 (D0-D13) selesai** — bukan menunggu "Backlog — Menunggu Giliran" atau "Model bisnis baru" di GUIDE.md tuntas, karena keduanya memang open-ended dan tidak pernah "tuntas" secara desain.
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

## Gap ditemukan 2026-08-24 — Filament Resource Trip/Category (harusnya bagian D1-D7)

GUIDE.md FASE V1 menjanjikan "Admin: CRUD trip", tapi prompt harian D1-D7 tidak pernah membangun Filament Resource-nya — dikonfirmasi nihil total (Category, Trip, TripSchedule, TripPrice, TripImage). Ditutup bertahap sebelum lanjut D8, karena operasional trip mustahil tanpa developer buka tinker/seeder manual.

- [x] `CategoryResource` — CRUD kategori lengkap dengan checklist perlengkapan (`Repeater`), selesai 2026-08-24. Test: `tests/Feature/CategoryResourceTest.php`.
- [x] Rencana resmi ditulis 2026-08-27 — sisa kerja ini sekarang punya blok **D7.7** di `docs/PLAN.md` §6 (berkas yang disentuh, bentuk form, batas scope, kriteria selesai) + test wajib nomor 21-23 di §9. Blok itu yang jadi acuan eksekusi, bukan catatan gap ini.
- [x] `TripResource` (hub) + `RelationManagers\SchedulesRelationManager` (jadwal, dengan `TripPrice` bersarang lewat `Repeater::relationship()`) + `RelationManagers\ImagesRelationManager` (galeri) — **selesai 2026-08-27** (D7.7). Migration `trips.difficulty_level` + Enum `TripDifficulty` sudah lebih dulu masuk lewat D7.6.
- [x] Test wajib `TripResource` hijau: admin create trip lengkap (1 kategori + 1 jadwal + 1 tingkat harga) dari nol lewat panel; trip published muncul di halaman publik; trip draft tetap 404. Berkas: `tests/Feature/TripResourceTest.php` (5 test).

## TODO menunggu fase berikutnya (bukan bug, sengaja ditunda)

- [ ] **`vendor_id` di `TripResource` disembunyikan sampai D8/D9 selesai.** Sekarang `Forms\Components\Hidden` + `dehydrated(false)` — form tidak pernah menulis kolom ini, dan ada test `tidak menyimpan vendor_id yang dikirim dari form trip` yang menjaganya. Alasan: di V1 semua trip milik E-GOTO (`vendor_id` null), dan setelah V1.5 kolom ini merujuk **`vendors.id`**, bukan `users.id` — kalau admin sempat memilih user vendor demo dari dropdown sekarang, angkanya jadi rujukan salah begitu tabel `vendors` dibuat. Begitu D8/D9 selesai: ganti jadi `Select::make('vendor_id')->relationship('vendor', 'business_name')`, tambah relasi `Trip::vendor()`, dan hapus test penjaga itu.

## Temuan audit form (audit 2026-08-27 — ketiganya sudah diperbaiki hari yang sama)

Audit `TripResource` + relation manager yang dibangun di D7.7. Field wajib inti (judul, slug, kategori, status, tanggal berangkat, kuota, minimal 1 tingkat harga, label & harga per tingkat, path foto) semuanya sudah `required`; field opsional (deskripsi, itinerary, includes/excludes, titik kumpul, cover image, tingkat kesulitan, tanggal pulang, tanggal publikasi, `max_pax`) benar tidak memblokir submit. Tiga hal yang belum beres — menunggu keputusan Anda sebelum disentuh:

- [x] **`min_pax` di repeater harga tidak `required`, tapi kolomnya NOT NULL di database** (`trip_prices.min_pax` `unsignedSmallInteger`->`default(1)`). Field-nya hanya punya `->default(1)` di form. Kalau admin mengosongkan isian itu saat menyunting, yang terkirim `null` dan bentrok dengan kolom NOT NULL — galat database, bukan pesan validasi yang bisa dibaca. Perbaikan paling murah: `->required()` (default 1 tetap terisi) atau `->dehydrateStateUsing(fn ($state) => $state ?: 1)`.
- [x] **Trip bisa disimpan tanpa satu pun jadwal.** Jadwal & harga hidup di relation manager yang baru muncul setelah trip tersimpan, jadi "minimal 1 jadwal" tidak bisa ditegakkan di form create — dan tidak ada penjaga lain. Akibatnya trip berstatus `published` tanpa jadwal: halaman detailnya terbuka (menampilkan "Belum ada jadwal terbuka") tapi tidak muncul di halaman kategori dan tidak bisa dipesan. Pilihan perbaikan: penjaga saat status diubah ke `published`, atau badge peringatan di tabel trip.
- [x] **`trip_schedules.status` masih TextInput teks bebas** (`required`, default `published`, `maxLength(50)`). Salah ketik tidak tertangkap. Belum berdampak — tidak ada query yang menyaring kolom ini — tapi kalau nanti dipakai, salahnya diam. Sejalan dengan aturan Enum di `CLAUDE.md` §4, seharusnya Enum PHP + `Select`.

## D7.5 — Halaman legal & bantuan

- [x] `/faq` — pertanyaan spesifik alur E-GOTO (nominal unik, jeda verifikasi, cap 12 peserta, QR sekali pakai)
- [x] `/syarat-ketentuan` — refund bertingkat (a/b/c/d) + tanggung jawab platform vs mitra + kewajiban peserta
- [x] `/kebijakan-privasi` — NIK/paspor terenkripsi, retensi, pihak ketiga penerima data, hak customer
- [x] Angka belum final ditandai `[SEMENTARA — validasi sebelum publish]` di dalam teks halaman
- [x] Tautan ketiganya di kolom "Bantuan" footer
- [x] Test: guest buka ketiga URL → 200 tanpa redirect; footer menautkan ketiganya; penanda `[SEMENTARA]` dijaga test
- [ ] Angka refund/biaya admin/retensi diganti nilai final — **butuh keputusan Anda, sebelum publish**

## D7.6 — Penyempurnaan sebelum rilis

- [x] **PWA installable**
  - [x] `public/manifest.json` (ikon 192/512, `display: standalone`, theme `#077C82`)
  - [x] Service worker minimal — cache aset statis saja; booking/pembayaran/tiket **jangan** di-cache
  - [x] Registrasi SW di `resources/js/app.js`, berkas SW di root domain
- [x] **Badge "Verified Payment" + estimasi waktu verifikasi**
  - [x] Badge di halaman pembayaran & "Booking Saya" (pakai `PaymentStatus` + komponen `status-badge` yang sudah ada)
  - [x] Estimasi dari `config('booking.verification_eta')`, bukan angka di Blade
  - [x] Tombol unduh gambar QRIS di halaman pembayaran (bayar dari app mobile banking terpisah tanpa scan langsung)
- [x] **Filter level fisik**
  - [x] Kolom `trips.difficulty_level` + Enum `TripDifficulty` (pemula/menengah/lanjutan) — di `trips`, **bukan** `categories` (alasan di PLAN §4)
  - [x] Opsi filter menempel ke panel filter kategori yang sudah ada
- [x] **Reminder H-1**
  - [x] Halaman antrean di panel admin (booking `confirmed`, berangkat besok)
  - [x] `MessagingService::remindDayBefore()` — jangan bikin service baru
  - [x] Pesan memuat titik kumpul + itinerary ringkas + checklist; **tanpa NIK/paspor**
- [x] **Checklist perlengkapan per kategori**
  - [x] Kolom JSON `categories.gear_checklist` (cast `array`, tanpa query JSON-path) — selesai 2026-08-24 via `CategoryResource`
  - [x] Editor di Filament admin — `Repeater::simple()`, `->defaultItems(0)` karena opsional
  - [x] Tampil di detail trip & e-tiket, dipakai ulang oleh reminder H-1
- [x] **Konfirmasi metode pembayaran sebelum lanjut**
  - [x] Panel konfirmasi tampil sebelum kode QRIS & form upload — alur unggah→verifikasi→tiket + estimasi waktu
  - [x] Peringatan eksplisit verifikasi manual (bukan instan)
  - [x] Tautan langsung ke `/kebijakan-privasi` & `/syarat-ketentuan` (bukan cuma via footer)
  - [x] Tombol "Saya paham, lanjutkan ke pembayaran" sebelum QRIS/form upload dirender
- [x] Test 15–20 (PLAN §9) hijau — 101 test lulus / 387 assertion, 2026-08-27

---

## ⚠ Perlu dieksekusi ulang ke kode (dokumen sudah berubah, kode belum)

Dokumen diperbarui 2026-08-14 sesi kedua, **tanpa menyentuh kode sama sekali** (sesuai permintaan). Halaman yang sudah dibangun **tidak ikut berubah** hanya karena GUIDE/PLAN diedit — dua item berikut masih memuat versi lama:

- [ ] **Tabel refund di `/syarat-ketentuan` masih versi lama.** `resources/views/pages/syarat-ketentuan.blade.php` masih memakai tingkat "H-3 sampai H-1" plus paragraf "H-7 sampai H-4 diputuskan kasus per kasus". Ganti ke dua tingkat sesuai GUIDE bagian "Kebijakan Refund": >H-7 → 50% − Rp25.000; H-7 ke bawah → tanpa refund, tawarkan reschedule kalau vendor punya slot. Hapus paragraf kasus-per-kasus.
  **Catatan:** test `menandai angka refund dan retensi yang belum final` **tetap hijau** walau tabelnya usang — test itu hanya memeriksa keberadaan penanda `[SEMENTARA]`, bukan isi tabel. Jadi tidak ada yang otomatis mengingatkan; baris inilah pengingatnya.
- [ ] **Bug copy di detail trip — prioritas sebelum D8.** `resources/views/pages/trip-detail.blade.php` menulis *"Kuota dikunci setelah pembayaran diverifikasi"*, padahal PLAN §5.3 menetapkan kuota dikunci **sejak booking dibuat** (`lockForUpdate` + `expires_at` 2 jam) dan baru dilepas kalau booking kedaluwarsa. Kalimat sekarang membuat customer mengira kursinya belum aman sampai admin menyetujui — persis kebalikan dari yang sistem lakukan, dan bisa memicu pemesanan ganda "untuk berjaga-jaga".

---

**✅ V1 selesai kalau semua di atas tercentang — ini syarat sebelum lanjut V1.5.**
**Status: seluruh bagian yang bisa dikerjakan tanpa akun hosting sudah selesai; sisanya menunggu server, plus 1 item angka legal yang menunggu keputusan Anda.**

---

## D8 — Onboarding mitra

- [x] Halaman publik "Jadi Mitra E-GOTO" (`/jadi-mitra`, tertaut dari footer)
- [x] Form pengajuan + upload dokumen (disk non-publik, throttle `pengajuan-mitra`)
- [x] Admin: daftar pengajuan (`VendorApplicationResource`), jadwal meeting, catatan, tolak dengan alasan wajib
- [x] Approve → akun vendor + profil `vendors` otomatis dibuat (`ApproveVendorApplication`), password sementara ditampilkan sekali

## D9 — Loop mitra aktif

- [x] Vendor ajukan trip lewat panel mitra → status `pending_review` (`app/Filament/Vendor/Resources/TripResource.php`)
- [x] Query mitra di-scope ke `vendor_id` sendiri; pilihan status dibatasi draf ↔ diajukan (`Rule::in`)
- [x] Admin approve/tolak di `TripResource` — approve butuh minimal 1 jadwal, tolak butuh `review_note` wajib
- [x] Panel mitra: kolom jumlah peserta + daftar booking trip sendiri (`Vendor\Resources\BookingResource`, read-only)
- [x] Notifikasi booking baru = badge navigasi panel mitra (belum ada kanal push/email di V1.5)

## D10 — Voucher, promo, combo, opsi trip

- [x] CRUD voucher di admin (`VoucherResource`) — percent/fixed, min spend, kuota, masa berlaku, scope global/kategori/trip
- [x] Checkout: potongan masuk `bookings.discount_amount`, pemakaian tercatat di `voucher_usages` dalam transaksi yang sama
- [x] Validasi voucher terpusat di `ApplyVoucher` — kedaluwarsa, belum berlaku, nonaktif, kuota habis, min spend, cakupan, dobel pakai per user
- [x] `trip_options`: CRUD lewat `OptionsRelationManager` (dipakai panel admin & mitra), tampil di detail trip + checkout
- [x] Harga opsi masuk `subtotal` sebelum potongan voucher dan sebelum nominal unik ditempel; `unit_price` dibekukan di `booking_options`

## D11 — Rating & komentar

- [x] Booking `completed` → rating 1-5 + komentar, satu per booking (unique `booking_id` di database)
- [x] Tampil di detail trip: rata-rata + daftar dipaginasi (bagian "Kata peserta")
- [x] Vendor melihat rating tripnya sendiri (read-only; review hidden tidak ikut tampil)
- [x] Admin bisa menyembunyikan/menampilkan ulang review — isinya tidak bisa disunting siapa pun

## D12 — Private trip & polish

- [x] Form request private trip → `wa.me` terisi otomatis lewat `MessagingService`, tanpa menyimpan data
- [x] Teaser "Jadi Mitra E-GOTO?" di homepage & profil customer
- [x] Widget chat pihak ketiga (Tawk.to/Crisp) di layout utama — **tidak dirender** selama `CHAT_WIDGET_ID` kosong
- [x] Tombol "Lanjut ke WhatsApp" di dekat widget
- [x] Batas keras ditegakkan: widget hanya chat umum/CS — approve/reject & tiket tetap lewat Filament
- [x] Penyedia widget dicantumkan di `/kebijakan-privasi` saat aktif
- [x] Web Push opt-in: tabel `push_subscriptions`, tombol izin di Booking Saya, listener `push`/`notificationclick` di `sw.js`
- [ ] **Pengiriman push belum jalan** — butuh `ext-gd` diaktifkan di php.ini, lalu `composer require minishlink/web-push` + kunci VAPID di `.env`

## D13 — QA & rilis V1.5

- [x] Regression test penuh V1 + V1.5 hijau (`php artisan test`)
- [x] `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus
- [x] `docs/hostinger.md` dibuat — langkah deploy manual yang dirujuk `CLAUDE.md` §17
- [ ] Cek responsive manual semua halaman baru di 3 breakpoint — **butuh mata Anda**, bukan test
- [ ] Deploy Hostinger — **butuh akun Anda** (lihat `docs/hostinger.md`)
- [ ] Verifikasi backup Hostinger benar-benar bisa di-restore

## Belum dimulai (V2 — menunggu keputusan komisi)

- [ ] Skema komisi 3-10% ditetapkan (flat/tiered per kategori)
- [ ] Dashboard pemasukan vendor interaktif
- [ ] Modul lain sesuai feedback nyata V1.5

### Backlog — Menunggu Giliran (daftar lengkap di GUIDE.md)

Ide yang **konsepnya sudah disetujui tapi belum masuk fase mana pun** dikumpulkan di `GUIDE.md` bagian **"Backlog — Menunggu Giliran"**, terbagi 5 kelompok: fitur customer, fitur mitra/vendor, operasional & admin, pembayaran & bisnis, serta model bisnis baru.

Daftarnya sengaja **tidak disalin ke sini** — dua daftar panjang di dua berkas berbeda pasti akan berbeda isi cepat atau lambat, dan tidak ada yang tahu mana yang benar. GUIDE tetap satu-satunya tempatnya.

**Aturan pakai:** item di sana **belum dijadwalkan**. Jangan dikerjakan sebelum pemilik project memindahkannya ke fase (D-sekian) lebih dulu. Beda dengan "Backlog — Ditunda Sadar" di GUIDE, yang isinya memang sengaja tidak dibangun.

## Keputusan masih menggantung (lihat GUIDE.md & PLAN.md bagian 1)

- [ ] Trip internasional: dummy aktif untuk uji coba, atau tetap tutup?
- [x] Design system: **final 2026-08-14** — identitas teal logo (mist/teal/amber, Plus Jakarta Sans + Inter)
- [ ] Angka refund (>H-7 = 50%), biaya admin Rp25.000, retensi data — masih sementara, wajib final sebelum publish
- [x] Struktur tingkat refund: **selesai 2026-08-14** — dua tingkat, batas tunggal di H-7, tidak ada lagi zona "kasus per kasus"
- [ ] Persentase DP & tenggat pelunasan — dibutuhkan sebelum eksekusi dua-opsi-pembayaran (setelah V1 live)
- [ ] Skema komisi platform: flat atau tiered per kategori?

## Catatan teknis penting (jangan diabaikan)

- Batas peserta per booking dibaca dari `config('booking.max_pax_per_booking')` — jangan tulis ulang angka 12 di controller/view/test, nanti bercabang
- Warna: `amber-600` sengaja lebih gelap dari `amber-500` demi kontras tombol berteks putih — jangan ditukar tanpa mengukur ulang (lihat komentar di `resources/css/app.css`)
- Queue TIDAK dipakai di V1 — expired booking pakai Scheduled Command, bukan queue job (lihat CLAUDE.md bagian 8, PLAN.md 12b)
- NIK/paspor wajib `encrypted` cast + `id_number_hash` terpisah untuk lookup
- Akun demo HANYA untuk lokal — wajib diganti sebelum ada user asli
