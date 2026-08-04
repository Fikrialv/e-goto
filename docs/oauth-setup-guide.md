# E-GOTO — Panduan OAuth Google & Facebook (dikerjakan manual oleh Anda)

Ini WAJIB dikerjakan Anda sendiri — butuh login akun Google/Facebook pribadi, tidak bisa diwakilkan ke AI. Ikuti berurutan, jangan lompat. Setelah dapat Client ID + Secret di akhir tiap bagian, balik ke Claude Code CLI dan minta dipasang ke `.env`.

---

## A. Google OAuth (Google Auth Platform — UI terbaru 2026)

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

## B. Facebook OAuth (Meta for Developers)

1. Buka **developers.facebook.com**, login dengan akun Facebook Anda.
2. Klik **My Apps** (kanan atas) → **Create App**.
3. Pilih tipe app — kalau ditanya use case, pilih yang berkaitan dengan **Authenticate and request data from users with Facebook Login** (atau "None" kalau opsi itu tidak muncul, nanti Facebook Login ditambah manual di step berikut).
4. Isi **App name** (contoh: "E-GOTO") dan **Contact email** → **Create app**.
5. Di dashboard app yang baru dibuat, cari produk **Facebook Login** → klik **Set up**.
6. Pilih platform **Web**.
7. Masukkan **Site URL**: `http://localhost:8000` untuk lokal (nanti update ke domain production).
8. Masuk ke **Facebook Login → Settings** (menu kiri).
9. Di **Valid OAuth Redirect URIs**, masukkan:
    - Lokal: `http://localhost:8000/auth/facebook/callback`
    - Production nanti: `https://domainanda.com/auth/facebook/callback`
10. **Save Changes**.
11. Buka **Settings → Basic** (menu kiri) — di sini Anda lihat **App ID**.
12. Klik **Show** di sebelah **App Secret**, mungkin diminta masukkan password Facebook Anda lagi untuk verifikasi keamanan.
13. **Copy App ID dan App Secret**, simpan sementara.

**Catatan penting:** app Facebook baru juga mulai dalam mode terbatas (cuma admin/developer/tester app yang bisa login). Untuk soft launch, tambahkan email tester di **App roles → Roles**. Kalau approval publik ternyata butuh **App Review** (biasanya untuk permission yang lebih sensitif dari sekadar email/profil dasar), itu bisa makan beberapa hari — makanya proses ini disarankan dimulai paling awal, paralel dengan development.

---

## Setelah Anda dapat 4 nilai ini:

- Google Client ID
- Google Client Secret
- Facebook App ID
- Facebook App Secret

**Balik ke Claude Code CLI**, paste prompt ini:

```
Ini kredensial OAuth hasil saya daftar manual:
GOOGLE_CLIENT_ID=[tempel-client-id-dari-google-cloud]
GOOGLE_CLIENT_SECRET=[tempel-client-secret-dari-google-cloud]
FACEBOOK_CLIENT_ID=[tempel]
FACEBOOK_CLIENT_SECRET=[tempel]

Pasang ke .env dan konfigurasi Laravel Socialite untuk keduanya sesuai docs/PLAN.md bagian 2. Redirect URI lokal pakai http://localhost:8000, nanti saya update ke domain production saat deploy.
```
