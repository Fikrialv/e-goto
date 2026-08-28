import './bootstrap';

// Font di-self-host lewat Vite (bukan CDN Google Fonts) — nol request ke domain
// luar, jadi halaman tidak menunggu pihak ketiga sebelum teks tampil.
import '@fontsource-variable/inter';
import '@fontsource-variable/plus-jakarta-sans';

import Alpine from 'alpinejs';

// Alpine dipakai untuk interaksi kecil di sisi customer (galeri, akordeon
// itinerary, dropdown filter). Panel Filament membundel Alpine sendiri —
// keduanya terpisah dan tidak saling bertabrakan.
window.Alpine = Alpine;
Alpine.start();

/*
 * Splash awal dilepas begitu DOM siap — bukan pada `window.load`, yang masih
 * menunggu seluruh gambar selesai dan bisa menahan layar berdetik-detik di
 * koneksi lambat. Elemennya dibuang, bukan sekadar disembunyikan, supaya tidak
 * ada lapisan transparan yang menghalangi klik.
 */
function lepasSplash() {
    const splash = document.getElementById('splash');

    if (!splash) {
        return;
    }

    splash.classList.add('splash-selesai');
    splash.addEventListener('transitionend', () => splash.remove(), { once: true });

    // Penjaga terakhir: kalau transisi tidak pernah menyala (tab latar,
    // prefers-reduced-motion), elemennya tetap harus hilang.
    setTimeout(() => splash.remove(), 600);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', lepasSplash);
} else {
    lepasSplash();
}

// Service worker cuma meng-cache aset statis (lihat public/sw.js). Didaftarkan
// setelah load supaya tidak berebut bandwidth dengan render pertama.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Registrasi gagal (mis. dibuka lewat http non-localhost) bukan
            // alasan menampilkan galat ke pengunjung — situsnya tetap jalan.
        });
    });
}
