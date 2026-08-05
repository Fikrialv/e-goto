# E-GOTO — Guide (Restart, versi final)

Dokumen ini menggantikan seluruh `GUIDE.md` versi sebelumnya (termasuk versi lama sebelum restart). Ini sumber kebenaran tunggal — hasil diskusi ulang penuh sebelum eksekusi dimulai.

## Tujuan produk

Memudahkan orang memesan tiket **open trip** — dari cari trip sampai punya e-tiket sah di tangan — dan berkembang jadi **wadah marketplace** yang didukung banyak mitra/vendor (E-GOTO supported by mitra/vendor), dengan customer yang dimanjakan (login mudah, profil lengkap, rating, promo) dan mitra yang punya alat kerja sendiri.

## Platform: WEBSITE responsive, bukan aplikasi native

Diakses lewat browser di semua perangkat — HP, tablet, laptop, desktop. Pendekatan desain: mobile-first, wajib responsive ke semua breakpoint (mobile <640px, tablet 640–1024px, desktop >1024px). Pola navigasi web standar, bukan ala app native.

## Stack

Laravel + Filament (panel admin/vendor) + Blade + Alpine.js (customer). Dieksekusi dengan **Claude Code CLI** — file instruksi wajib bernama `CLAUDE.md` di root repo (bukan `AGENTS.md`, itu untuk Codex CLI).

## Role & Akses

Tiga role: **Customer, Vendor/Mitra, Admin**. CS digabung ke Admin untuk sekarang (WA manual) — dipisah nanti kalau volume booking sudah butuh orang kedua.

**Catatan keamanan:** akun demo hanya untuk uji coba lokal. Sebelum ada user asli, password admin production wajib diganti kuat & unik.

## Kategori Trip

Domestik (NIK), Internasional (paspor — ditutup sementara), Pendakian (wajib NIK), Pantai, Keliling Kota, Aktivitas.

---

## ALUR UTAMA — Tujuan Awal sampai Akhir

### Alur Customer (browsing publik → login saat mau transaksi)

```
Buka web (TANPA perlu login) → Lihat homepage: Trip Populer, Jadwal Terdekat,
browse per kategori → Buka detail trip (masih tanpa login) →
Klik "Booking Sekarang" → BARU diminta login/sign up
  (isi manual ATAU Google/Facebook, cepat & tidak kaku) →
Lengkapi profil (kalau baru daftar) →
Kembali otomatis ke halaman booking trip yang tadi dipilih →
Isi data peserta (leader+peserta lain, NIK domestik/pendakian, paspor internasional) →
[Opsional] pakai voucher/promo/combo → total otomatis terpotong →
Bayar QRIS (nominal unik + kode booking wajib di catatan transfer) →
Upload bukti bayar di website → [sistem auto-kirim notif WA ke admin] →
Status booking: "menunggu verifikasi" →
Admin approve → tiket + QR branded E-GOTO otomatis terbit →
Customer lihat e-tiket di profil "Booking Saya" →
Setelah trip selesai → beri rating & komentar →
[Kalau reject] lihat alasan di app → bisa upload ulang bukti bayar
```

**Prinsip penting:** browsing tidak boleh ada hambatan sama sekali. Login hanya jadi gerbang tepat sebelum transaksi (booking), bukan syarat masuk website.

### Alur Admin

```
Login → Dashboard (badge notifikasi pembayaran pending) →
Kelola trip (CRUD + kategori + harga bertingkat) →
Lihat pembayaran pending → bukti bayar vs nominal seharusnya
  ditampilkan berdampingan (flag otomatis kalau bukti terindikasi duplikat) →
Approve (+auto-generate tiket QR) / Reject (+alasan wajib) →
Kelola pengajuan mitra baru (onboarding — lihat alur mitra di bawah) →
Approve/tolak trip yang diajukan mitra
```

### Alur Vendor/Mitra — Onboarding (baru gabung E-GOTO)

```
Calon mitra baca halaman/blog "Jadi Mitra E-GOTO" (kriteria, benefit) →
Isi form pengajuan (profil bisnis, dokumen) →
Admin lihat pengajuan → jadwalkan meeting →
Review dokumen & kriteria → Admin approve/tolak →
[Approve] mitra dapat akun vendor resmi
```

### Alur Vendor/Mitra — Sudah Aktif

```
Login → Ajukan trip baru (foto, harga, kuota, jadwal) →
Admin review & approve/tolak → Trip tayang di web →
Booking masuk → Notifikasi booking baru →
Lihat daftar trip sedang/sudah berjalan + jumlah peserta →
Hari-H: input token QR customer → validasi signature →
  Valid & belum dipakai → check-in sukses
  Invalid/sudah dipakai → ditolak jelas (anti pakai-ganda) →
Setelah trip selesai → lihat rating dari customer
```

---

## Alur Pembayaran — Anti-Fraud (detail)

Bukti bayar **selalu diupload di dalam website**, tidak dipindah ke WA — WA hanya notifikasi cepat ke admin (link `wa.me` otomatis), supaya approve tetap terhubung otomatis ke proses generate tiket.

Mitigasi fraud:
1. **Nominal unik per booking** — harga asli + beberapa digit unik (mis. Rp175.000 → Rp175.017), memudahkan admin cocokkan mutasi.
2. **Kode booking wajib** di catatan transfer, ditampilkan besar di halaman pembayaran.
3. **Deteksi bukti bayar duplikat** — hash file gambar, tandai otomatis kalau dipakai di 2 booking berbeda.
4. **Batas waktu upload** — booking otomatis `expired` kalau tidak ada upload dalam waktu tertentu.
5. **Tampilan cocok-cocokan di admin** — nominal seharusnya vs bukti bayar berdampingan, besar & jelas.

QRIS statis (GoPay Merchant) dipakai sekarang — gratis, tidak perlu badan usaha. Upgrade ke Midtrans QRIS dinamis (auto-verifikasi webhook) ditunda sampai ada NIB/UMKM dan volume lebih besar.

---

## Login & Profil Customer

- Sign up/login manual (email+password) **atau** via Google/Facebook (Laravel Socialite) — dibuat interaktif, cepat, tidak kaku.
- **Catatan waktu:** pendaftaran aplikasi OAuth ke Google Cloud Console & Facebook Developer harus dimulai **paralel sejak hari pertama development** — proses approval (khususnya Facebook) bisa makan beberapa hari di luar kendali kecepatan coding, supaya tidak jadi bottleneck di tengah jalan.
- Profil customer: data diri, foto, riwayat booking, e-tiket tersimpan, beri rating setelah trip selesai, teaser "Jadi Mitra E-GOTO?" (link ke halaman onboarding mitra).
- Login wajib **hanya saat mau booking**, bukan syarat browsing.

---

## FASE PENGERJAAN

### FASE V1 — Fondasi Platform (6-7 hari)

- Browsing publik tanpa login (homepage, kategori, detail trip, Trip Populer, Jadwal Terdekat)
- Login/sign up manual + Google/Facebook, gerbang tepat sebelum booking
- Profil customer dasar + riwayat booking
- Booking + field adaptif NIK/paspor
- Pembayaran anti-fraud lengkap (nominal unik, kode booking, deteksi hash duplikat, expired otomatis)
- Admin: CRUD trip, verifikasi pembayaran, approve/reject
- Tiket + QR branded E-GOTO otomatis terbit
- Vendor: check-in manual + anti pakai-ganda
- PII (NIK, paspor) terenkripsi di database
- Backup Hostinger terverifikasi aktif
- Fondasi extensible: interface `PaymentGateway`/`MessagingService`, enum status konsisten, komponen Blade reusable, middleware role siap diperluas

**Output:** website jalan penuh untuk loop transaksi dasar, aman, siap diuji user asli terbatas.

### FASE V1.5 — Marketplace & Interaktivitas (5-7 hari, lanjut langsung)

- Onboarding mitra/vendor baru (blog info, form pengajuan, kriteria, jadwal meeting, review dokumen, approval admin) — ini yang dimaksud "affiliate": kerja sama mitra, BUKAN referral berkomisi customer
- Loop mitra aktif: ajukan trip → admin approve/tolak → tayang
- Voucher, promo, combo package di checkout
- Rating & komentar customer setelah trip selesai
- Request private trip via WA ke admin/CS
- Vendor: daftar trip sedang/sudah berjalan + jumlah peserta
- Teaser "Jadi Mitra E-GOTO?" tampil di homepage

**Output:** E-GOTO jadi wadah multi-mitra, customer dapat promo & bisa rating, ada jalur private trip.

### FASE V2 — Menyusul terpisah (belum bisa diestimasi pasti)

- Dashboard pemasukan vendor interaktif (grafik, breakdown per trip) — **butuh Anda tetapkan dulu**: skema komisi platform 3-10% dari harga normal mitra, diterapkan ke perhitungan seperti apa
- Modul lanjutan lain sesuai kebutuhan nyata dari feedback V1.5

**Total estimasi V1 + V1.5: 11-14 hari kerja.** V2 menyusul setelah V1.5 terbukti stabil dipakai, bukan dipaksa masuk jendela waktu yang sama.

---

## Backlog — Ditunda Sadar, Bukan Dihapus

Dashboard analitik penuh, Ledger/Buku Kas, AI Assistant, Web Builder, Ticket Designer custom, payment gateway otomatis (Midtrans — nanti setelah NIB/UMKM), WhatsApp Business API resmi (link `wa.me` manual cukup dulu), push notification, audit log lengkap, 2FA staf, multi-bahasa penuh, dark mode, role CS terpisah dari Admin, sistem affiliate referral berkomisi (customer-refer-customer — **tidak ada rencana dibangun**, sudah dikonfirmasi tidak diperlukan).

---

## Data Uji Coba (Seeder)

10-12 trip demo, variatif: minimal 2 per kategori aktif, variasi harga bertingkat, variasi tanggal jadwal (untuk uji "Jadwal Terdekat"), minimal 1 trip kuota hampir penuh, minimal 1 kuota penuh (state disabled/waitlist). Internasional: status masih perlu dikonfirmasi — aktifkan dummy untuk uji coba atau tetap tutup.

## Design System — status: **diputuskan (2026-08-05, sebelum D2)**

Arah terpilih: **editorial hangat**, bukan palet teal/navy/orange yang sebelumnya jadi default sementara.

| Peran | Warna | Catatan |
|---|---|---|
| Permukaan / latar | **sand** (`sand-50` … `sand-400`) | Latar halaman, kartu, panel filter |
| Teks & aksen | **forest** (`forest-200` … `forest-900`) | Heading, body, badge sukses |
| CTA | **terracotta** (`terracotta-500/600/700`) | **Hanya** untuk aksi utama & state pending — tidak untuk dekorasi, supaya satu-satunya warna hangat pekat di layar selalu berarti "klik ini" |

Tipografi: **Fraunces Variable** (heading/display, serif) + **Inter Variable** (body). Keduanya di-self-host lewat Vite, bukan CDN.

Warna semantik: hijau = `forest` (sukses/kursi tersedia), terracotta = pending/hampir habis, `sand-200` + strikethrough = habis/nonaktif. Merah error dipakai apa adanya dari state form.

Prinsip visual: setiap elemen (3D object, animasi, ilustrasi) harus fungsional, tidak boleh terlihat "dibuat AI". Token warna & font didefinisikan di `resources/css/app.css` (`@theme`).

## Yang Masih Perlu Dikonfirmasi

- [ ] Trip internasional: aktifkan dummy untuk uji coba, atau tetap tutup?
- [ ] Hasil pemilihan design system (2 variasi → pilih 1)
- [ ] Skema komisi platform 3-10% mitra — diterapkan flat atau tiered per kategori? (dibutuhkan sebelum mulai V2)

---
*Dibuat ulang dari nol menggantikan seluruh versi GUIDE.md sebelumnya. Update dokumen ini tiap ada keputusan baru yang mengubah scope, supaya tetap jadi sumber kebenaran tunggal untuk Claude Code CLI.*
