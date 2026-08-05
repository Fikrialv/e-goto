# E-GOTO — Panduan OAuth Google (dikerjakan manual oleh Anda)

Ini WAJIB dikerjakan Anda sendiri — butuh login akun Google pribadi, tidak bisa diwakilkan ke AI. Ikuti berurutan, jangan lompat. Setelah dapat Client ID + Secret di akhir, balik ke Claude Code CLI dan minta dipasang ke `.env`.

> Login Facebook **tidak dipakai** di E-GOTO (keputusan 2026-08-05). Satu-satunya login pihak ketiga adalah Google.

---

## Google OAuth (Google Auth Platform — UI terbaru 2026)

1. Buka **console.cloud.google.com**, login dengan akun Google Anda.
2. Buat project baru (atau pilih project yang sudah ada khusus E-GOTO) — jangan campur dengan project lain, supaya gampang di-manage nanti.
3. Di menu kiri, cari **Google Auth Platform** (ini nama baru, dulu namanya "OAuth consent screen" di bawah APIs & Services).
4. Masuk ke tab **Overview** → klik **Get started**.
5. Isi **App name** (contoh: "E-GOTO") dan **User support email** (email Anda) → klik **Next**.
6. Di bagian **Audience**, pilih **External** (supaya semua orang dengan akun Google bisa login, bukan cuma internal organisasi Anda) → **PENTING: pilihan ini tidak bisa diubah nanti tanpa bikin project baru, jadi pastikan pilih External**.
7. Lanjutkan wizard sampai selesai (isi contact email developer, dsb) → **Save**.
8. Setelah consent screen selesai, buka **Google Auth Platform → Clients** (atau **APIs & Services → Credentials** kalau navigasi versi lama muncul).
9. Klik **Create Client** → pilih **Application type: Web application**.
10. Beri nama (contoh: "E-GOTO Web").
11. Di **Authorized redirect URIs**, klik **Add URI**, masukkan:
    - Untuk lokal: `http://localhost:8000/auth/google/callback`
    - Untuk production nanti (setelah domain aktif): `https://domainanda.com/auth/google/callback`
12. Klik **Create**.
13. Anda akan melihat **Client ID** dan **Client Secret** — **copy keduanya**, simpan sementara (jangan share ke siapa pun selain ke Claude Code CLI Anda sendiri saat setup `.env`).

**Catatan penting:** app External Anda otomatis mulai dalam mode **"Testing"** — cuma email yang Anda daftarkan di **Test users** yang bisa login duluan. Untuk soft launch 5-10 user pertama, tambahkan email mereka satu-satu di bagian **Audience → Test users** (maks 100 email). Kalau nanti mau publik penuh tanpa batas, ada proses **Publish app** terpisah — untuk scope login dasar (email/profil), ini bisa langsung dipublish tanpa review manual dari Google.

---

## Setelah Anda dapat 2 nilai ini:

- Google Client ID
- Google Client Secret

Buka `.env` di root project, isi dua baris yang sudah disiapkan (slot-nya sudah ada, tinggal ditempel):

```
GOOGLE_CLIENT_ID=[tempel-client-id-dari-google-cloud]
GOOGLE_CLIENT_SECRET=[tempel-client-secret-dari-google-cloud]
```

Tombol "Masuk dengan Google" di halaman `/masuk` dan `/daftar` **muncul sendiri** begitu dua nilai itu terisi — tidak perlu ubah kode. Kalau kosong, tombolnya sengaja disembunyikan supaya tidak ada tombol yang pasti error.

Jalankan `php artisan config:clear` setelah menempel, lalu coba klik tombolnya.
