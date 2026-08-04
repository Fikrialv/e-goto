# E-GOTO — CHANGELOG

Entri ringkas tiap iterasi selesai (aturan `CLAUDE.md` §6). Terbaru di atas.

---

## 2026-08-05 — D0: Persiapan paralel

- Repo di-`git init`, branch `main`, commit awal berisi dokumen perencanaan + `CLAUDE.md` + `.claude/settings.json`.
- `.gitignore` Laravel 12 dipasang di root (siap untuk scaffold D1), plus ignore `.claude/settings.local.json`.
- Folder `Docs/` di-rename jadi `docs/` (lowercase) — menyamakan dengan seluruh rujukan di `CLAUDE.md`/`PLAN.md` dan mencegah path gagal resolve di Hostinger (Linux, case-sensitive).
- `docs/PLAN.md` §0 diperbarui ke kondisi faktual; rujukan `GUIDE (3).md` yang sudah tidak ada diganti `docs/GUIDE.md`.
- `docs/CHANGELOG.md` dibuat (sebelumnya diwajibkan `CLAUDE.md` §6 tapi belum pernah ada).
- `docs/oauth-setup-guide.md` diverifikasi sudah lengkap — tidak ditulis ulang.

**Blocker tercatat:** PHP 8.3, Composer, dan MySQL 8 belum terpasang di mesin dev — D1 (scaffold Laravel) tidak bisa dimulai sebelum itu tersedia.
