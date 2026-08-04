# Catatan Project E-GOTO — untuk Konteks Chat Lain

Chat ini (tempat catatan ini dibuat) **khusus membahas pengembangan website E-GOTO**. Kalau Anda buka chat baru di project yang sama untuk topik lain (dokumen legal, video promosi, konten marketing, dll), pakai catatan ringkas ini supaya chat baru tetap nyambung konteks tanpa perlu baca ulang seluruh histori diskusi teknis.

## Apa itu E-GOTO

Website (bukan aplikasi native) untuk booking **open trip & aktivitas wisata** di Indonesia. Diakses dari HP, tablet, laptop, desktop — responsive penuh. Tujuannya jadi wadah marketplace: E-GOTO didukung banyak mitra/vendor wisata (bukan cuma 1 operator).

## Status saat ini

Project **di-restart total dari nol** (versi sebelumnya dinilai kurang rapi). Dokumen perencanaan (`GUIDE.md`, `PLAN.md`, `CLAUDE.md`, `EXECUTION_PROMPTS.md`, `update.md`) sudah selesai disusun di chat ini. Eksekusi kode dilakukan lewat **Claude Code CLI**, desain visual lewat **Claude Design**. Belum mulai coding — masih tahap dokumen final sebelum eksekusi.

## Tiga jenis pengguna

1. **Customer** — bisa lihat-lihat trip tanpa login (browsing bebas), baru diminta login/daftar (manual atau Google/Facebook) saat mau booking. Bayar via QRIS, upload bukti bayar di website, dapat e-tiket QR setelah admin verifikasi.
2. **Vendor/Mitra** — pihak wisata yang gabung ke E-GOTO. Punya trip sendiri, verifikasi tiket QR customer saat check-in di lokasi.
3. **Admin** — kelola trip, verifikasi pembayaran, approve mitra baru. Merangkap fungsi CS (balas pertanyaan/pesan private trip via WhatsApp manual).

## Rencana fitur bertahap

- **V1 (6-7 hari)**: loop transaksi inti — booking, bayar QRIS dengan sistem anti-penipuan (nominal unik per booking, deteksi bukti bayar duplikat), tiket QR otomatis, check-in vendor. Data pribadi (NIK/paspor) terenkripsi.
- **V1.5 (5-7 hari, lanjut langsung)**: mitra bisa daftar sendiri jadi partner E-GOTO ("Jadi Mitra E-GOTO" — bukan sistem referral berkomisi, murni onboarding kerja sama bisnis), mitra bisa ajukan trip sendiri, ada voucher/promo/paket combo, customer bisa kasih rating, ada jalur permintaan private trip via WhatsApp.
- **V2 (belum dimulai)**: dashboard pemasukan untuk mitra — menunggu keputusan skema komisi platform (disepakati 3-10% dari harga mitra, tapi flat atau bertingkat per kategori belum ditentukan).

## Identitas visual (untuk konteks dokumen/marketing/video nanti)

- Warna: hijau-teal (brand utama), navy (otoritas/teks admin), orange (aksen CTA/urgensi)
- Tipografi: Poppins (heading), Inter (body text)
- Prinsip: tidak boleh terlihat "dibuat AI" — hindari gradient generik, ilustrasi stok, 3D abstrak tanpa konteks. Semua elemen visual harus fungsional, merepresentasikan sesuatu yang spesifik dari dunia travel Indonesia (gunung, pantai, kota, dokumen perjalanan).
- Nada komunikasi: memudahkan, tidak kaku, memanjakan pengguna — tapi tetap profesional dan bisa dipercaya (menyangkut uang & data pribadi orang).

## Yang PENTING diketahui kalau bikin konten/dokumen terkait

- Jangan janjikan fitur yang belum ada (voucher, rating, dashboard mitra belum jalan sampai V1.5/V2 selesai)
- Jangan sebut ini "aplikasi" — selalu "website"
- Kalau bikin materi yang menyebut cara pembayaran: QRIS statis + verifikasi manual admin (bukan otomatis instan) — jangan janjikan "pembayaran otomatis real-time" ke customer
- Kalau bikin materi rekrutmen mitra: proses onboarding ada tahap pengajuan, kriteria, jadwal meeting, review dokumen — bukan instan daftar-langsung-jadi-mitra

## File teknis yang sudah jadi (kalau perlu dirujuk)

`GUIDE.md` (scope produk lengkap), `PLAN.md` (rencana teknis harian D0-D13), `CLAUDE.md` (instruksi kerja untuk Claude Code CLI), `EXECUTION_PROMPTS.md` (prompt siap-paste harian), `update.md` (checklist progres). Semua ini murni untuk sisi development — tidak perlu dibawa ke chat non-teknis kecuali memang mau cek detail fitur.
