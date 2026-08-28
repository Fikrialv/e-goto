# E-GOTO — Prompt Gabungan: Redesign Visual & Component System
## (Sesi 9 — satu prompt, dari repo/paket sampai loading screen)

Tempel prompt di bagian **"PROMPT UTAMA"** ke Claude Code CLI sebagai **satu pesan**.
Disarankan pakai **Claude Fable 5** kalau tersedia di model picker Anda — sesi ini
visual-heavy, bukan cuma logic. Kalau Fable belum tersedia saat Anda coba, prompt ini
tetap jalan normal dengan model default Anda, tidak ada bagian yang bergantung fitur
khusus Fable.

Ini **V1+V1.5 sudah selesai secara fungsional (170 test lulus)** — sesi ini murni polish
tampilan, TIDAK ada logic/alur yang berubah. Prompt sudah eksplisit melarang Claude Code
CLI menyentuh business logic.

---

## Langkah 0 — Sebelum masuk sesi (di terminal, bukan di dalam Claude Code CLI)

```bash
npx skills add Leonxlnx/taste-skill
```

Kalau sudah pernah di-install, lewati (cek `claude skills list` dulu, jangan dobel).

---

## PROMPT UTAMA (tempel semua di bawah sebagai satu pesan)

```
Baca dulu docs/update.md bagian "Sesi Terakhir" dan docs/GUIDE.md bagian Design System
sebelum mulai apa pun. Ini sesi REDESIGN VISUAL — tidak ada fitur/alur/logic yang
berubah, murni layer tampilan. JANGAN sentuh business logic booking/payment/ticket/
kuota — kalau ada test yang jadi merah karena perubahan sesi ini, itu tanda ada yang
salah sentuh, perbaiki sebelum lanjut.

===================================================================
KONTEKS — MASALAH YANG DIPERBAIKI
===================================================================
Brand token SUDAH BENAR (warna mist/teal/amber, font Plus Jakarta Sans + Inter) —
JANGAN diganti. Masalahnya: (1) nol icon di seluruh halaman, semua info teks polos,
(2) nol micro-interaction, card/button statis flat, (3) panel admin/vendor 100% default
Filament (dark login tidak match brand, widget starter kit "Filament v3.3.54/
Dokumentasi/GitHub" masih nempel di dashboard, empty state icon X generic), (4) halaman
login masih form polos tanpa treatment visual, (5) homepage/detail trip terasa
"wireframe dikasih warna" — butuh floating card, badge chip, stats bar, icon circle.

===================================================================
REPO & PAKET — PASANG INI, JANGAN PASANG YANG LAIN
===================================================================
Project ini Laravel + Filament + Blade + Alpine.js (lihat CLAUDE.md §1). SAYA PUNYA
referensi visual dari dua komponen React/shadcn/framer-motion (sign-in split-panel
dengan glass input + hero testimonial, dan animated-testimonial carousel) — AMBIL POLA
VISUALNYA SAJA, JANGAN install React/shadcn/framer-motion/@tabler-icons-react ke
project ini. Itu akan merusak arsitektur single-stack yang sudah dipilih sengaja untuk
shared hosting Hostinger (lihat CLAUDE.md §1 soal larangan stack lain + §9 larangan
library JS berat).

Paket yang BOLEH dipasang (ganti hand-drawn SVG icon jadi package resmi, jauh lebih
cepat & konsisten daripada gambar manual):

  composer require blade-ui-kit/blade-icons
  composer require codeat3/blade-lucide-icons

Ini icon set line-style 24px lewat komponen Blade native (<x-lucide-mountain />, dst),
TANPA nambah JS/build step baru. Kalau nama package di atas ternyata sudah tidak
maintained/berbeda versi saat dicoba, cari alternatif resmi terbaru di packagist untuk
"blade icon lucide" atau "blade-ui-kit heroicons" — prinsipnya: icon lewat Blade
component, bukan icon font, bukan npm icon library React.

Alpine.js SUDAH ADA di project (bawaan Laravel) — dipakai untuk carousel testimonial
dan toggle password (pengganti pola useState React di referensi sign-in.tsx).

Repo untuk DIPELAJARI POLA-nya saja (jangan clone/install, cuma referensi baca kode):
- github.com/pterodactyl/panel — pola dashboard admin dengan card lembut + status pill,
  relevan untuk Langkah 5 di bawah
- github.com/spatie/laravel-permission — kalau middleware role belum konsisten,
  cek pola di sini (tapi project sudah punya middleware role sendiri, jangan migrasi
  paksa kalau yang sekarang sudah jalan)

===================================================================
LANGKAH 1 — Dokumen dulu (baca+tulis, JANGAN sentuh kode)
===================================================================
- Kalau docs/DESIGN_SYSTEM.md belum ada, buat dari isi docs/GUIDE.md bagian Design
  System yang sudah ada sekarang (token warna/font JANGAN diubah, cuma diformalkan)
- Tambahkan ke dokumen itu (boleh GUIDE.md langsung kalau itu polanya di project ini):
  a) Daftar icon wajib dari Lucide/Heroicons yang dipakai: mountain, waves (pantai),
     building (kota), tent, calendar, users, check, x, clock, map-pin, file-text,
     message-circle (WA), camera, star, armchair/ticket (kuota), wallet, arrow-left,
     upload — sebutkan nama komponen Blade final yang dipakai per icon
  b) Prinsip animasi: hover scale 1.02 + shadow naik (200ms ease-out) untuk button/
     card, fade+slideUp 200ms untuk modal, carousel testimonial pakai Alpine
     x-transition (bukan JS animation library). TIDAK ADA particle/gradient-shift/3D.
     WAJIB hormati prefers-reduced-motion.
  c) Spesifikasi loading screen (Langkah 6) dan pola split-panel login (Langkah 4)
- Update docs/GUIDE.md bagian Design System kalau ada keputusan baru dari sesi ini
  yang mengubah scope — boleh ditulis ulang, GUIDE tetap sumber kebenaran tunggal

===================================================================
LANGKAH 2 — Pasang icon system
===================================================================
- Install paket dari bagian "Repo & Paket" di atas
- Terapkan icon ke: grid kategori (ganti ilustrasi gunung generic sekarang), list
  "Sudah/Belum termasuk" di detail trip (check/x), meta info card (calendar/map-pin/
  kursi), empty state Filament per konteks (icon beda untuk "belum ada booking" vs
  "belum ada review", jangan satu icon X generic dipakai di semua tempat)

===================================================================
LANGKAH 3 — Component baru + upgrade component lama
===================================================================
Buat komponen Blade baru (reusable, ikuti pola component yang sudah ada di
resources/views/components/):
- x-badge-chip: rounded-full kecil untuk "Sisa 3 kursi" / "Populer" / "Diskon" —
  overlay di pojok gambar card, bukan teks polos di bawah card
- x-icon-circle: lingkaran soft-background + icon 24px, untuk grid kategori
- x-stat-bar: angka besar + label, untuk homepage (jumlah trip terlaksana/mitra aktif/
  peserta) — AMBIL DATA ASLI dari query DB yang sudah ada, JANGAN hardcode angka
  fiktif. Kalau data masih sedikit/kosong, section ini disembunyikan dulu, jangan
  ditampilkan dengan angka karangan.
- x-avatar-cluster: avatar kecil bertumpuk (pakai inisial nama kalau tidak ada foto
  profil, mirip avatar Filament yang sudah ada di panel), untuk dekat rating trip
  ("dipercaya 240+ peserta")
- x-glass-input: varian input dengan border lembut + fokus glow, dipakai khusus di
  halaman login (Langkah 4) — bukan pengganti x-input biasa yang sudah dipakai di
  form booking, cukup dipakai di satu tempat itu saja

Upgrade component yang SUDAH ADA (tambah class Tailwind transition/hover saja,
JANGAN tulis ulang dari nol): button.blade.php, trip-card.blade.php, input.blade.php

===================================================================
LANGKAH 4 — Redesign halaman Login (pola dari referensi sign-in split-panel)
===================================================================
Adaptasi pola dari referensi (BUKAN kode React-nya, cuma layoutnya) ke Blade+Alpine:
- Layout 2 kolom di desktop: kiri form login (pakai x-glass-input, logo1 — versi
  dengan tulisan "e-goto" — ditaruh di atas form sebagai brand mark), kanan foto
  hero trip real (bukan foto generic) dengan 1-2 mini testimonial card melayang di
  pojok bawah (pola avatar+quote singkat, statis atau fade-in sekali saat load —
  BUKAN carousel di halaman login, itu untuk detail trip di Langkah 5)
- Toggle show/hide password: pakai Alpine x-data (pengganti pola useState di
  referensi), icon dari Lucide (eye/eye-off)
- Mobile: kolom kanan (hero+testimonial) disembunyikan, form full width — SAMA
  seperti pola referensi (hidden md:block di kolom hero)
- Tombol "Masuk dengan Google" TETAP seperti sekarang (jangan ubah logic Socialite),
  cuma restyle sesuai token brand + hover state baru

===================================================================
LANGKAH 5 — Testimonial/Rating carousel di detail trip (pola animated-testimonials)
===================================================================
Adaptasi pola carousel (gambar besar + quote + tombol prev/next) ke Alpine.js:
- x-data menyimpan index aktif, x-show/x-transition untuk fade antar review (durasi
  200-300ms, BUKAN 5 layer animasi kompleks kayak referensi React-nya — cukup fade+
  sedikit translate, sesuai prinsip "subtle" di Langkah 1b)
- Tombol panah prev/next bulat kecil (pola dari referensi: rounded-full, icon Lucide
  arrow-left/arrow-right, hover rotate ringan)
- Data dari reviews yang SUDAH ADA di database (fitur rating dari D11) — JANGAN buat
  data review palsu untuk demo, kalau kosong tampilkan empty state yang sudah ada

===================================================================
LANGKAH 6 — Pola visual homepage (dari moodboard travel marketplace)
===================================================================
- Floating search/filter card yang overlap ke foto hero (bukan search bar polos di
  bawah hero)
- Badge chip (x-badge-chip dari Langkah 3) di pojok gambar trip card untuk status
- Grid kategori pakai x-icon-circle (ganti ilustrasi gunung generic)
- Stats bar (x-stat-bar) — ingat aturan data asli dari Langkah 3
- Avatar cluster (x-avatar-cluster) dekat rating di trip card kalau ada review

===================================================================
LANGKAH 7 — Rebrand panel Admin & Vendor (Filament)
===================================================================
- Samakan warna login Filament (sekarang dark generic) ke token brand lewat panel
  config ->colors([...]) — pakai warna yang SUDAH ADA
- Hapus widget starter kit default dari dashboard admin & vendor — override
  getWidgets() jadi array kosong atau widget custom ringkas (HANYA kalau datanya
  sudah tersedia lewat query yang sudah ada, jangan bikin query baru yang berat)
- Ganti empty state icon per Resource sesuai konteksnya (pola dari Pterodactyl Panel
  soal card lembut + status pill boleh jadi referensi visual, warna tetap token
  E-GOTO bukan warna Pterodactyl)

===================================================================
LANGKAH 8 — Loading screen dengan logo E-GOTO
===================================================================
- Logo asli SUDAH ADA di public/images/ — DUA versi, jangan tertukar penggunaannya:
  - logo1 = logo dengan tulisan "e-goto" (wordmark lengkap) — pakai untuk: footer,
    login hero panel (Langkah 4), splash awal sebelum animasi loading mulai (kalau
    mau ada frame diam bertuliskan nama brand dulu)
  - logo2 = logo icon saja tanpa tulisan (cuma bentuk pesawat+swoosh lingkaran) —
    pakai KHUSUS untuk loading screen/spinner (Langkah 8 ini) dan overlay kecil saat
    submit booking/upload bukti, karena bentuknya kompak dan tetap jelas di ukuran
    kecil — logo1 dengan teks penuh akan sulit dibaca kalau di-scale kecil/diputar
  - Cek ekstensi file asli di public/images/ (bisa .png/.jpg/.svg) — kalau BUKAN SVG,
    convert logo2 ke SVG dulu supaya bisa dianimasikan lewat stroke-dasharray/CSS
    tanpa blur di berbagai ukuran layar (raster yang di-scale besar akan pecah)
- SVG + CSS ringan saja, BUKAN Lottie/animasi library berat
- Animasi: PILIH SATU — stroke-dasharray/dashoffset "menggambar" garis lingkaran
  logo2, ATAU pulsing scale 0.95→1→0.95 loop halus pada logo2. Jangan gabung
  keduanya.
- Tampil saat initial page load (splash sebentar) dan opsional overlay kecil saat
  submit booking/upload bukti bayar (bukan splash penuh untuk itu)
- WAJIB hormati prefers-reduced-motion — tampilkan logo statis kalau aktif
- JANGAN pasang di setiap klik link biasa, cuma di titik ada delay network nyata

===================================================================
LANGKAH 9 — Section "Jadi Mitra E-GOTO" (sederhana)
===================================================================
- SATU gambar (foto real trip/mitra, bukan stok generic) + SATU tombol CTA "Jadi
  Mitra E-GOTO" ke halaman onboarding mitra yang sudah ada (D8) — JANGAN dibikin
  kompleks
- Pasang di homepage (sebelum footer) dan profil customer (sudah ada tempatnya dari
  D12, tinggal upgrade visual sesuai pola ini)

===================================================================
VERIFIKASI SEBELUM LAPOR SELESAI
===================================================================
1. php artisan test — semua tetap hijau (sesi ini tidak boleh ubah logic)
2. npm run build sukses, cek ukuran asset tidak melonjak (icon lewat Blade component
   PHP-side, bukan icon font/JS library React yang nambah bundle besar)
3. Cek manual 3 breakpoint (375px/768px/1440px): icon tidak pecah, badge chip tidak
   overflow mobile, login split-panel jadi single-column mobile, loading screen tidak
   nge-block interaksi kalau network cepat
4. Cek prefers-reduced-motion di DevTools — animasi mati/minimal
5. Login /admin dan /vendor — warna brand-consistent, widget starter kit hilang,
   empty state icon kontekstual
6. Pastikan TIDAK ada dependency React/framer-motion/shadcn ter-install (cek
   package.json tetap bersih dari itu)
7. Update docs/update.md checklist + docs/CHANGELOG.md entri baru
8. Kalau docs/GUIDE.md bagian Design System berubah, pastikan konsisten dengan
   docs/DESIGN_SYSTEM.md — jangan sampai dua sumber beda isi

Jawab ringkas. Update centang di update.md + isi "Sesi Terakhir" + entri CHANGELOG.md.
Tutup dengan: "Perlu diupdate:" (file/keputusan yang perlu saya cek manual — terutama
kalau paket blade-icons/lucide yang disebut ternyata sudah berubah nama/deprecated,
dan hasil stats bar kalau datanya masih kosong) dan "Langkah selanjutnya:" (satu
kalimat).
```

---

## Catatan Sebelum Ditempel

1. **Kenapa React di referensi Anda tidak langsung dipasang:** dua komponen yang
   Anda upload (`sign-in.tsx`, `animated-testimonials.tsx`) itu ekosistem
   shadcn/React/framer-motion. Kalau dipasang mentah ke project Laravel+Blade, itu
   butuh build pipeline terpisah (Vite React plugin, TSX compiler) yang tidak
   dibutuhkan project ini dan bertentangan dengan keputusan sengaja "single stack,
   ringan, shared hosting" di `CLAUDE.md` Anda. Solusinya: ambil **pola layoutnya**
   (split-panel glass form, carousel dengan transisi halus), eksekusi pakai
   Alpine.js — hasil visualnya mirip, tapi stack tetap bersih.
2. **Icon jadi package, bukan gambar manual.** Ini saya ubah dari rencana awal
   (Fauzan gambar 18 SVG manual) — pakai `blade-ui-kit/blade-icons` +
   `codeat3/blade-lucide-icons` jauh lebih cepat dan konsisten. Fauzan jadi bisa
   fokus ke foto trip asli & materi promosi, bukan gambar icon garis satu-satu.
3. **Stats bar tetap dilarang pakai angka karangan** — prinsip ini saya pertahankan
   dari draft sebelumnya karena penting untuk kredibilitas produk.
4. **Ini satu sesi besar.** Kalau context/waktu CLI terasa mepet di tengah jalan,
   Claude Code CLI Anda sudah punya instruksi permanen di `CLAUDE.md` §13 untuk
   berhenti di titik aman + catat di "Sesi Terakhir" — jadi aman dilanjut sesi
   berikutnya kalau perlu, tidak wajib selesai sekaligus.
