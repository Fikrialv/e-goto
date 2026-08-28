---
description: Nyalakan RTK + caveman mode sekaligus
---

Nyalakan dua hal ini sekarang, lalu lapor status singkat:

1. **RTK** — verifikasi hook aktif: jalankan `rtk --version` dan `rtk gain`. Kalau `rtk hook claude` belum ada di `PreToolUse` pada `~/.claude/settings.json`, laporkan dan tawarkan pasang. Mulai sekarang semua command shell pakai prefix `rtk` (termasuk di dalam rantai `&&`).
2. **Caveman** — panggil skill `caveman:caveman` dengan argumen `$ARGUMENTS` (kosong = level `full`).

Lapor dalam 2 baris: status RTK (versi + total penghematan), status caveman (level aktif). Tanpa basa-basi.
