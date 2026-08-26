@props([
    'eyebrow' => 'Informasi',
    'title',
    'intro' => null,
    'updated' => '14 Agustus 2026',
])

{{--
    Kerangka bersama tiga halaman legal (FAQ, S&K, Privasi). Lebar teks dikunci
    ~65 karakter: halaman ini dibaca berparagraf, bukan dipindai seperti daftar
    trip, jadi baris panjang penuh layar justru bikin orang berhenti membaca.
--}}
<x-layouts.app :title="$title">
    <article class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
        <header class="border-b border-mist-200 pb-8">
            <p class="text-xs font-semibold tracking-[0.18em] text-amber-600 uppercase">{{ $eyebrow }}</p>
            <h1 class="font-display mt-3 text-4xl leading-[1.05] font-extrabold tracking-[-0.03em] text-teal-900 sm:text-5xl">
                {{ $title }}
            </h1>

            @if ($intro)
                <p class="mt-5 text-base leading-relaxed text-teal-600 sm:text-lg">{{ $intro }}</p>
            @endif

            <p class="mt-5 text-xs text-teal-500">Terakhir diperbarui: {{ $updated }}</p>
        </header>

        <div class="mt-10 space-y-10">
            {{ $slot }}
        </div>

        <footer class="mt-14 border-t border-mist-200 pt-6 text-sm text-teal-600">
            Ada yang belum terjawab? Hubungi admin lewat WhatsApp yang tertera di halaman pembayaran,
            atau baca <a href="{{ route('pages.faq') }}" class="text-amber-600 underline underline-offset-2 hover:text-amber-700">FAQ</a>.
        </footer>
    </article>
</x-layouts.app>
