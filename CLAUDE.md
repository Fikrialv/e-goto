# CLAUDE.md — Instruksi Wajib untuk Claude Code CLI, Project E-GOTO

Claude Code membaca file ini otomatis dari root repo tiap sesi. Perlakukan sebagai aturan kerja wajib, bukan referensi opsional.

## 0. Baca dulu sebelum kerja apa pun

Sebelum mengerjakan task apa pun, baca `docs/GUIDE.md` (sumber kebenaran *scope*) lalu `docs/PLAN.md` (urutan kerja teknis, skema data, kriteria selesai). Kalau dua dokumen bentrok → **GUIDE menang**, PLAN yang harus diperbaiki, bukan sebaliknya.

`docs/PLAN.md` bagian 6-8 (FASE V1/V1.5/V2) adalah rencana harian D0-D13 — kerjakan berurutan, jangan lompat ke fitur V1.5 sebelum checklist "✅ Selesai kalau" di fase V1 terpenuhi.

## 1. Konteks project

- **Website** responsive (bukan aplikasi native) — booking open trip & aktivitas wisata, mendukung banyak mitra/vendor (marketplace). Solo developer.
- Stack: **Laravel 12 + PHP 8.2 (dev XAMPP; target production Hostinger PHP 8.3) + Filament 3** (2 panel terpisah: `/admin` dan `/vendor`) + **Blade + Alpine.js + Tailwind** (sisi customer, mobile-first tapi wajib responsive semua breakpoint). Jangan usulkan stack lain kecuali diminta eksplisit.
- Hosting target: Hostinger Business (shared hosting) — **tidak ada proses background persisten**. Tidak pakai queue worker daemon. Tugas periodik (expired booking) pakai Scheduled Artisan Command via cron `schedule:run`, bukan queue.
- Browsing publik (homepage, kategori, detail trip) **tanpa login sama sekali**. Login hanya jadi gerbang tepat sebelum booking (lihat PLAN.md 5.5).

## 2. Lensa wajib sebelum eksekusi

Untuk fitur yang menyentuh lebih dari satu aktor (Customer/Vendor/Admin), tinjau singkat trade-off tiap sisi sebelum implementasi. Untuk kode yang menyentuh keamanan (auth, upload bukti bayar, QR check-in, PII NIK/paspor, OAuth), sebutkan eksplisit ancaman relevan (pakai-ganda QR, fraud bukti bayar, overbooking kuota, XSS/SQL injection) dan mitigasinya di penjelasan singkat — bukan cuma di kode.

## 3. Batas scope — patuhi ketat

Yang boleh dikerjakan sekarang mengikuti urutan D0-D13 di `docs/PLAN.md`. **Jangan** kerjakan modul V2 (dashboard pemasukan vendor interaktif, sistem affiliate referral berkomisi) atau backlog (dashboard analitik penuh, ledger, AI Assistant, Web Builder, Ticket Designer custom, payment gateway otomatis, WhatsApp Business API resmi, push notification, audit log lengkap, 2FA, multi-bahasa penuh, dark mode) kecuali diminta eksplisit oleh user.

Kalau task yang diminta terlihat melebar dari daftar D0-D13 yang sedang berjalan, **tanya dulu**, jangan langsung eksekusi.

## 4. Sebelum menulis kode baru

Sebelum menulis fungsi/file baru, tangga keputusan:
1. Apakah benar-benar dibutuhkan sekarang (bukan "siapa tahu nanti perlu")?
2. Sudah ada di codebase ini (fungsi/helper serupa, atau Action/Contract yang sudah ada di `docs/PLAN.md` bagian 3)?
3. Tersedia di Laravel/Filament bawaan?
4. Ada di package yang sudah ter-install?
5. Bisa diselesaikan tanpa abstraksi baru?
6. Baru kalau semua di atas tidak cukup: tulis kode minimum yang diperlukan.

Jangan kompromi tangga ini untuk validasi input, keamanan (hash, signing, enkripsi PII, sanitasi), atau aksesibilitas — bagian itu tetap harus lengkap walau "berlebih" secara baris kode.

**Fondasi extensible wajib** (lihat PLAN.md bagian 3): implementasi V1 lewat `interface PaymentGateway`, `interface MessagingService`, Enum PHP native untuk semua status (jangan string bebas), middleware `role:` sejak awal. Ini bukan over-engineering — ini yang mencegah bongkar ulang saat V1.5/V2 nanti nempel fitur baru.

## 5. Cara kerja teknis

- Password, upload file, token QR **wajib** pakai fungsi bawaan Laravel (`Hash`/bcrypt, validasi `file` mimetype+size, `Crypt`/HMAC signed token) — jangan implementasi kripto sendiri.
- NIK/paspor (`booking_participants.id_number`) **wajib** cast `encrypted`. Kalau butuh lookup/cari, gunakan kolom `id_number_hash` (sha256) terpisah — jangan `where` langsung ke kolom terenkripsi.
- Kuota trip: kunci pakai transaksi DB + `lockForUpdate()` saat booking dibuat — cegah race condition/overbooking.
- Migration hanya untuk tabel yang sudah dijadwalkan di fase berjalan (lihat PLAN.md bagian 4). Jangan buat migration tabel V2/backlog sekarang.
- Commit kecil dan sering (per sub-fitur selesai), bukan satu commit besar di akhir hari.
- Setiap fitur: minimal happy-path test + 1 edge case (Pest). Cek konvensi `tests/` yang sudah ada sebelum bikin file test baru.

## 6. Setelah selesai satu iterasi

Tambahkan entri ringkas di `docs/CHANGELOG.md` dan centang item terkait di `docs/update.md`. Kalau ada keputusan yang mengubah scope, update `docs/GUIDE.md` dulu (sumber kebenaran), baru sesuaikan `docs/PLAN.md`.

## 7. Gaya komunikasi

Ringkas, langsung, jawab dalam Bahasa Indonesia kecuali user minta lain. Jangan ubah kode di luar yang diminta — kalau lihat masalah lain, laporkan dan sarankan, jangan langsung ubah tanpa diminta (kecuali bug yang menghalangi task yang sedang dikerjakan).

## 8. Tooling Claude Code CLI — pasang sendiri, jangan tanya user dulu

- **Laravel Boost — WAJIB dipasang sendiri di D1**, tanpa nanya dulu: `composer require laravel/boost --dev` → `php artisan boost:install` → `claude mcp add laravel-boost -- php artisan boost:mcp`. Ini MCP server resmi tim Laravel, kasih akses dokumentasi Laravel/Filament/Pest semantic search + baca skema DB/log langsung — mengurangi risiko kode salah syntax versi terbaru.
- **Playwright MCP — opsional, pasang sekitar D4-D5** kalau sudah ada alur booking yang bisa dites otomatis: `claude mcp add playwright -- npx -y @playwright/mcp@latest`. Berguna untuk regression test alur booking→bayar→tiket tanpa perlu klik manual berulang tiap ada perubahan kode.
- **Caveman, RTK, plugin marketplace Ponytail** (dari `TOOL.md` versi lama) — itu khusus ekosistem Codex CLI, **tidak dipasang** di sini. Setara bawaan Claude Code CLI: `/compact` untuk kompresi context manual, hooks (`.claude/hooks/`) untuk automasi pre/post-tool, dan slash commands custom (`.claude/commands/*.md`) untuk checklist berulang.
- Prinsip "jangan over-engineer" dari Ponytail sudah dimasukkan langsung ke bagian 4 di atas — tidak bergantung ke plugin eksternal.
- Package/plugin lain yang dibutuhkan spesifik untuk fitur di fase berjalan (bukan tooling AI) — boleh install sendiri asal official/populer, lihat bagian 15.

## 9. Performa — Wajib Ringan

Tujuan produk adalah mempermudah orang, jadi web yang berat justru melawan tujuan itu. Wajib:
- Lazy-load semua gambar non-critical (`loading="lazy"`), kompres & convert ke WebP kalau memungkinkan.
- Tailwind pakai build production (Vite, purge otomatis) — jangan andalkan CDN Tailwind/Alpine untuk production, itu boleh cuma untuk dev cepat.
- Jangan pasang library JS berat (jQuery, animasi library besar) kalau cukup pakai Alpine.js + CSS transition.
- Paginasi untuk daftar panjang (booking, trip) — jangan render semua data sekaligus.
- Cache query yang jarang berubah (kategori, trip published) pakai Laravel cache sewajarnya.
- Sebelum submit fitur selesai, cek ukuran halaman & jumlah request tidak melonjak tanpa alasan jelas.

## 10. Standar Desain — Jangan Terlihat "Dibuat AI"

Halaman customer-facing (homepage, detail trip, checkout) adalah yang pertama dilihat calon user — kualitas visual di sini menentukan dipakai atau ditinggalkan. Hindari pola generik: gradient ungu-biru template, kartu shadow seragam tanpa karakter, ikon stok tanpa konteks, layout "cookie-cutter" ala SaaS landing page generik. Arahkan ke desain yang terasa dirancang khusus: tipografi editorial (ukuran kontras jelas antara heading dan body), whitespace yang disengaja bukan sisa, micro-interaction halus (bukan animasi berlebihan), foto/ilustrasi yang spesifik ke konteks travel Indonesia — bukan stok generik. Kalau ragu arah visual, tanya dulu referensi sebelum eksekusi styling besar-besaran, jangan asal pasang default framework.

## 11. OAuth Google/Facebook — Batas yang Bisa Dieksekusi AI

Claude Code CLI **tidak bisa** mendaftarkan aplikasi OAuth ke Google Cloud Console / Facebook Developer secara otomatis — itu butuh login manusia ke console masing-masing (verifikasi identitas, consent screen). Yang harus dilakukan: buat panduan super detail (`docs/oauth-setup-guide.md`) berisi langkah bernomor persis (field mana diisi apa, tombol mana diklik, di halaman mana) supaya user tinggal ikuti seperti resep tanpa bingung. Setelah user selesai klik manual dan dapat Client ID + Secret, baru Claude Code lanjut pasang ke `.env` dan konfigurasi Socialite. Jangan berhenti di D3 hanya karena kredensial belum ada — kode tetap disiapkan penuh, tombol disembunyikan lewat config flag sampai kredensial masuk.

## 12. Double-Cek Wajib Sebelum Lapor Selesai (berlaku semua sesi, permanen)

Ini bagian dari `CLAUDE.md` sendiri — berlaku otomatis di semua sesi kerja, bukan cuma kalau disebutkan di prompt harian. Sebelum melaporkan task apa pun selesai:
1. Jalankan test terkait (Pest). Kalau ada yang gagal → **perbaiki dulu, jalankan ulang** → ulangi loop ini sampai semua hijau. Jangan lapor "selesai" di tengah loop ini masih merah.
2. Cek ulang log Laravel (`storage/logs`) dan console browser (kalau relevan) — tidak boleh ada error/warning baru yang belum ditangani.
3. Cek migration/seeder masih bersih (`php artisan migrate:fresh --seed` tanpa error) kalau task menyentuh database.
4. Baru setelah 3 poin di atas bersih, lapor selesai dengan ringkas.

## 13. Sesi Kerja Terbatas (reset tiap ±5 jam)

Sebelum mulai kerja apa pun di sesi baru: baca `docs/update.md` dulu, cek bagian "Sesi Terakhir" di paling atas dokumen itu — lanjutkan dari situ, jangan mengulang yang sudah selesai (buang waktu sesi yang terbatas).

Kalau di tengah sesi terasa mepet (context atau waktu), prioritaskan urutan ini:
1. Selesaikan sub-task yang sedang dikerjakan sampai titik aman (test hijau) — jangan berhenti di tengah kode setengah jadi.
2. Update centang di `docs/update.md` untuk yang benar-benar selesai.
3. Tulis catatan di bagian "Sesi Terakhir" di `docs/update.md`: apa yang sedang dikerjakan, langkah persis berikutnya — supaya sesi berikutnya (bisa jadi instance baru setelah reset) langsung lanjut tanpa re-explore dari nol.

Satu prompt harian (D0-D13) **boleh dipecah jadi beberapa sesi** kalau ternyata kompleks — jangan dipaksakan selesai sekaligus kalau berisiko tergesa mendekati batas sesi.

## 14. Laporan Akhir Fase

Setelah checkpoint D7 (V1 selesai) dan D13 (V1.5 selesai), berikan laporan terstruktur, bukan cuma "selesai":
- **Urutan pengerjaan** — daftar D0 sampai terakhir yang sudah dilalui, ringkas per item.
- **Fitur yang sudah jalan & teruji** — sebutkan hasil test yang lulus.
- **Fitur yang masih tertunda/backlog** — beserta alasan (bukan lupa, tapi memang dijadwalkan fase berikutnya).
- **Lokasi hasil** — URL localhost/staging, path file penting yang perlu diketahui.
- **Rekomendasi langkah selanjutnya** — satu paragraf singkat.

## 15. Package & Plugin — Boleh Diinstal Sendiri

Composer/npm package yang dibutuhkan untuk fitur di fase berjalan boleh diinstal langsung tanpa nanya dulu, asal official/populer dan searah ekosistem Laravel/Filament — bukan package eksperimental atau kurang maintained. Kalau ragu soal popularitas/keamanan sebuah package, sebutkan alasan pemilihan sebelum install, jangan diam-diam pasang yang belum jelas rekam jejaknya.

## 16. Pemilihan Model

Default: `/model opusplan` (Opus untuk rencana/keputusan arsitektur, Sonnet untuk eksekusi kode — hemat token, tetap kuat di bagian riskan). Kalau ternyata pakai mode manual: `/model sonnet` untuk kerjaan rutin (CRUD, UI, test), pindah `/model opus` khusus untuk skema database awal, logic anti-fraud (nominal unik, hash duplikat), dan signing token QR — bagian yang mahal diperbaiki kalau salah desain dari awal.

## 17. Command referensi

Untuk command spesifik (Artisan, MySQL, deploy Hostinger), rujuk `docs/Code_Command_per_Stack.md`. Untuk langkah deploy manual, rujuk `docs/hostinger.md`.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.2. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project keeps committed, area-grouped rules in `.ai/rules` (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
