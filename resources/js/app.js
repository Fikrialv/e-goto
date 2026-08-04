import './bootstrap';

import Alpine from 'alpinejs';

// Alpine dipakai untuk interaksi kecil di sisi customer (galeri, akordeon
// itinerary, dropdown filter). Panel Filament membundel Alpine sendiri —
// keduanya terpisah dan tidak saling bertabrakan.
window.Alpine = Alpine;
Alpine.start();
