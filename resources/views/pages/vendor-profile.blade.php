<x-layouts.app :title="$vendor->business_name">
    {{-- Pita identitas. Yang dijual E-GOTO adalah kepercayaan pada penyelenggara
         di baliknya, jadi hubungan keduanya ditulis terang di paling atas —
         bukan disembunyikan di footer seperti keterangan teknis. --}}
    <section class="border-b border-mist-200 bg-mist-100">
        <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:py-14">
            <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Mitra penyelenggara</p>

            <div class="mt-5 flex flex-col gap-6 sm:flex-row sm:items-center sm:gap-8">
                <div class="size-20 shrink-0 overflow-hidden rounded-2xl border border-mist-300 bg-white sm:size-24">
                    @if ($vendor->logo)
                        <img src="{{ Storage::url($vendor->logo) }}"
                             alt="Logo {{ $vendor->business_name }}"
                             loading="eager" decoding="async"
                             class="h-full w-full object-contain p-2">
                    @else
                        <x-media-fallback icon="handshake" />
                    @endif
                </div>

                <div class="min-w-0">
                    <h1 class="font-display text-3xl leading-tight font-bold text-teal-900 sm:text-4xl">
                        {{ $vendor->business_name }}
                    </h1>

                    <p class="mt-3 inline-flex items-center gap-2 rounded-full border border-teal-200 bg-white px-4 py-1.5 text-sm font-medium text-teal-800">
                        <img src="{{ asset('images/logo2.svg') }}" alt="" width="1536" height="1024"
                             class="h-4 w-auto" aria-hidden="true">
                        E-GOTO <span class="text-teal-400" aria-hidden="true">&times;</span> {{ $vendor->business_name }}
                    </p>
                </div>
            </div>

            @if ($vendor->description)
                <p class="mt-7 max-w-2xl text-base leading-relaxed text-teal-700">
                    {{ $vendor->description }}
                </p>
            @endif

            <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4 border-t border-mist-300 pt-6 text-sm">
                @if ($vendor->address)
                    <div class="min-w-0">
                        <dt class="text-xs tracking-widest text-teal-500 uppercase">Basis operasi</dt>
                        <dd class="mt-1 inline-flex items-center gap-1.5 text-teal-800">
                            <x-lucide-map-pin class="size-4 shrink-0" aria-hidden="true" />
                            {{ $vendor->address }}
                        </dd>
                    </div>
                @endif

                @if ($vendor->approved_at)
                    <div>
                        <dt class="text-xs tracking-widest text-teal-500 uppercase">Bergabung</dt>
                        <dd class="mt-1 text-teal-800">{{ $vendor->approved_at->translatedFormat('F Y') }}</dd>
                    </div>
                @endif

                <div>
                    <dt class="text-xs tracking-widest text-teal-500 uppercase">Trip aktif</dt>
                    <dd class="mt-1 text-teal-800">{{ $trips->total() }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:py-16">
        <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Yang sedang dibuka</p>
        <h2 class="mt-2 font-display text-2xl font-bold text-teal-900 sm:text-3xl">
            Trip dari {{ $vendor->business_name }}
        </h2>

        @if ($trips->isEmpty())
            <div class="mt-8 rounded-3xl border border-mist-200 bg-white px-6 py-14 text-center">
                <x-lucide-search class="mx-auto size-8 text-teal-500" aria-hidden="true" />
                <p class="mt-4 font-display text-lg font-bold text-teal-900">Belum ada trip yang dibuka</p>
                <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-teal-600">
                    Mitra ini belum menayangkan jadwal baru. Coba lihat trip lain dulu, ya.
                </p>
                <a href="{{ route('home') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-full bg-teal-700 px-6 py-2.5 text-sm font-medium text-mist-50 transition-colors hover:bg-teal-800">
                    Lihat semua trip
                    <x-lucide-arrow-right class="size-4" aria-hidden="true" />
                </a>
            </div>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($trips as $trip)
                    <x-trip-card :trip="$trip" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $trips->links() }}
            </div>
        @endif
    </section>
</x-layouts.app>
