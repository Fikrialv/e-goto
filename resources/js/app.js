import './bootstrap';

// Font di-self-host lewat Vite (bukan CDN Google Fonts) — nol request ke domain
// luar, jadi halaman tidak menunggu pihak ketiga sebelum teks tampil.
import '@fontsource-variable/inter';
import '@fontsource-variable/fraunces';

import Alpine from 'alpinejs';

// Alpine dipakai untuk interaksi kecil di sisi customer (galeri, akordeon
// itinerary, dropdown filter). Panel Filament membundel Alpine sendiri —
// keduanya terpisah dan tidak saling bertabrakan.
window.Alpine = Alpine;
Alpine.start();
