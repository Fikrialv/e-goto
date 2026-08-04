# CLAUDE.md — Instruksi Wajib untuk Claude Code CLI, Project E-GOTO

Claude Code membaca file ini otomatis dari root repo tiap sesi. Perlakukan sebagai aturan kerja wajib, bukan referensi opsional.

## 0. Baca dulu sebelum kerja apa pun

Sebelum mengerjakan task apa pun, baca `docs/GUIDE.md` (sumber kebenaran *scope*) lalu `docs/PLAN.md` (urutan kerja teknis, skema data, kriteria selesai). Kalau dua dokumen bentrok → **GUIDE menang**, PLAN yang harus diperbaiki, bukan sebaliknya.

`docs/PLAN.md` bagian 6-8 (FASE V1/V1.5/V2) adalah rencana harian D0-D13 — kerjakan berurutan, jangan lompat ke fitur V1.5 sebelum checklist "✅ Selesai kalau" di fase V1 terpenuhi.

## 1. Konteks project

- **Website** responsive (bukan aplikasi native) — booking open trip & aktivitas wisata, mendukung banyak mitra/vendor (marketplace). Solo developer.
- Stack: **Laravel 12 + PHP 8.3 + Filament 3** (2 panel terpisah: `/admin` dan `/vendor`) + **Blade + Alpine.js + Tailwind** (sisi customer, mobile-first tapi wajib responsive semua breakpoint). Jangan usulkan stack lain kecuali diminta eksplisit.
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
