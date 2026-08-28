# Deploy Manual ke Hostinger — E-GOTO

Dokumen ini yang dirujuk `CLAUDE.md` §17. Urutannya sengaja dari yang paling
mudah dibatalkan ke yang paling sulit, supaya kalau berhenti di tengah, situs
lama tidak ikut rusak.

Target: **Hostinger Business (shared hosting), PHP 8.3, MySQL 8.** Tidak ada
proses persisten di sana — tidak ada queue worker daemon, tidak ada WebSocket.
Tugas periodik jalan lewat cron `schedule:run`.

---

## 0. Sebelum menyentuh server

- [ ] `php artisan test` hijau di lokal.
- [ ] `npm run build` sukses (hasilnya `public/build/`).
- [ ] `git status` bersih — yang dideploy adalah commit, bukan berkas lepas.
- [ ] Siapkan: kredensial database Hostinger, Client ID/Secret Google untuk
      domain production, berkas QRIS merchant asli, nomor WhatsApp admin.

## 1. Domain & PHP

1. hPanel → **Websites** → pilih domain/subdomain.
2. **Advanced → PHP Configuration**: pilih **PHP 8.3**.
3. Di tab **PHP extensions**, pastikan aktif: `pdo_mysql`, `mbstring`,
   `openssl`, `fileinfo`, `gd`, `zip`, `bcmath`.

   **`gd` wajib dicek betulan, jangan dianggap pasti aktif.** Di laptop
   pengembangan, `ext-gd` ternyata masih mati di `C:\xampp\php\php.ini` sampai
   2026-08-28 — akibatnya Composer menolak memasang paket **apa pun**, karena
   `simplesoftwareio/simple-qrcode` (dipakai QR e-tiket sejak D6) mensyaratkannya.
   Pesan galatnya menyebut paket yang sedang dipasang, bukan `simple-qrcode`, jadi
   penyebabnya tidak langsung kelihatan. Ini juga yang menggagalkan
   `minishlink/web-push` di sesi 15.

   Cek di server sesudah SSH aktif: `php -m | grep -i '^gd$'` harus mengeluarkan
   satu baris `gd`. Kalau kosong, nyalakan dulu di hPanel sebelum menjalankan
   `composer install` — bukan sesudahnya.
4. **Advanced → SSH Access**: aktifkan, catat host/port/user.

## 2. Database

1. hPanel → **Databases → MySQL Databases**: buat database + user, beri hak penuh.
2. Catat nama database, user, dan password — dipakai di `.env` nanti.
3. Jangan impor apa pun dulu. Skema dibuat lewat `php artisan migrate`.

## 3. Menaruh kode

Lewat SSH (cara yang dianjurkan):

```bash
cd ~/domains/NAMA-DOMAIN
git clone <url-repo> app
cd app
composer install --no-dev --optimize-autoloader
```

`node_modules` **tidak** perlu diunggah. Hasil build (`public/build/`) dibawa
dari lokal — build di shared hosting sering kehabisan memori.

Kalau tanpa SSH: unggah lewat File Manager, kecualikan `node_modules`,
`vendor` (hasil `composer install` di server), `.env`, dan `storage/logs`.

## 4. Document root

Root domain harus menunjuk ke **`app/public`**, bukan `app`.

- hPanel → **Websites → Advanced → Website root**, arahkan ke `domains/NAMA-DOMAIN/app/public`.
- Kalau opsi itu tidak tersedia di paket Anda, buat symlink dari `public_html`
  ke `app/public`.

Jangan menyiasatinya dengan menaruh isi `public/` di root dan sisanya di
subfolder — itu membuat `.env` dan `storage/` bisa diakses dari internet.

## 5. Berkas `.env`

Salin `.env.example` jadi `.env` di server, lalu isi:

```env
APP_NAME=E-GOTO
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nama-domain

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_LIFETIME=240

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://nama-domain/auth/google/callback

QRIS_IMAGE_PATH=images/qris-merchant.png
QRIS_MERCHANT_NAME=...
ADMIN_WHATSAPP=628...

BOOKING_PROOF_DISK=local
PARTNER_DOC_DISK=local
```

**`APP_ENV=production` + `APP_DEBUG=false` wajib diisi sebelum situs dibuka ke
publik.** Ini diuji langsung pada 2026-08-27, bukan diasumsikan:

- Dengan `APP_DEBUG=true`, satu galat 500 menghasilkan halaman ±870 KB berisi
  jejak tumpukan, **potongan kode sumber**, dan **jalur berkas** (mis.
  `routes/web.php`) beserta versi framework. Nilai `.env` sendiri tidak ikut
  tercetak di versi Laravel ini, tapi peta berkas dan potongan kode sudah cukup
  untuk memandu percobaan berikutnya.
- Dengan `APP_DEBUG=false`, galat yang sama menghasilkan halaman "Server Error"
  ±13 KB tanpa jalur berkas, tanpa nama kelas pengecualian, dan tanpa pesan
  aslinya. Detailnya hanya masuk `storage/logs/laravel.log`.

Periksa ulang setelah `config:cache` (langkah 9) — config yang ter-cache tidak
membaca `.env` lagi, jadi mengubah `APP_DEBUG` tanpa mengulang cache tidak
berpengaruh.

Lalu:

```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force
```

`DemoTripSeeder` dan `DemoUserSeeder` **jangan** dijalankan di production —
isinya data contoh beserta akun demo berpassword `password`.

## 6. Izin folder

```bash
chmod -R 775 storage bootstrap/cache
```

Bukti bayar dan dokumen mitra disimpan di disk `local` (`storage/app/private`),
di luar `public/` — jangan memindahkannya ke disk publik "supaya gampang
dibuka". Keduanya memuat identitas orang.

## 7. Cron

hPanel → **Advanced → Cron Jobs**, tambah satu entri **tiap menit**:

```
/usr/bin/php8.3 ~/domains/NAMA-DOMAIN/app/artisan schedule:run >> /dev/null 2>&1
```

Tanpa ini, `bookings:expire` tidak pernah jalan dan booking kedaluwarsa terus
menahan kuota — kursi habis padahal tidak ada yang membayar.

## 8. Google OAuth production

1. Google Cloud Console → Credentials → OAuth client yang sudah ada.
2. Tambahkan **Authorized redirect URI**: `https://nama-domain/auth/google/callback`.
3. Tambahkan domain ke **Authorized JavaScript origins**.
4. URI lokal (`http://localhost:8000/...`) boleh tetap ada untuk pengembangan.

## 9. Cache production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ulangi ketiganya **setiap kali** `.env` atau route berubah. Config yang
ter-cache tidak membaca `.env` lagi.

## 10. Sesudah rilis — periksa manual

- [ ] Buka homepage, kategori, dan satu detail trip sebagai tamu (tanpa login).
- [ ] Pesan satu trip sampai halaman pembayaran, cek QRIS tampil dan nominal
      uniknya masuk akal.
- [ ] Unggah bukti, setujui dari `/admin`, pastikan e-tiket terbit.
- [ ] Check-in tiket dari `/vendor`, lalu coba lagi — harus ditolak.
- [ ] Ganti password akun admin/vendor demo kalau sempat ikut ter-seed.
- [ ] Tempel berkas QRIS merchant asli menggantikan `qris-placeholder.svg`.
- [ ] Ganti ikon PWA (`public/icons/`) dengan artwork logo asli.

## 11. Backup

Hostinger punya backup otomatis, tapi **aktif tidak sama dengan bisa
di-restore**. Sekali di awal: minta restore ke staging/subdomain lain dan
pastikan datanya benar-benar kembali. Selama itu belum diuji, anggap belum ada
backup.

## Belum berlaku sekarang

- **Web Push (D12).** Butuh `minishlink/web-push` (`ext-gd` wajib aktif) dan
  sepasang kunci VAPID di `.env`. Selama `VAPID_PUBLIC_KEY` kosong, tombolnya
  tidak muncul dan tidak ada izin browser yang diminta.
- **Widget chat (D12).** Isi `CHAT_WIDGET_ID` (Tawk.to/Crisp) supaya widget
  dirender. Kosong = tidak ada satu baris pun yang keluar.
