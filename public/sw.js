/*
 * Service worker minimal (PLAN.md D7.6 a).
 *
 * Yang di-cache HANYA aset statis hasil build Vite (/build/...) dan ikon PWA.
 * Halaman booking, pembayaran, dan tiket TIDAK BOLEH di-cache: kuota, hitung
 * mundur, dan status pembayaran yang basi jauh lebih berbahaya daripada halaman
 * yang tidak bisa dibuka saat offline.
 *
 * Berkas ini berada di root domain supaya cakupannya seluruh situs.
 */

const CACHE = 'egoto-aset-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(['/icons/icon-192.png', '/icons/icon-512.png']))
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((kunci) => Promise.all(kunci.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

function bolehDicache(url) {
    return url.origin === self.location.origin
        && (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/'));
}

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Navigasi dan request non-GET selalu lewat jaringan — dokumen HTML tidak
    // pernah disajikan dari cache, jadi tidak ada halaman uang yang basi.
    if (event.request.method !== 'GET' || event.request.mode === 'navigate' || !bolehDicache(url)) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((tersimpan) => {
            if (tersimpan) {
                return tersimpan;
            }

            return fetch(event.request).then((respons) => {
                if (respons.ok) {
                    const salinan = respons.clone();
                    caches.open(CACHE).then((cache) => cache.put(event.request, salinan));
                }

                return respons;
            });
        })
    );
});

/*
 * Web Push (D12). Ditambahkan di service worker yang sama supaya tidak ada
 * pendaftaran kedua — aturan cache di atas tidak berubah sedikit pun:
 * dokumen HTML tetap tidak pernah disajikan dari cache.
 *
 * Isi notifikasi datang dari server dan sengaja dibatasi kode booking, judul
 * trip, dan waktu. Jangan menambahkan data identitas di sini.
 */
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let isi = {};

    try {
        isi = event.data.json();
    } catch (e) {
        isi = { title: 'E-GOTO', body: event.data.text() };
    }

    event.waitUntil(
        self.registration.showNotification(isi.title || 'E-GOTO', {
            body: isi.body || '',
            icon: '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            data: { url: isi.url || '/booking-saya' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const tujuan = event.notification.data?.url || '/booking-saya';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((daftar) => {
            for (const klien of daftar) {
                if (klien.url.includes(tujuan) && 'focus' in klien) {
                    return klien.focus();
                }
            }

            return self.clients.openWindow(tujuan);
        })
    );
});
