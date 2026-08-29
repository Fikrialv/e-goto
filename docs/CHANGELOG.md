# E-GOTO — CHANGELOG

Entri ringkas tiap iterasi selesai (aturan `CLAUDE.md` §6). Terbaru di atas.

---

## 2026-08-29 — Sesi 12: QRIS bernominal, slider diperbaiki, ikon monokrom

**204 test / 825 assertion** hijau (14 test baru), Pint lulus, `npm run build` sukses, grep warna terlarang nol temuan.

**Bug fatal hero slider diperbaiki.** Sebelumnya hanya slide pertama yang berada di aliran normal dan slide 2 ke atas `absolute inset-0`; begitu `aktif` berpindah dari 0, pembungkusnya kehilangan satu-satunya anak beraliran normal, tingginya jadi 0, dan **seluruh hero lenyap dari layar** — itulah yang membuat slidernya terasa tidak jalan. Sekarang rasionya dipegang pembungkus dan semua slide absolut. Bug ikutan yang ikut hilang: `<figcaption>` slide pertama tidak punya induk berposisi, jadi posisinya beda dengan slide lain.

**Slider diperbesar.** Kolom slider `lg:col-span-7` (teks `lg:col-span-5`, sebelumnya 6/6), rasio `4/3` → `sm:3/2` → `lg:16/10` + `lg:min-h-[30rem]`.

**Logo dipakai di header dan footer**, menggantikan teks `E·GOTO`: `Logo1.svg` `h-8` di header, `h-7` di footer, `h-6` + `brightness-0 invert` di header e-tiket. `width`/`height` eksplisit supaya tidak ada layout shift.

**Ikon jadi monokrom dan disebar ke alur transaksi.** Bintang rating dan bullet amber diganti teal; aturan barunya: ikon selalu mewarisi warna teks dari tangga teal, amber cuma milik tombol dan penanda urgensi. Ikon ditambahkan ke `booking-create`, `payment`, `booking-saya`, `e-ticket`, `category`, `jadi-mitra`, dan `private-trip` — sembilan halaman yang sebelumnya nol ikon.

**QRIS bernominal (dinamis) disiapkan.** `QrisDynamicPayload::untukNominal()` membaca payload EMVCo QRIS statis merchant, mengubah tag `01` dari `11` jadi `12`, menyisipkan nominal booking ke tag `54`, mengurutkan ulang tag tingkat atas, dan menghitung ulang CRC16 tag `63`. Parsing/encoding TLV lewat `phumin/promptparse` (MIT, EMVCo generik) — bukan parser tulis sendiri, dan bukan paket QRIS mikro yang unduhannya di bawah 50 dan belum teruji siapa pun.

Ini **bukan** payment gateway: rekening tujuan sama, tidak ada API pihak ketiga, tidak butuh NIB, verifikasi tetap manual admin. Nominal dibaca dari `booking.total_amount`, tidak pernah dari request. Payload dihitung saat halaman bayar dirender, tidak disimpan — ia sepenuhnya turunan kolom yang sudah beku sejak booking dibuat.

**Fitur mati sampai `QRIS_STATIC_PAYLOAD` diisi.** Nilainya harus dipindai sendiri dari QRIS merchant (gambar tidak bisa disisipi nominal — yang dibutuhkan string EMVCo-nya). Selama kosong, atau kalau payloadnya gagal verifikasi CRC, halaman bayar jatuh balik ke gambar statis + nominal diketik manual. QR yang cacat baru ketahuan di depan kasir, jadi kegagalannya sengaja mundur ke jalur lama.

**Jebakan yang dicatat:** `TLV::encode()` milik promptparse menulis sub-tag DAN value induknya sekaligus, jadi payload tergandakan kalau di-parse dengan `subTags: true`. Semua parsing di project ini memakai `subTags: false`.

**Dokumen.** `docs/PLAN.md` dapat blok **D14 — Metode pembayaran kedua: Transfer Bank** (manual, bukan Virtual Account; `payments.method` sudah ada jadi tanpa migration; config `BANK_ACCOUNT_NUMBER`/`BANK_ACCOUNT_NAME`/`BANK_NAME`; opsi tidak dirender selama nomor rekening kosong). Entri transfer bank dipindahkan dari Backlog GUIDE ke fase resmi V1.5. **Kodenya belum disentuh** — blok ini dokumen saja. `CLAUDE.md` §10 dapat aturan permanen kelima: ikon tidak diwarnai.

---

## 2026-08-28 — Sesi 11: latar putih, hero slider, aturan desain permanen

**190 test / 786 assertion** hijau (6 test baru), Pint lulus, `npm run build` sukses. CSS turun **109 KB → 94 KB** (gzip 19,4 → 15,5) karena `welcome.blade.php` dibuang; JS tetap 95 KB.

**Latar jadi putih murni.** Token `--color-background: #ffffff` ditambahkan dan `body` memakainya, menggantikan `mist-50`. Semua kartu `bg-white/70` → `bg-white` di 11 berkas: transparansi 70% di atas permukaan bertint itulah yang bikin kartu terbaca kotor, bukan lembut. Input dan dropdown ikut putih. Pita `mist-100` **dipertahankan** untuk selang-seling seksi — token `mist` sudah dingin (bertint teal), jadi ia memberi jeda tanpa menghangatkan halaman.

**Satu blok krem betulan ditemukan dan diperbaiki:** `pages/payment.blade.php` memakai `bg-amber-50/60`. `amber-50` tidak ada di blok `@theme` project, jadi Tailwind memakai palet bawaannya yang hangat kekuningan — persis kesan yang dilarang. Panelnya jadi `bg-white` + border amber; perhatian sekarang datang dari garis dan eyebrow, bukan dari bidang kuning muda. Dua aside peringatan di halaman S&K dan Kebijakan Privasi mendapat perlakuan sama.

**`backdrop-blur` dibatasi ke panel halaman masuk.** Sebelumnya bocor ke header sticky, chip di atas foto kartu trip, dan caption hero. Blur yang bertebaran adalah penanda cepat "template generik". Chip juga ganti tone: info netral sekarang `teal-50`, urgensi tetap amber solid.

**`resources/views/welcome.blade.php` dihapus.** Berkas bawaan Laravel yang tidak pernah dirutekan (`/` mengarah ke `HomeController` sejak D1), isinya dump CSS Tailwind lengkap dengan violet/purple/indigo. Ini satu-satunya sumber temuan grep warna terlarang selain blok amber-50 di atas.

**Hero slider** (`x-hero-slider`) — satu-satunya bangunan baru sesi ini. Slide dari trip `is_featured` terbit, maksimal 5. Di bawah 2 slide komponennya merender hero statis, bukan slider berdot tunggal yang tidak menuju ke mana-mana. Auto-advance 5 detik, transisi fade + geser 4px (250ms), berhenti saat hover **dan** saat ada elemen di dalamnya menerima fokus keyboard. Auto-advance **tidak pernah menyala** kalau `prefers-reduced-motion` aktif — dot tetap bisa diklik manual, jadi mematikan gerakan tidak berarti mematikan isinya. Alpine + CSS saja; nol library carousel pihak ketiga.

Selama sampul belum diunggah, tiap slide memakai ikon fallback dari `categories.icon` masing-masing — lima slide tidak tampil sebagai lima bidang kembar. Ada test yang memeriksa dua path SVG berbeda benar-benar hadir di halaman.

**`x-icon-circle`** dapat hover lift (angkat 2px + bayangan naik) yang dipasang di komponennya, bukan diulang di tiap pemanggil.

**Favicon** memakai `logo2.svg` lewat `<link rel="icon" type="image/svg+xml">` di kedua layout dan di kedua panel Filament, dipasang di atas tautan PNG lama supaya browser modern memakainya. Ikon PWA di `manifest.json` masih PNG 192/512 bawaan D7.6 — manifest butuh raster, dan merasterisasi SVG butuh Imagick (GD tidak bisa membaca SVG). Tidak ada berkas logo ketiga di repo; peran ikon persegi diambil `logo2.svg` sesuai keputusan pemilik project.

**Aturan permanen masuk `CLAUDE.md` §10** supaya sesi berikutnya tidak mengulang: latar wajib putih + daftar token krem yang dilarang berikut alasan teknisnya, larangan violet/purple/indigo sebagai elemen dominan (moodboard hanya untuk pola layout — warnanya selalu diganti token E-GOTO), aturan pemilihan berkas logo per konteks, dan larangan library carousel/animasi pihak ketiga. Perintah grep verifikasinya ditulis langsung di aturan itu. `docs/DESIGN_SYSTEM.md` dan bagian Design System `docs/GUIDE.md` disesuaikan.

---

## 2026-08-28 — Sesi 10: fallback state + rekonsiliasi dokumen desain

Lanjutan Sesi 9. **184 test / 747 assertion** hijau (3 test baru), Pint lulus, `npm run build` sukses (CSS 109 KB, JS 95 KB — tidak berubah, fallback murni CSS).

**Fallback state.** Sampul trip diunggah mitra belakangan, jadi bidang foto kosong adalah keadaan normal — bukan kerusakan, tapi sebelumnya terbaca begitu: hero, panel halaman masuk, dan seksi mitra sama sekali tidak dirender kalau belum ada foto, menyisakan halaman yang terlihat setengah jadi. Sekarang satu komponen menanganinya di empat titik: `x-media-fallback` — gradasi `mist-100 → mist-200 → teal-200` plus satu ikon Lucide 48–64px beropasitas `text-teal-700/25`, ikonnya menyesuaikan konteks (gunung di hero, peta di panel masuk, kamera di kartu trip, jabat tangan di seksi mitra).

Yang sengaja tidak dipakai: foto stok dari sumber mana pun, dan abu-abu polos. Yang pertama adalah ciri paling cepat terbaca "dibuat AI" (`CLAUDE.md` §10); yang kedua membuat halaman terbaca rusak alih-alih sedang menunggu konten. Ada test yang menjaga keduanya — `assertDontSee('unsplash')` dan pemeriksaan kelas gradasi + opasitas ikon.

Konsekuensi kecil yang perlu dicatat: hero homepage sekarang **selalu** merender kolom fotonya (jatuh ke fallback kalau kosong), dan `x-trip-image` tidak lagi menggambar ilustrasi pegunungannya sendiri — cabang kosongnya diserahkan ke komponen bersama supaya kekosongan terlihat sebagai satu keputusan, bukan empat tambalan berbeda.

**Gradient dapat satu pengecualian.** `docs/DESIGN_SYSTEM.md` melarang gradient; bidang fallback adalah satu-satunya tempat ia diizinkan, karena justru gradasi itu yang membedakan bidang yang memang dirancang kosong dari kartu berlatar solid di sekitarnya. Pengecualiannya ditulis eksplisit di kedua dokumen, bukan dibiarkan jadi kebiasaan tak tertulis.

**Rekonsiliasi dokumen.** Bagian Design System di `docs/GUIDE.md` masih berhenti di keputusan 2026-08-14 (warna + tipografi) dan belum menyebut apa pun dari Sesi 9. Sekarang ditambah empat sub-bagian: sistem ikon (Lucide lewat Blade, dan kenapa `codeat3` diganti `mallardduck`), prinsip animasi lengkap dengan aturan `prefers-reduced-motion` untuk animasi berulang, pemakaian dua berkas logo, dan fallback state. Ditutup penegasan hubungan kedua dokumen: GUIDE ringkasan keputusan, DESIGN_SYSTEM detail teknis, dan kalau bentrok **GUIDE yang menang**.

Tiga bagian `DESIGN_SYSTEM.md` yang ternyata sudah tidak cocok dengan kodenya ikut diperbaiki: splash dilepas pada `DOMContentLoaded` (dokumennya masih menulis `window.load`), `Logo1.svg` juga dipakai sebagai `brandLogo` panel Filament, dan panel kanan halaman masuk sekarang punya jalur fallback.

**`docs/hostinger.md`** — catatan `ext-gd` di Langkah 1. Bukan sekadar "pastikan aktif", tapi kenapa: `simplesoftwareio/simple-qrcode` (QR e-tiket sejak D6) mensyaratkannya, dan tanpa itu Composer menolak memasang paket apa pun sambil menyebut nama paket yang salah di pesan galatnya. Ditambah perintah pemeriksaannya di server (`php -m | grep -i '^gd$'`) dan urutannya: nyalakan sebelum `composer install`, bukan sesudah.

---

## 2026-08-28 — Sesi 9: redesign visual & component system

Sesi tampilan murni. Tidak ada alur, logic, atau skema transaksi yang berubah — **181 test / 737 assertion** hijau (170 lama + 11 baru), `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus.

**Ikon.** `codeat3/blade-lucide-icons` yang direncanakan sudah tidak ada di Packagist; penggantinya `mallardduck/blade-lucide-icons` 2.0.8 (prefix `lucide`). `blade-ui-kit/blade-icons` dan `blade-heroicons` ternyata sudah ikut terpasang lewat Filament, jadi tidak ada dependensi ikon kedua yang perlu ditambah. Ikon dirender PHP-side lewat komponen Blade — bundle JS tidak bertambah sama sekali (95 KB, gzip 35 KB).

Kolom `categories.icon` yang sejak D7 ditandai "belum dipakai" akhirnya dipakai: jadi ikon di grid kategori homepage. Field-nya di `CategoryResource` diubah dari teks bebas jadi `Select` berbasis `Category::ICON_OPTIONS` — daftar tertutup, karena nilai karangan akan melempar `SvgNotFound` di halaman publik, bukan di panel tempat mengetiknya.

**Komponen baru.** `x-badge-chip` (chip di atas foto kartu trip), `x-icon-circle`, `x-stat-bar`, `x-avatar-cluster` (inisial, bukan foto — kolom foto profil belum ada), `x-glass-input` (khusus halaman masuk/daftar; form booking tetap `x-form-field` supaya form transaksi seragam), `x-auth-split`, `x-loading-splash`, `x-submit-overlay`, plus layout `x-layouts.auth`.

**Halaman masuk & daftar** jadi dua kolom di desktop, satu kolom di ponsel. Panel kanan memuat foto trip terbit dan kutipan review asli lewat View composer — kalau database masih kosong, bagian itu hilang sendiri, tidak diisi contoh karangan. Toggle tampil/sembunyi kata sandi pakai Alpine, bukan komponen React dari referensi. Logic Socialite tidak disentuh.

**Baris statistik homepage** menghitung tiga angka dari database (jadwal lampau yang punya booking terkonfirmasi, mitra `approved`, peserta pada booking terkonfirmasi/selesai) dan **menyembunyikan seksinya kalau salah satu masih nol** — ada test yang menjaga keduanya. Rata-rata rating di kartu trip datang dari `withCount`/`withAvg` sebagai subquery; kartu di halaman yang tidak menghitungnya tidak menampilkan apa-apa, jadi tidak ada query per kartu yang menyelinap masuk.

**Carousel review** di detail trip pakai Alpine `x-transition` (fade + geser kecil). Seluruh ulasan halaman itu tetap ada di DOM supaya terbaca pembaca layar dan ketemu Ctrl+F; paginasi lama tetap di bawahnya. Daftar "Sudah/Belum termasuk" dipecah per baris dengan ikon check/x — rencana perjalanan sengaja dibiarkan paragraf utuh.

**Loading screen** memakai logo bentuk (`logo2.svg`) dengan pulsing scale. Bukan stroke-dasharray: berkas logonya hasil trace berisi ratusan path *fill*, bukan garis, jadi teknik "menggambar garis" tidak berlaku. Splash dilepas pada `DOMContentLoaded` (bukan `window.load` yang menunggu semua gambar), punya `<noscript>` penyembunyi dan timer penjaga, dan animasinya dimatikan penuh — bukan dipercepat — saat `prefers-reduced-motion` aktif.

**Panel Filament.** `AccountWidget` dan `FilamentInfoWidget` dicabut dari dashboard admin dan mitra (yang kedua memajang versi Filament + tautan dokumentasi ke staf yang tidak berkepentingan). Panel dapat `brandName`, `brandLogo`, dan favicon E-GOTO. Sembilan Resource dapat empty state kontekstual — ikon, judul, dan kalimat langkah berikutnya per layar, menggantikan satu ikon generik yang sama di mana-mana.

**Aset logo.** Berkas SVG dari trace 343 KB dan 384 KB dioptimasi jadi ±74 KB dan ±79 KB dengan `viewBox` ditambahkan supaya bisa diskalakan; salinan asli disimpan di luar repo.

**Catatan lingkungan:** `ext-gd` diaktifkan di `C:\xampp\php\php.ini` (backup `php.ini.bak-20260828`) — tanpa itu Composer menolak memasang paket apa pun karena `simplesoftwareio/simple-qrcode` mensyaratkannya. Ini juga blocker yang menggagalkan `minishlink/web-push` di sesi sebelumnya.

---

## 2026-08-27 — D13: QA & berkas deploy

Regression penuh V1 + V1.5 hijau: **170 test / 706 assertion**, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, dan test penjaga N+1 di `PerformaTest` tetap lolos setelah semua fitur V1.5 masuk.

`docs/hostinger.md` akhirnya ada — berkas yang dirujuk `CLAUDE.md` §17 sejak awal tapi belum pernah ditulis. Isinya langkah bernomor dari PHP 8.3 + ekstensi, database, penempatan kode lewat SSH, document root ke `app/public`, `.env` production, izin folder, cron `schedule:run` tiap menit, redirect URI Google untuk domain production, cache production, sampai daftar periksa manual sesudah rilis. Dua hal ditulis tegas karena akibatnya besar dan sunyi: `APP_DEBUG=false` (dengan `true`, halaman galat menampilkan isi `.env`), dan cron yang wajib ada (tanpa itu `bookings:expire` tidak pernah jalan dan booking kedaluwarsa terus menahan kuota).

Catatan pengujian: sempat muncul satu `testing.ERROR` deadlock MySQL ketika dua proses test berjalan bersamaan di database test yang sama. Bukan bug aplikasi — hilang begitu suite dijalankan tunggal.

---

## 2026-08-27 — D11 + D12: rating, private trip, widget chat, Web Push opt-in

**D11 — rating & komentar.** Hanya booking `completed` milik sendiri yang bisa dinilai, satu review per booking dengan unique key di database: validasi controller saja kalah balapan kalau tombol ditekan dua kali. Detail trip menampilkan rata-rata dan daftar review yang dipaginasi. Panel mitra melihat rating tripnya sendiri secara read-only — moderasi sengaja hanya ada di admin, supaya penilaian buruk tidak bisa dihapus oleh pihak yang dinilai. Admin bisa menyembunyikan dan menampilkan ulang, tapi tidak ada jalur untuk menyunting isi review: mengubah kalimat orang lain lalu menampilkannya sebagai ucapannya tidak boleh bisa dilakukan dari panel mana pun.

**D12 — private trip.** Halaman `/private-trip` merangkai isian jadi pesan WhatsApp lewat `MessagingService` dan tidak menyimpan apa pun ke database — rombongan di atas batas peserta per booking (PLAN.md §5.6) sekarang punya jalur yang jelas. Teaser "Jadi Mitra E-GOTO?" dipasang di homepage dan profil customer.

**D12 — widget chat.** Skrip embed Tawk.to/Crisp dipasang di layout utama, dibungkus config: selama `CHAT_WIDGET_ID` kosong, tidak ada satu baris pun yang dirender — pola yang sama dengan tombol Google sebelum kredensialnya masuk. Tombol "Lanjut ke WhatsApp" berdiri di dekatnya lewat `MessagingService::generalEnquiry()`, bukan URL yang ditulis ulang di view. Batas kerasnya ditulis di komentar komponen dan config: widget ini hanya untuk tanya-jawab umum/CS, approve/reject pembayaran dan penerbitan tiket tetap wajib lewat layar verifikasi Filament D5. Saat widget aktif, penyedianya otomatis muncul di `/kebijakan-privasi` sebagai pihak ketiga penerima data, lengkap dengan peringatan agar NIK dan data pembayaran tidak diketik di kotak chat.

**D12 — Web Push (opt-in).** Tabel `push_subscriptions` (satu baris per browser, endpoint di-hash untuk kunci unik), endpoint daftar/berhenti, dan tombol "Nyalakan notifikasi" di Booking Saya yang hanya muncul setelah kunci VAPID dipasang. Izin browser diminta **hanya** di dalam handler tombol itu — ada test yang menjaga `Notification.requestPermission()` tetap di sana dan tidak dipanggil saat halaman dimuat, karena prompt yang muncul sendiri hampir selalu ditolak dan penolakan itu permanen per browser. Listener `push` dan `notificationclick` ditambahkan ke service worker yang sama tanpa menyentuh aturan cache D7.6; test cache-nya dipertajam supaya memeriksa isi fungsi penentu cache, bukan sekadar mencari kata di seluruh berkas.

**Yang belum jalan:** pengiriman push. `composer require minishlink/web-push` ditolak di mesin dev ini karena `ext-gd` belum aktif di php.ini. Langkahnya: aktifkan ekstensi itu, pasang paketnya, isi `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY`. Langganan, service worker, dan UI-nya sudah siap menerima.

Verifikasi: **170 test lulus / 706 assertion**, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus.

---

## 2026-08-27 — D10: voucher & opsi tambahan

Empat tabel baru: `vouchers`, `voucher_usages`, `trip_options`, `booking_options`. Enum `VoucherType` (percent/fixed) dan `VoucherScope` (global/kategori/trip) — nilai yang menentukan cara menghitung uang tidak boleh berupa string bebas.

**Validasi voucher terpusat di `ApplyVoucher`.** Kedaluwarsa, belum berlaku, nonaktif, kuota habis, minimal belanja, cakupan salah, dan dobel pakai per user semuanya diputuskan di satu tempat, jadi checkout tidak bisa punya versi aturan yang berbeda dari layar lain. Form hanya mengirim kode; nilai potongannya dihitung ulang dari database.

**Urutan hitung dikunci dan diuji:** harga opsi masuk `subtotal` → potongan voucher dihitung dari subtotal itu → nominal unik ditempel paling akhir. Kalau urutannya tertukar, nominal unik berhenti menjadi pembeda terakhir yang dicocokkan admin dengan mutasi bank (PLAN.md §5.1). Potongan juga dicap di subtotal — voucher tidak boleh membuat total negatif.

**Harga opsi dibekukan** di `booking_options.unit_price` saat booking dibuat: mitra menaikkan harga besok tidak mengubah total yang sudah disepakati. Pemakaian voucher dicatat di dalam transaksi yang sama dengan booking-nya, dengan unique key `(voucher_id, booking_id)` sebagai penjaga terakhir kalau dua request checkout tiba bersamaan. Booking yang kedaluwarsa, dibatalkan, atau ditolak tidak menghanguskan kesempatan pakai — kesempatannya memang tidak jadi terpakai.

`used_count` sengaja tidak bisa disunting admin: angkanya bergerak bersama transaksi checkout, dan mengetiknya manual akan membuat kuota promo berbeda dari pemakaian sebenarnya.

Opsi tambahan dikelola lewat `OptionsRelationManager` yang dipakai panel admin dan panel mitra sekaligus, tampil di detail trip dan di checkout (jumlah dibatasi jumlah peserta — opsi dijual per orang).

Verifikasi: 15 test baru di `VoucherOptionTest`, Pint lulus.

---

## 2026-08-27 — D9: loop mitra aktif

Mitra sekarang punya jalurnya sendiri: ajukan trip di panel `/vendor`, admin meninjau, baru tayang.

**Dua batas yang menentukan keamanannya.** Pertama, `getEloquentQuery()` di `Vendor\Resources\TripResource` menyaring ke `vendor_id` milik user yang masuk — trip mitra lain tidak pernah ditemukan, bukan ditemukan lalu ditolak; penjaga di lapisan query ini juga berlaku saat id diketik langsung di URL. Kedua, pilihan status mitra dibatasi `draft` ↔ `pending_review` dan ditegakkan `Rule::in`, bukan cuma dibatasi daftar `->options()` — pola dari D7.7 berlaku lagi di sini: daftar opsi tidak menolak nilai yang dikirim langsung ke Livewire, dan tanpa aturan itu mitra bisa menayangkan tripnya sendiri lewat request buatan. Kalau mitra bisa mem-publish sendiri, tinjauan admin tidak ada artinya.

**Sisi admin:** aksi Setujui dan Tolak di `TripResource`, badge jumlah pengajuan menunggu. Setujui menolak trip yang belum punya jadwal (penjaga yang sama dengan `EditTrip` — trip tayang tanpa jadwal tidak muncul di kategori dan tidak bisa dipesan). Tolak mewajibkan `review_note`, sama seperti penolakan pembayaran di D5: mitra perlu tahu apa yang harus diperbaiki, dan tanpa itu keputusan lama tidak bisa ditelusuri ulang. Catatan penolakan muncul kembali di form mitra.

**Peserta:** `Vendor\Resources\BookingResource` read-only berisi booking trip miliknya saja. NIK/paspor sengaja tidak ditampilkan — mitra butuh nama dan kontak untuk mengurus keberangkatan, nomor identitas tidak menambah apa pun selain risiko kalau layar itu terbuka di lapangan. Badge navigasi menghitung booking seminggu terakhir; itu bentuk "notifikasi booking baru" di V1.5, karena belum ada kanal push maupun email.

Migration: `trips.review_note`, `reviewed_by`, `reviewed_at`.

Verifikasi: **131 test lulus / 575 assertion** (9 test baru di `VendorTripTest`), Pint lulus.

---

## 2026-08-27 — D8: onboarding mitra

Halaman publik `/jadi-mitra` (kriteria, benefit, tiga langkah, form pengajuan) tertaut dari footer. Terbuka tanpa login: calon mitra menilai dulu sebelum memutuskan, dan memaksa daftar akun lebih dulu hanya menyaring orang yang belum tahu ini cocok untuknya atau tidak.

**Dokumen pengajuan diperlakukan seperti bukti bayar.** Disimpan di disk non-publik (`config/partner.php`), dibatasi jpg/png/webp/pdf maksimal 4 MB dan lima berkas, dan hanya keluar lewat route ber-`role:admin` — isinya akta usaha dan identitas penanggung jawab. Endpoint kirimnya di-throttle 3/menit per email+IP karena terbuka tanpa login sekaligus menerima unggahan, dua sifat yang membuatnya sasaran empuk.

**Panel admin: `VendorApplicationResource`,** sengaja tanpa form create/edit — barisnya lahir dari form publik, dan yang dilakukan admin cuma menjadwalkan obrolan, menyetujui, atau menolak dengan alasan wajib. Badge navigasi menghitung pengajuan yang masih menunggu.

**`ApproveVendorApplication`** membuat profil `vendors` dan akun panel dalam satu transaksi — kalau pembuatan akun gagal di tengah, pengajuan tidak boleh tertinggal berstatus approved tanpa vendor, karena admin akan mengira urusannya sudah beres padahal mitra tidak bisa masuk. Akun baru dapat password acak yang ditampilkan sekali ke admin untuk diteruskan lewat WhatsApp (tidak ada email transaksional di V1.5); email yang sudah punya akun customer dinaikkan perannya jadi vendor, bukan digandakan — kolom email unik.

Tabel baru: `vendors` (termasuk `commission_percent` yang disiapkan untuk V2 dan sengaja belum punya UI) dan `vendor_applications`.

Verifikasi: **122 test lulus / 524 assertion** (10 test baru di `PartnerOnboardingTest`), `migrate:fresh --seed` bersih, Pint lulus.

---


## 2026-08-27 — Perbaikan hasil audit form + masa sesi login

Empat perbaikan, semuanya berangkat dari temuan audit form D7.7 dan laporan masa sesi login.

**`trip_prices.min_pax` wajib diisi.** Kolomnya NOT NULL tapi form hanya punya `->default(1)`. Field yang dikosongkan saat menyunting mengirim `null` dan berakhir sebagai galat database, bukan pesan validasi yang bisa dibaca admin. Sekarang `required()`.

**`trip_schedules.status` pakai Enum.** Dari TextInput teks bebas jadi `Select` berbasis `TripStatus`, plus cast Enum di model `TripSchedule` dan `Rule::enum()` di form. Migration `normalize_trip_schedules_status` merapikan baris lama yang nilainya di luar enum ke `published`. **Pola yang perlu diingat:** daftar `->options()` saja tidak menolak nilai asing yang dikirim langsung ke Livewire — tanpa `Rule::enum()`, yang meledak adalah cast Enum saat baris dibaca, jauh dari tempat nilai itu masuk. Aturan yang sama dipasang ke Select status di `TripResource`.

**Trip tidak bisa published tanpa jadwal.** Dijaga di `CreateTrip::beforeCreate()` dan `EditTrip::beforeSave()`: notifikasi Filament persistent yang menyebut langkah berikutnya, lalu `halt()` — bukan galat mentah. Sebelum ini, trip published tanpa jadwal menghasilkan keadaan yang membingungkan dari dua sisi: halaman detailnya terbuka untuk umum tapi bertuliskan "belum ada jadwal", sementara di halaman kategori trip itu tidak muncul sama sekali. Konsekuensi alur yang disengaja: trip baru wajib disimpan sebagai draf dulu, karena jadwal memang baru bisa diisi setelah record induknya ada.

**`SESSION_LIFETIME` 120 → 240 menit.** Sebelumnya persis sama dengan `BOOKING_EXPIRY_MINUTES` (120), jadi customer yang menunda pembayaran sampai mendekati batas booking bisa ter-logout tepat saat kembali membuka halaman bayar — hambatan di titik paling rawan batal. Dua angka itu sekarang tidak lagi bertabrakan.

Verifikasi: **112 test lulus / 468 assertion** (5 test baru di `TripResourceTest`), `migrate:fresh --seed` bersih, Pint lulus.

---


## 2026-08-27 — Web Push naik ke D12 + aturan pengingat commit + audit form D7.7

**Web Push notification pindah ke D12** (dokumen saja). Item terakhir yang masih menganggur di GUIDE "Backlog — Menunggu Giliran" untuk urusan notifikasi sekarang punya fase resmi. Waktunya tepat karena fondasinya sudah berdiri sejak D7.6: `public/manifest.json` dan `public/sw.js` tinggal ditambahi listener `push` + `notificationclick`, bukan dibangun dari nol. Blok D12 di PLAN.md §7 mengunci empat hal:

- Dua pemicu saja — pembayaran jadi `verified` dan pengingat H-1. Keduanya sudah punya jalur yang bekerja tanpa push (Booking Saya, tombol wa.me admin), jadi push gagal tidak menghilangkan informasi apa pun.
- **Opt-in lewat tombol customer.** `Notification.requestPermission()` yang jalan sendiri saat halaman dibuka dilarang: prompt tak diminta hampir selalu ditolak, dan penolakan itu permanen per browser — sekali mati, tidak bisa diminta ulang lewat kode.
- Tanpa worker daemon (`CLAUDE.md` §1): push dikirim di dalam request yang sedang terjadi atau lewat command yang dipanggil cron, sama seperti `bookings:expire`.
- Isi notifikasi dibatasi kode booking, judul trip, dan waktu. NIK/paspor tidak boleh ikut — isi notifikasi melewati server push browser, jadi diperlakukan sebagai kanal luar, aturan yang sama dengan pesan reminder H-1 di D7.6 (d).

Aturan cache D7.6 ditegaskan tidak boleh dilonggarkan saat ini dikerjakan: service worker tetap tidak menyentuh dokumen HTML.

**`CLAUDE.md` §6 — commit & push berkala.** Kapan commit tetap hak pemilik project dan CLI tidak melakukannya tanpa diminta, tapi sekarang CLI **wajib** menyebutkan jumlah berkas belum ter-commit di bagian "Perlu diupdate:"/"Langkah selanjutnya:" begitu tumpukan melewati ~15 berkas atau satu sesi kerja penuh. Alasannya: tumpukan lintas sesi berakhir jadi satu commit raksasa yang tidak bisa di-review, dan begitu ada yang rusak tidak ada titik balik yang jelas.

**Audit form D7.7 — dilaporkan, belum diperbaiki.** Field wajib inti sudah `required` dan field opsional benar tidak memblokir submit. Tiga temuan dicatat di `update.md` bagian "Temuan audit form (belum diputuskan)": `min_pax` tidak `required` padahal kolomnya NOT NULL (berpotensi galat database alih-alih pesan validasi), trip bisa disimpan tanpa satu pun jadwal (relation manager baru muncul setelah trip tersimpan, jadi tidak ada yang menegakkan "minimal 1 jadwal"), dan `trip_schedules.status` masih teks bebas alih-alih Enum. Tidak ada yang disentuh — keputusan perbaikan menunggu pemilik project.

---


## 2026-08-27 — Chat widget naik dari Backlog ke D12 + `vendor_id` disembunyikan

**Chat widget (dokumen saja).** Item "In-app chat widget" dipindahkan dari GUIDE "Backlog — Menunggu Giliran" ke fase resmi **D12**, digabung dengan private trip & polish. Selama masih di backlog, item ini tidak punya kriteria selesai dan — lebih penting — tidak punya batasan tertulis, padahal justru batasannya yang menentukan aman-tidaknya. Blok D12 di PLAN.md §7 sekarang memuat: pilih **satu** penyedia embed (Tawk.to atau Crisp, ditentukan saat eksekusi setelah free-tier dicek terhadap trafik nyata), dipasang di layout utama customer supaya berlaku di semua halaman sekaligus, tombol "Lanjut ke WhatsApp" di dalam widget, dan larangan membangun sistem chat sendiri — bukan soal selera, shared hosting tidak bisa menjalankan proses persisten untuk WebSocket.

Dua batasan ditulis eksplisit:

- **Chat umum/CS saja.** Approve/reject pembayaran dan penerbitan tiket tetap wajib lewat layar verifikasi Filament dari D5. Prinsip permanen, sama persis dengan yang dipegang chatbot FAQ-only — jalur uang dan tiket tidak boleh punya pintu belakang lewat kanal chat mana pun.
- **Data mengalir ke pihak ketiga.** Isi percakapan, nama, dan kontak yang diketik customer sampai ke server penyedia widget, jadi penyedia yang dipilih wajib dicantumkan di `/kebijakan-privasi` bagian pihak ketiga penerima data saat D12 dikerjakan.

Item dihapus dari daftar Backlog supaya tidak hidup di dua tempat sekaligus — masalah yang sama sudah dibereskan untuk push notification di sesi 2026-08-14. `EXECUTION_PROMPTS.md` (Sesi 7) dan checklist D12 di `update.md` ikut menyusul.

**`TripResource.vendor_id` disembunyikan (kode).** Dropdown mitra dicabut dari form trip, diganti `Hidden` + `dehydrated(false)`. Alasannya rujukan, bukan kerapian: di V1 semua trip milik E-GOTO (`vendor_id` null), dan setelah V1.5 kolom ini merujuk `vendors.id` — bukan `users.id` yang dipakai dropdown sebelumnya. Satu klik admin ke user vendor demo sekarang akan jadi rujukan salah begitu tabel `vendors` lahir. Ada TODO di kode + di `update.md` untuk mengaktifkannya kembali lewat `->relationship()` setelah D8/D9, dan test `tidak menyimpan vendor_id yang dikirim dari form trip` menjaga sampai saat itu. Verifikasi: **107 test lulus / 434 assertion**, Pint lulus.

---


## 2026-08-27 — D7.7: CRUD trip di panel admin

Gap yang ditemukan 2026-08-24 tertutup. Menambah trip sekarang tidak lagi butuh tinker atau seeder: `TripResource` (hub) + `SchedulesRelationManager` (jadwal, dengan `TripPrice` bersarang lewat `Repeater::relationship()`) + `ImagesRelationManager` (galeri). Polanya mengikuti `CategoryResource` yang sudah tervalidasi — slug otomatis dari judul, label Bahasa Indonesia, tiga Page standar.

Dua hal yang sengaja dibuat berbeda dari bawaan generator:

- **`booked_count` tidak bisa disunting** (`disabled()` + `dehydrated(false)`). Angka itu dikunci `lockForUpdate()` saat booking dibuat (PLAN.md §5); admin yang mengetiknya manual sama dengan menjual kursi dua kali tanpa jejak. Ada test yang mengirim `booked_count: 12` lewat form dan memastikan yang tersimpan tetap 0.
- **`vendor_id` diisi lewat `->options()`, bukan `->relationship()`.** Tabel `vendors` baru lahir di V1.5 (PLAN.md §4) dan kolom ini akan berpindah induk; relasi Eloquent yang dibuat sekarang hanya akan jadi utang saat itu.

**Temuan pola `Repeater::relationship()`** (belum pernah dipakai di project ini, dan risikonya sudah ditandai di rencana D7.7): data test berbentuk list berindeks angka hanya diterima kalau repeater-nya `defaultItems(0)`. Dengan baris bawaan, Filament menyimpan state berkunci uuid, data test menambah baris kedua, dan baris bawaan yang kosong itu yang gagal validasi. Repeater harga karena itu dipasang `defaultItems(0)` + `minItems(1)` — sekalian menutup jadwal tanpa tingkat harga, yang memang tidak bisa dipesan.

Verifikasi: **106 test lulus / 430 assertion** (5 test baru di `tests/Feature/TripResourceTest.php`), `migrate:fresh --seed` bersih, Pint lulus, tidak ada entri log baru.

---


## 2026-08-27 — D7.6: enam penyempurnaan sebelum rilis

Semua item blok D7.6 (PLAN.md §6) masuk ke kode. Test §9 nomor 15-20 hijau.

**PWA installable.** `public/manifest.json` + `public/sw.js` di root domain, registrasi setelah `load` di `resources/js/app.js`. Batas cache-nya sengaja sempit: hanya `/build/` (aset Vite) dan `/icons/`. Navigasi dan request non-GET selalu lewat jaringan — halaman pembayaran yang tersaji dari cache berarti kuota, hitung mundur, dan status verifikasi yang basi, jauh lebih berbahaya daripada halaman yang tidak bisa dibuka offline. Ikon 192/512 dibangkitkan lewat encoder PNG minimal (bidang teal + titik amber) karena ekstensi GD tidak aktif di XAMPP mesin dev; ini penempatan sementara sampai artwork logo asli ditempel.

**Badge verifikasi + estimasi + unduh QRIS.** Badge memakai `PaymentStatus` dan komponen `status-badge` yang sudah ada — tidak ada state baru. Estimasi waktu keluar dari `config('booking.verification_eta')` supaya halaman pembayaran, Booking Saya, dan panel konfirmasi tidak pernah menyebut angka berbeda. Tombol unduh gambar QRIS ditambahkan untuk yang membayar dari perangkat lain.

**Filter level fisik.** Migration `trips.difficulty_level` (enum nullable) + `App\Enums\TripDifficulty` dengan label kalimat manusia ("Cocok untuk pemula"), bukan nama enum. Filternya radio di panel filter kategori yang sudah ada, ditaruh paling atas; di kategori Pendakian ada catatan tambahan. Nilai di luar daftar ditolak validasi, bukan diam-diam diabaikan.

**Pengingat H-1.** Halaman admin `ReminderKeberangkatan` memuat booking `confirmed` yang berangkat besok — dibandingkan lewat `whereDate` pada tanggal jadwal, bukan rentang 24 jam yang akan ikut menyeret keberangkatan lusa dini hari. Pesannya dibangun `MessagingService::remindDayBefore()` di `WhatsAppLinkService` (tanpa service baru), berisi kode booking, titik kumpul, tanggal, itinerary ringkas, dan checklist perlengkapan; **NIK/paspor tidak ikut** — begitu pesan keluar lewat wa.me, isinya di luar kendali kita. Tanpa cron, tanpa queue: admin yang menekan tombolnya.

**Checklist perlengkapan.** Ditampilkan di detail trip dan e-tiket, dibaca utuh dari kolom JSON tanpa query JSON-path. Kategori tanpa checklist tidak meninggalkan judul menggantung — ada test untuk itu.

**Konfirmasi metode pembayaran.** QRIS dan form unggah bukti baru dirender setelah customer menekan "Saya paham, lanjutkan ke pembayaran". Panelnya memuat alur unggah→verifikasi→tiket, estimasi waktu, peringatan eksplisit bahwa verifikasinya manual (bukan instan), dan tautan langsung ke S&K + Kebijakan Privasi. Tandanya disimpan per kode booking, bukan per akun: peringatan itu justru paling perlu dibaca ulang saat orang memesan lagi berbulan-bulan kemudian. Booking yang sudah punya bukti terunggah dianggap sudah lewat panel ini supaya sesi kedaluwarsa tidak mengunci unggah ulang setelah bukti ditolak.

Verifikasi: **101 test lulus / 387 assertion**, `migrate:fresh --seed` bersih, `npm run build` sukses, Pint lulus, tidak ada entri log baru.

---


## 2026-08-27 — Blok D7.7 (Filament Resource Trip) masuk PLAN + aturan ikon dekoratif

Dokumen saja, kode tidak disentuh.

`PLAN.md` §6 dapat blok **D7.7 — Filament Resource Trip**, disisipkan setelah D7.6 dan sebelum FASE V1.5. Sebelumnya sisa gap CRUD trip cuma hidup sebagai tiga bullet di `update.md` — tidak punya daftar berkas, bentuk form, maupun kriteria selesai, jadi eksekusinya akan bergantung ingatan sesi lama. Blok baru memuat: berkas yang disentuh (`TripResource` + Pages, `SchedulesRelationManager` dengan `TripPrice` bersarang lewat `Repeater::relationship()`, `ImagesRelationManager`, migration `trips.difficulty_level` + Enum `TripDifficulty` kalau belum lewat D7.6), bentuk form per bagian, dan dua batas scope yang gampang ditabrak: `booked_count` read-only di form (angka itu dikunci `lockForUpdate()` saat booking — admin mengetiknya manual sama dengan overbooking senyap) dan layar mitra mengajukan trip tetap milik D9. Test wajib 21-23 menyusul di §9.

`CLAUDE.md` §10 naik dari tiga ke empat pola pembocor "dibuat AI": tambahan **ikon dekoratif di judul** — emoji/ikon kecil menempel di depan header dilarang, ikon hanya boleh kalau fungsinya jelas dan ada preseden di kode (heroicon navigasi Filament). Ditutup dengan rujukan pola header yang benar: label kapital kecil di atas judul tebal, spacing bernilai beda per jenis jarak.

Environment lokal: database `egoto` + `egoto_testing` dibuat ulang dari kosong, dump database lama `e_goto` disimpan di luar repo. Detail path di `update.md` bagian "Cara menyalakan environment lokal". Verifikasi: **83 test lulus / 314 assertion**, `migrate:fresh --seed` bersih.

---


## 2026-08-24 — CategoryResource: CRUD kategori pertama di panel admin

GUIDE.md menjanjikan "Admin: CRUD trip" sejak FASE V1, tapi prompt harian D1-D7 tidak pernah membangun satu pun Filament Resource untuk kategori/trip — dikonfirmasi nihil total (Category, Trip, TripSchedule, TripPrice, TripImage). Operasional trip sebelum ini mustahil tanpa developer buka tinker/seeder manual. Ditutup bertahap, dimulai dari `CategoryResource` karena paling mandiri dan jadi tempat memvalidasi pola sebelum dipakai di `TripResource` yang jauh lebih besar (menyusul di sesi berikutnya).

**Migration baru:** `categories.gear_checklist` (json, nullable) — kolom yang sudah disetujui sejak blok D7.6 tapi belum pernah benar-benar dibuat. Form kategori sekarang punya `Repeater::simple()` untuk mengisinya, dengan `->defaultItems(0)` (bukan default Filament yang 1) karena checklist ini memang opsional.

**Field lain:** `id_requirement` jadi `Select` dari enum `IdType` (bukan input teks bebas), slug auto-terisi dari nama + validasi unique, `is_active` bisa diklik langsung di tabel (`ToggleColumn`).

**Keputusan scope disengaja (bukan lupa):** `icon` tetap `TextInput` polos (belum dipakai satu pun view publik — dicek lewat grep), bukan icon-picker.

Test baru: `tests/Feature/CategoryResourceTest.php` (create lengkap dengan checklist, slug duplikat ditolak). 83 test lulus/314 assertion, `migrate:fresh --seed` bersih, Pint lulus.

Dokumen tersentuh: `update.md` (checklist baru + centang sub-item D7.6 gear_checklist), `CHANGELOG.md`. **Belum:** `TripResource` + `RelationManagers` (Schedule bersarang Price, Image) — direncanakan sesi terpisah karena pola repeater-di-dalam-relation-manager belum pernah dipakai di project ini.

---

## 2026-08-14 (sesi 3) — Penamaan kolom D7.6 difinalkan sebelum ada kodenya

Dua kolom yang dipakai D7.6 diberi nama final selagi masih murni dokumen: `trips.difficulty` menjadi **`trips.difficulty_level`** dengan nilai enum `pemula`/`menengah`/`lanjutan` (sebelumnya `santai`/`sedang`/`menantang`), dan `categories.equipment_checklist` menjadi **`categories.gear_checklist`**. Mengganti nama kolom sekarang gratis; setelah ada migration, model, dan view yang memakainya, harganya jauh lebih mahal.

**Alasan penempatan ditulis permanen, bukan cuma nama yang diganti.** Tingkat kesulitan tinggal di `trips` karena dua trip pendakian dalam kategori yang sama bisa jauh berbeda beratnya — satu cocok untuk pemula, satunya jelas tidak. Menaruhnya di kategori akan memaksa seluruh trip pendakian berbagi satu label, dan label yang salah pada konteks fisik bukan sekadar bikin kecewa. Sebaliknya `gear_checklist` memang seragam per kategori (bawaan pendakian relatif sama antar trip pendakian), jadi cukup ditulis sekali. Tanpa catatan ini di dokumen, orang berikutnya bisa memindahkan salah satunya "supaya konsisten" tanpa tahu kenapa memang tidak boleh.

**Keduanya naik ke tabel skema PLAN §4**, tidak lagi hanya hidup di dalam blok fase D7.6 — pembaca skema tidak perlu tahu nomor fase untuk tahu bentuk tabelnya. Enum `TripDifficulty` masuk daftar enum §4, dan `gear_checklist` masuk paragraf aturan kolom JSON (cast `array`, dilarang query JSON-path karena beda perilaku MariaDB 10.4 dev vs MySQL 8 production — checklist hanya dibaca utuh lalu dirender, tidak pernah dicari isinya).

Dokumen tersentuh: `GUIDE.md`, `PLAN.md`, `update.md`. **Belum ada migration** — seluruh D7.6 masih dokumen, menunggu prompt eksekusi terpisah.

---

## 2026-08-14 (sesi 2) — Refund disederhanakan jadi dua tingkat, blok D7.6 lahir

**Kebijakan refund versi pertama punya lubang.** Tingkatannya melompat dari ">H-7" ke "H-3 sampai H-1", jadi pembatalan di H-6, H-5, dan H-4 tidak diatur sama sekali — sempat ditambal kalimat "diputuskan kasus per kasus". Untuk dokumen yang mengikat, tambalan itu berarti dua hal buruk sekaligus: customer baru bisa tahu haknya setelah bertanya, dan admin memutuskan tanpa pegangan. Sekarang dua tingkat dengan satu batas: **lebih dari H-7** → 50% dikurangi biaya admin Rp25.000; **H-7 ke bawah** → tanpa refund, ditawarkan reschedule kalau vendor punya slot. Trip batal dari sisi penyelenggara tetap 100%, force majeure tetap wajib refund penuh atau reschedule. Batas (b) dan (c) sengaja berdempet di angka yang sama supaya bersambung tanpa celah dan tanpa tumpang tindih.

Kebijakannya dinaikkan ke `GUIDE.md` sebagai bagian tersendiri, bukan dibiarkan hanya di PLAN dan di halaman S&K — ini keputusan *scope* yang menentukan hak customer, dan GUIDE adalah sumber kebenarannya.

**Blok D7.6 lahir**, berisi lima penyempurnaan yang masuk V1 tapi tidak menambah alur baru: PWA installable, badge "Verified Payment" + estimasi waktu verifikasi, filter level fisik, reminder H-1 lewat antrean admin, dan checklist perlengkapan per kategori. Dinamai D7.6 dengan alasan yang sama seperti D7.5 — menjaga penomoran D8–D13 tetap di tempatnya. Reminder H-1 sengaja **tanpa cron dan tanpa queue**: `wa.me` tidak bisa dikirim server, tombolnya diklik admin, konsisten dengan D5 dan dengan prinsip "tidak ada worker daemon di shared hosting". Blok ini membawa satu pengecualian migration yang disetujui eksplisit dan dicatat di dalam bloknya, bukan diterobos diam-diam.

**Catatan jujur yang perlu diingat:** halaman `/syarat-ketentuan` yang sudah tayang **masih memuat tabel refund versi lama**. Dokumen berubah lebih dulu atas permintaan pemilik project, kodenya menyusul di prompt terpisah. Test yang ada **tidak** menangkap ketidaksesuaian ini — `menandai angka refund dan retensi yang belum final` hanya memeriksa keberadaan penanda `[SEMENTARA]`, bukan isi tabelnya. Satu-satunya pengingat adalah item di `update.md` bagian "⚠ Perlu dieksekusi ulang ke kode", bersama satu bug copy di halaman detail trip yang menyebut kuota dikunci setelah pembayaran diverifikasi — padahal PLAN §5.3 menetapkan kuota dikunci sejak booking dibuat.

Dokumen tersentuh: `GUIDE.md`, `PLAN.md`, `update.md`. Nol perubahan kode.

---

## 2026-08-14 — Identitas teal, cap 12 peserta, D7.5 halaman legal

Empat keputusan pemilik project. Sesuai `CLAUDE.md` §6, `GUIDE.md` diperbarui lebih dulu sebagai sumber kebenaran, lalu `PLAN.md` menyesuaikan.

**Design system diganti — teal, mengikuti logo.** Palet "editorial hangat" (sand/forest/terracotta + Fraunces) diganti: `mist` untuk permukaan, `teal` untuk teks & aksen, `amber` **khusus** CTA dan state urgensi. Tiga warna inti diambil langsung dari logo (`#199FA5` / `#077C82` / `#044D4A`), sisanya turunan. Sifat pekerjaannya rename token 1:1 di 20 berkas (413 kemunculan), bukan redesain layout — pola penggantinya `\b(sand|forest|terracotta)-\d` supaya kata biasa di teks salinan tidak ikut terganti. Panel Filament admin & vendor ikut pindah dari `Color::Amber` ke teal logo supaya panel staf dan sisi customer terbaca satu produk.

`amber-600` sengaja dibuat lebih gelap (`#A8630D`) daripada amber "alami": tombol solid berteks putih butuh 4,5:1, dan amber terang `#E08A1E` tidak lolos di sana — yang terang dipakai untuk badge/ikon berteks gelap. Catatan ini ditulis di komentar `app.css` supaya tidak tertukar saat disunting nanti.

**Tipografi: Fraunces (serif) → Plus Jakarta Sans.** Dipilih karena dirancang untuk city branding Jakarta — kontekstual untuk produk wisata Indonesia — dan geometric-humanist-nya cukup berbeda dari Inter yang netral. Konsekuensinya disadari: pasangan lama serif+sans dapat kontras gratis dari perbedaan bentuk huruf, pasangan baru tidak. Kontras itu diganti manual lewat berat + ukuran + tracking (`.text-hero`, heading `font-bold`/`font-extrabold`, tracking negatif), karena tanpa itu halaman jatuh ke kesan template SaaS generik yang dilarang `CLAUDE.md` §10.

**Cap keras 12 peserta per booking.** Angka tunggal di `config('booking.max_pax_per_booking')`, dibaca oleh validasi server, batas repeater Alpine, dan teks halaman — batas yang diketik ulang di beberapa tempat cepat berbeda, dan yang paling longgar yang akhirnya menang. Berlaku walau sisa kuota jauh lebih besar; kuota tetap dicek terpisah, yang lebih kecil yang menang. Ditegakkan di `StoreBookingRequest`, bukan cuma di form — batas yang hanya hidup di UI bisa dilewati POST manual. Pesan validasi dan panel di halaman booking/detail trip mengarahkan ke Request Private Trip (`MessagingService::requestPrivateTrip()`, jalur `wa.me` sementara sampai D12).

**D7.5 — FAQ, Syarat & Ketentuan, Kebijakan Privasi.** Tiga halaman publik tanpa `auth`: justru dibaca orang yang belum punya akun, tepat saat memutuskan mau pesan atau tidak. S&K memuat refund bertingkat — (a) batal penyelenggara/kuota tidak tercapai → 100% otomatis, (b) peserta batal >H-7 → 50% dikurangi admin Rp25.000, (c) H-3 s/d H-1 → tanpa refund, ditawarkan reschedule, (d) force majeure → refund penuh atau reschedule, wajib salah satu — plus pembagian tanggung jawab platform vs mitra. Privasi menyebut eksplisit NIK/paspor terenkripsi, retensi akun aktif + 2 tahun, dan pihak ketiga yang menerima data (mitra penyelenggara untuk perizinan/asuransi, Google saat login, penyedia hosting).

Angka refund, biaya admin, dan retensi masih sementara, jadi ditandai `[SEMENTARA — validasi sebelum publish]` **di dalam teks halaman**, bukan cuma di dokumen internal — supaya angka yang belum divalidasi tidak ikut terbit diam-diam. Ada test yang gagal kalau penanda itu hilang sebelum angkanya final.

**Dua opsi pembayaran (lunas / DP + pelunasan)** dicatat di GUIDE sebagai bagian baru "Keputusan Disetujui — Pelaksanaan Ditunda": sudah disetujui, pasti dikerjakan, tapi baru setelah V1 live. Yang dijaga sekarang hanya satu: jangan berasumsi 1 booking = 1 pembayaran.

**Hasil verifikasi:** `php artisan test` **81 lulus / 302 assertion** (73 lama tetap hijau + 8 baru: cap pax di batas & lewat batas, ajakan private trip, 3 halaman legal untuk guest, tautan footer, penanda `[SEMENTARA]`), `migrate:fresh --seed` bersih, `npm run build` sukses (Plus Jakarta Sans ikut terbundel, Fraunces hilang dari dependencies), Pint lulus, tidak ada entri baru di `storage/logs`.

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
