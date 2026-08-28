@props([
    'message' => 'Sedang diproses…',
])

{{--
    Overlay kecil untuk form yang menunggu server (booking, unggah bukti bayar).
    Dipasang DI DALAM <form>: Alpine menempel ke elemen <form> induknya, jadi
    tidak perlu menangani klik tiap tombol satu per satu.

    Overlay hanya menutupi layar; pengiriman formnya tetap ditangani browser
    seperti biasa. Kalau JavaScript mati, form tetap terkirim — cuma tanpa
    overlay.
--}}
<div x-data="{ mengirim: false }"
     x-init="$el.closest('form')?.addEventListener('submit', () => { mengirim = true })">
    <div x-show="mengirim" x-cloak class="overlay-kirim" role="status" aria-live="assertive">
        <div class="flex flex-col items-center gap-4">
            <img src="{{ asset('images/logo2.svg') }}" alt="" width="1536" height="1024"
                 class="overlay-kirim-mark" aria-hidden="true">
            <p class="text-sm font-medium text-teal-800">{{ $message }}</p>
        </div>
    </div>
</div>
