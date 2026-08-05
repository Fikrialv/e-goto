# E-GOTO — CHANGELOG

Entri ringkas tiap iterasi selesai (aturan `CLAUDE.md` §6). Terbaru di atas.

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
