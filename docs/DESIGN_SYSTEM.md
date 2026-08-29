# E-GOTO — Design System

Dokumen ini memformalkan keputusan visual yang sudah dipakai di kode. Sumber kebenaran
scope tetap `docs/GUIDE.md`; dokumen ini menjelaskan **bagaimana** keputusan itu terlihat
di layar. Token warna dan font **tidak** diubah oleh dokumen ini — semuanya sudah final
sejak 2026-08-14 dan hidup di `resources/css/app.css` blok `@theme`.

## Token warna

Sumber tunggal: `resources/css/app.css`. Jangan tulis nilai hex langsung di Blade.

| Peran | Token | Dipakai untuk |
|---|---|---|
| Latar halaman | `--color-background` (#FFFFFF) | latar `body` semua halaman customer |
| Permukaan | `mist-50` … `mist-400` | pita seksi, kartu, garis pemisah |
| Teks & aksen | `teal-50` … `teal-900` | judul, teks isi, tautan, ikon informatif |
| Aksi utama | `amber-500` / `amber-600` / `amber-700` | tombol CTA, eyebrow, fokus |

Amber sengaja dibatasi. Satu-satunya warna hangat di layar harus selalu berarti "aksi
utama" atau "perhatikan ini" — begitu amber dipakai jadi dekorasi, tombol beli kehilangan
keistimewaannya. `amber-500` untuk badge/ikon di atas latar terang, `amber-600` untuk
tombol solid berteks putih (4.7:1). Jangan ditukar tanpa mengukur ulang kontras.

Gradient tidak dipakai. Kalau butuh kedalaman, pakai perbedaan `mist` bertingkat atau
`border` — bukan gradasi (CLAUDE.md §10).

**Satu pengecualian:** bidang fallback gambar (`x-media-fallback`, lihat bagian
"Fallback state" di bawah). Justru gradasi itu yang membedakan bidang kosong yang
disengaja dari kartu berlatar solid di sekitarnya. Di luar itu, gradient tetap dilarang.

### Latar wajib putih — dan kenapa krem dilarang

Latar utama halaman customer adalah **putih murni** (`--color-background`, #FFFFFF).
Kartu memakai `bg-white` penuh, bukan `bg-white/70` — transparansi di atas permukaan
bertint membuat kartu terbaca kotor, bukan lembut.

Pita `mist-100` **tetap boleh** untuk selang-seling seksi (Jadwal Terdekat, footer, seksi
mitra). Token `mist` sudah dingin (#F6FAFA / #EAF3F3, bertint teal), jadi ia memberi jeda
tanpa menghangatkan halaman.

Yang **dilarang** sebagai latar: `amber-50`, `orange-50`, `stone-50`, `yellow-50`,
`neutral-50`, dan hex apa pun yang condong kuning/coklat muda. Alasan teknisnya penting
diingat: token-token itu **tidak** didefinisikan di blok `@theme`, jadi begitu dipakai,
Tailwind memakai palet bawaannya yang hangat — hasilnya krem di samping teal, persis
kesan "template generik" yang dilarang `CLAUDE.md` §10.

`amber-100` (token brand, #FBEBD3) tetap boleh untuk **bidang kecil** — badge peringatan,
lingkaran ikon aksen. Untuk panel atau aside berukuran besar, pakai `bg-white` dengan
border amber: perhatian datang dari garis dan label, bukan dari bidang kuning muda.

### Efek glass (`backdrop-blur`)

Hanya di **kartu kutipan panel halaman masuk** (`x-auth-split`). Di luar itu dilarang —
header, chip di atas foto, dan caption hero memakai latar solid. Blur yang bertebaran
adalah salah satu penanda tercepat "template generik".

## Tipografi

- Judul: `font-display` = Plus Jakarta Sans Variable, `font-weight: 700`, `tracking -0.02em`
- Isi: `font-sans` = Inter Variable
- `.text-hero` — judul terbesar satu halaman, `clamp(2.5rem, 6vw, 4.25rem)`, `line-height 0.95`

Hierarki datang dari **lompatan ukuran**, bukan kenaikan selangkah. Heading dan body
sama-sama sans, jadi kontras tidak datang gratis dari perbedaan serif — berat tebal +
tracking negatif yang menggantikannya.

Pola header seksi yang benar (sudah jadi `x-section-heading`): eyebrow huruf kapital
kecil `tracking-[0.18em]` di atas, judul tebal di bawahnya. **Tanpa ikon dekoratif di
judul** — ikon hanya boleh muncul kalau fungsinya jelas.

## Skala spasi

Nilai berbeda dipilih sadar, bukan satu angka untuk semua (CLAUDE.md §10).

| Konteks | Nilai |
|---|---|
| Jarak antar-seksi halaman | `py-16` sampai `py-24` |
| Jarak judul seksi ke isinya | `mt-8` / `mt-10` |
| Gap antar-kartu di grid | `gap-6` (mobile `gap-5`) |
| Padding dalam kartu | `p-5` (kartu trip), `p-6 sm:p-8` (kartu form) |
| Jarak antar-baris di dalam kartu | `gap-3` |
| Margin bawah heading ke subjudul | `mt-2` / `mt-3` |

Aturan bacanya: makin dalam sarangnya, makin rapat. Jeda antar-seksi tidak boleh sama
dengan gap antar-baris di dalam kartu.

## Ikon

Dua set, keduanya lewat komponen Blade (`blade-ui-kit/blade-icons`) — bukan icon font,
bukan library ikon JavaScript. Ikon dirender PHP-side jadi tidak menambah bundle JS.

- **Lucide** (`mallardduck/blade-lucide-icons`, prefix `lucide`) — semua ikon sisi customer.
  Line-style 24px, cocok dengan garis tipis desain E-GOTO.
- **Heroicons** (`blade-ui-kit/blade-heroicons`, prefix `heroicon`) — sudah terpasang
  sebagai bawaan Filament, dipakai **hanya** di panel admin/vendor supaya konsisten dengan
  ikon navigasi Filament yang sudah ada.

Catatan paket: `codeat3/blade-lucide-icons` yang disebut di rencana awal sudah tidak ada
di Packagist. Penggantinya `mallardduck/blade-lucide-icons` (2.0.8) — repositori penerus
dari maintainer yang sama.

### Daftar ikon yang dipakai

| Konteks | Komponen Blade |
|---|---|
| Kategori gunung / pendakian | `<x-lucide-mountain />` |
| Kategori pantai / laut | `<x-lucide-waves />` |
| Kategori kota / wisata urban | `<x-lucide-building-2 />` |
| Kategori camping | `<x-lucide-tent />` |
| Tanggal keberangkatan | `<x-lucide-calendar />` |
| Jumlah peserta | `<x-lucide-users />` |
| Termasuk (fasilitas ada) | `<x-lucide-check />` |
| Tidak termasuk | `<x-lucide-x />` |
| Durasi / batas waktu bayar | `<x-lucide-clock />` |
| Titik kumpul | `<x-lucide-map-pin />` |
| Dokumen / syarat | `<x-lucide-file-text />` |
| WhatsApp / kontak | `<x-lucide-message-circle />` |
| Galeri foto | `<x-lucide-camera />` |
| Rating | `<x-lucide-star />` |
| Sisa kursi / kuota | `<x-lucide-armchair />` |
| E-tiket | `<x-lucide-ticket />` |
| Harga / pembayaran | `<x-lucide-wallet />` |
| Navigasi mundur / carousel | `<x-lucide-arrow-left />` |
| Navigasi maju / carousel | `<x-lucide-arrow-right />` |
| Unggah bukti bayar | `<x-lucide-upload />` |
| Tampilkan kata sandi | `<x-lucide-eye />` |
| Sembunyikan kata sandi | `<x-lucide-eye-off />` |
| Jadi mitra | `<x-lucide-handshake />` |
| Pencarian trip | `<x-lucide-search />` |
| Panel filter | `<x-lucide-sliders-horizontal />` |
| Kategori umum / aktivitas (fallback slide) | `<x-lucide-compass />` |
| Kategori domestik | `<x-lucide-map />` |
| Kategori internasional | `<x-lucide-globe />` |
| Kategori jalan kaki | `<x-lucide-footprints />` |

Empat baris terakhir tidak dipanggil sebagai komponen Blade, melainkan lewat
`@svg('lucide-'.$icon)` dari kolom `categories.icon`. Daftar nilai yang boleh masuk kolom
itu dikunci di `Category::ICON_OPTIONS` — nama ikon karangan akan melempar saat render,
jadi pilihannya sengaja tertutup, bukan input bebas.

Ukuran baku: `size-5` (20px) untuk ikon dalam teks, `size-6` (24px) untuk ikon berdiri
sendiri di dalam `x-icon-circle`. Ikon yang cuma dekoratif **wajib** `aria-hidden="true"`;
ikon yang jadi satu-satunya isi tombol wajib punya `<span class="sr-only">` penjelas.

### Ikon tidak diwarnai (2026-08-29)

Ikon mewarisi warna teks di sekitarnya, dan warna teks itu selalu dari tangga teal:
`text-teal-900` (judul), `text-teal-700` (isi), `text-teal-500` (keterangan sekunder).

**Amber dilarang untuk ikon** — termasuk bintang rating, yang sebelumnya `text-amber-500`
dan sekarang `text-teal-700`. Amber cuma dipakai untuk tombol aksi dan penanda urgensi
(tenggat, sisa kursi menipis). Begitu ikon ikut amber, penanda urgensinya kehilangan arti:
kalau semua hal kuning, tidak ada yang mendesak.

Pengecualian tunggal: ikon **di dalam** tombol amber mewarisi `text-mist-50` dari tombolnya
— itu warna teks tombol, bukan pewarnaan ikon.

Verifikasi: `grep -rn "lucide.*text-amber" resources/views/` harus nol.

### Ikon hanya di tempat yang fungsinya jelas

Ulangan `CLAUDE.md` §10, karena ini yang paling sering dilanggar: **tidak ada ikon
dekoratif menempel di judul seksi**. Ikon dipasang di penanda baris data, tombol, dan
empty state — tempat yang ikonnya menggantikan pencarian mata, bukan menghiasi.

## Prinsip animasi

Halus, pendek, sekali lihat langsung paham. Tidak ada particle, gradient-shift, parallax,
atau efek 3D.

| Elemen | Gerakan | Durasi |
|---|---|---|
| Tombol & kartu (hover) | `scale 1.02` + bayangan naik satu tingkat | 200ms ease-out |
| Modal / panel | fade + `translateY(4px)` naik | 200ms ease-out |
| Carousel review | fade + geser kecil, lewat Alpine `x-transition` | 250ms |
| Loading screen | pulsing `scale 0.95 → 1 → 0.95`, loop | 1.6s ease-in-out |

Semua animasi memakai `transition`/`animation` CSS atau `x-transition` Alpine. **Tidak
ada library animasi JavaScript** — tidak GSAP, tidak framer-motion, tidak Lottie.

`prefers-reduced-motion: reduce` sudah ditangani global di `app.css` (durasi dipangkas ke
0.01ms). Animasi loop seperti loading screen harus **dimatikan penuh** di media query itu,
bukan sekadar dipercepat — animasi cepat yang berulang justru lebih mengganggu.

## Loading screen

- Memakai `public/images/logo2.svg` — logo bentuk saja (pesawat + swoosh), tanpa tulisan.
- Animasi: pulsing scale. Bukan stroke-dasharray — berkas logo hasil trace berisi ~600
  path terisi (fill), bukan garis (stroke), jadi teknik "menggambar garis" tidak berlaku.
- Tampil sebagai splash saat muat halaman pertama, dan sebagai overlay kecil saat submit
  booking / unggah bukti bayar. **Bukan** di setiap klik tautan biasa.
- Overlay tidak boleh menghalangi interaksi kalau jaringan cepat — splash dilepas pada
  `DOMContentLoaded`, **bukan** `window.load` yang masih menunggu seluruh gambar dan bisa
  menahan layar berdetik-detik di koneksi lambat. Ada `<noscript>` yang menyembunyikannya
  kalau JavaScript mati, dan timer penjaga yang membuang elemennya kalau transisi tidak
  pernah menyala (tab latar, reduced-motion).

## Pemakaian dua berkas logo

Jangan tertukar — beda bentuk, beda tempat.

| Berkas | Isi | Dipakai di |
|---|---|---|
| `public/images/Logo1.svg` | wordmark lengkap bertuliskan "e-goto" | header situs (`h-8`), footer (`h-7`), header e-tiket (`h-6`, `brightness-0 invert` di atas latar gelap), panel hero halaman masuk/daftar, `brandLogo` panel Filament |
| `public/images/logo2.svg` | bentuk saja tanpa tulisan | loading screen, overlay submit, favicon tab |

Alasan pemisahan: wordmark jadi tidak terbaca kalau diperkecil atau dianimasikan; bentuk
saja tetap jelas di ukuran kecil.

Tidak ada berkas logo ketiga. Peran "ikon persegi" (favicon, ikon aplikasi) diambil
`logo2.svg`, dipasang sebagai `<link rel="icon" type="image/svg+xml">` **di atas** tautan
PNG lama supaya browser modern memakainya. Ikon PWA di `manifest.json` masih PNG 192/512
bawaan D7.6 — manifest butuh raster, dan merasterisasi SVG butuh Imagick (GD tidak bisa
membaca SVG), jadi penggantiannya menunggu ekspor raster dari berkas asli.

Header dan footer memakai berkasnya, **bukan teks `E·GOTO`** yang dipakai sebelum
2026-08-29. `width`/`height` ditulis eksplisit di tiap `<img>` supaya tidak ada layout
shift saat SVG-nya dimuat.

Kedua berkas sudah dioptimasi (presisi koordinat dipangkas, `viewBox` ditambahkan) — dari
343 KB/384 KB turun ke ±74 KB/79 KB. Keduanya dipanggil lewat `<img>`, bukan di-inline ke
HTML, supaya bisa di-cache browser dan tidak menggemukkan setiap halaman.

## Pola halaman masuk & daftar — split-panel

Dua kolom di desktop, satu kolom di ponsel.

- **Kolom kiri** — form. Wordmark E-GOTO di atas form sebagai brand mark, lalu judul,
  tombol Google, pemisah "atau pakai email", lalu field. Input memakai `x-glass-input`:
  border lembut + cincin fokus, khusus halaman ini.
- **Kolom kanan** — foto trip asli dari database (bukan foto stok; kalau belum ada sampul
  terunggah, bidangnya jatuh ke `x-media-fallback`), dengan satu-dua kartu
  testimonial melayang di pojok bawah. Statis atau fade-in sekali saat muat — **bukan**
  carousel; carousel hanya ada di halaman detail trip.
- **Ponsel** — kolom kanan disembunyikan (`hidden lg:block`), form jadi lebar penuh.

Toggle tampil/sembunyi kata sandi memakai Alpine `x-data`, ikon `eye`/`eye-off`.

## Komponen Blade

Komponen yang sudah ada: `auth-card`, `chat-widget`, `empty-state`, `form-field`,
`google-button`, `legal-page`, `price-tag`, `section-heading`, `status-badge`, `trip-card`,
`trip-image`.

Ditambahkan pada sesi redesign:

| Komponen | Fungsi |
|---|---|
| `x-badge-chip` | label kecil rounded-full yang menempel di pojok gambar (status kuota, "Populer") |
| `x-icon-circle` | lingkaran latar lembut + ikon 24px, untuk grid kategori |
| `x-stat-bar` | angka besar + label, untuk baris statistik homepage |
| `x-avatar-cluster` | avatar inisial bertumpuk, untuk "dipercaya N peserta" |
| `x-glass-input` | varian input dengan cincin fokus, **khusus** halaman masuk/daftar |
| `x-media-fallback` | bidang pengganti saat foto belum diunggah — satu-satunya tempat gradasi dipakai |

`x-glass-input` sengaja bukan pengganti `x-form-field`. Form booking dan form panel tetap
memakai `x-form-field` — konsistensi form transaksi lebih penting daripada seragam dengan
halaman masuk.

## Hero slider

Sumber slide: trip `is_featured` yang sudah terbit, **maksimal 5**.

- **Kurang dari 2 slide → hero statis.** Slider satu slide dengan satu dot yang tidak
  menuju ke mana-mana hanya menambah bingung.
- Auto-advance **5 detik**, transisi **fade** + geser 4px (250ms) — bukan geser horizontal.
- Berhenti saat kursor di atasnya (`mouseenter`) dan saat ada elemen di dalamnya menerima
  fokus keyboard (`focusin`).
- **Tidak pernah menyala** kalau `prefers-reduced-motion: reduce` aktif — dot tetap bisa
  diklik manual, jadi mematikan gerakan tidak berarti mematikan isinya.
- Dot indicator di bawah, dot aktif melebar; tiap dot punya teks `sr-only` berisi judul
  tripnya, bukan sekadar nomor.
- **Tanpa library carousel pihak ketiga.** Alpine `x-data` + `x-transition` + CSS saja.
- Selama sampul belum diunggah, tiap slide memakai ikon fallback dari `categories.icon`
  masing-masing — supaya lima slide tidak tampil sebagai lima bidang kembar.

### Ukuran dan struktur (diperbaiki 2026-08-29)

- Grid hero: kolom teks `lg:col-span-5`, kolom slider `lg:col-span-7`. Sebelumnya 6/6 dan
  slidernya terbaca terlalu kecil untuk elemen yang jadi hal pertama dilihat orang.
- Rasio naik bertahap: `aspect-[4/3]` → `sm:aspect-[3/2]` → `lg:aspect-[16/10]`, plus
  `lg:min-h-[30rem]` supaya tingginya tidak ikut menyusut saat kolomnya menyempit.
- **Rasio dipegang pembungkus, dan SEMUA slide `absolute inset-0`** — termasuk slide
  pertama. Ini bukan detail gaya: sebelumnya hanya slide pertama yang berada di aliran
  normal, jadi begitu `aktif` berpindah dari 0 pembungkusnya kehilangan tinggi dan
  seluruh hero lenyap dari layar. Bug yang sama juga membuat `<figcaption>` slide pertama
  lari keluar karena tidak punya induk berposisi.

## Fallback state — saat foto belum ada

Sampul trip diunggah mitra belakangan, jadi bidang foto kosong adalah keadaan normal,
bukan kerusakan. Satu komponen menanganinya di semua titik: `x-media-fallback`.

| Titik | Ikon | Lewat |
|---|---|---|
| Kartu trip (`cover_image` null) | `camera` | `x-trip-image` |
| Hero homepage | `mountain` | `x-trip-image` dengan `fallback-icon` |
| Panel kanan halaman masuk/daftar | `map-pin` | dipanggil langsung di `x-auth-split` |
| Seksi "Jadi Mitra" | `handshake` | `x-trip-image` dengan `fallback-icon` |

Bentuknya: gradasi `mist-100 → mist-200 → teal-200` + satu ikon Lucide 48–64px
beropasitas `text-teal-700/25` di tengah, plus label kecil opsional di bawah.

Yang dilarang di posisi ini: **foto stok** dari sumber mana pun (ciri paling cepat
terbaca "dibuat AI" — CLAUDE.md §10) dan **abu-abu polos atau bidang kosong**, yang
membuat halaman terbaca rusak alih-alih sedang menunggu konten. Fallback harus terlihat
sengaja dirancang, tapi juga tidak berpura-pura jadi foto asli.

Semua bidang fallback digambar lokal — nol request ke domain luar.

## Aturan data pada elemen statistik

`x-stat-bar` dan avatar cluster **hanya** menampilkan angka dari query database yang
sudah ada. Kalau datanya nol atau terlalu sedikit untuk berarti, seksinya disembunyikan —
tidak diisi angka karangan. Kredibilitas produk mahal, angka palsu di homepage murah.
