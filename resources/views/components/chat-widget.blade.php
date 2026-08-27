@php($widgetId = config('partner.chat_widget_id'))
@php($tautanWhatsApp = app(App\Contracts\MessagingService::class)->generalEnquiry())

{{--
    Widget chat pihak ketiga (D12). Tanpa CHAT_WIDGET_ID di .env, tidak ada satu
    baris pun yang dirender — pola yang sama dengan tombol Google sebelum
    kredensialnya masuk.

    Batas keras: widget ini HANYA untuk tanya-jawab umum/CS. Approve/reject
    pembayaran dan penerbitan tiket tetap wajib lewat layar verifikasi Filament
    (D5) — jangan pernah menautkan aksi uang atau tiket dari sini.

    Penyedia yang dipakai wajib tercantum di /kebijakan-privasi: isi percakapan,
    nama, dan kontak yang diketik pengunjung mengalir ke server mereka.
--}}
@if (filled($widgetId))
    @php($penyedia = config('partner.chat_widget_provider'))

    <div class="fixed bottom-4 left-4 z-40">
        <a href="{{ $tautanWhatsApp }}" target="_blank" rel="noopener"
           class="inline-flex items-center rounded-full bg-teal-800 px-4 py-2.5 text-sm font-medium text-mist-50 shadow-lg transition-colors hover:bg-teal-900">
            Lanjut ke WhatsApp
        </a>
    </div>

    @if ($penyedia === 'crisp')
        <script>
            window.$crisp = [];
            window.CRISP_WEBSITE_ID = @json($widgetId);
            (function () {
                const s = document.createElement('script');
                s.src = 'https://client.crisp.chat/l.js';
                s.async = true;
                document.head.appendChild(s);
            })();
        </script>
    @else
        <script>
            window.Tawk_API = window.Tawk_API || {};
            (function () {
                const s = document.createElement('script');
                s.src = 'https://embed.tawk.to/' + @json($widgetId) + '/default';
                s.async = true;
                s.charset = 'UTF-8';
                s.setAttribute('crossorigin', '*');
                document.head.appendChild(s);
            })();
        </script>
    @endif
@endif
