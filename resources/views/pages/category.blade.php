<x-layouts.app :title="$category->name">
    <section class="border-b border-mist-200 bg-mist-100">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <nav class="text-xs text-teal-500" aria-label="Remah roti">
                <a href="{{ route('home') }}" class="hover:text-amber-600">Beranda</a>
                <span aria-hidden="true"> / </span>
                <span class="text-teal-700">{{ $category->name }}</span>
            </nav>

            <h1 class="font-display mt-3 text-4xl leading-tight font-bold text-teal-900 sm:text-5xl">{{ $category->name }}</h1>

            <p class="mt-3 max-w-xl text-sm leading-relaxed text-teal-600 sm:text-base">
                {{ $trips->total() }} trip dengan jadwal yang masih terbuka.
                @if ($category->id_requirement->value === 'nik')
                    Kategori ini butuh NIK tiap peserta saat pemesanan.
                @elseif ($category->id_requirement->value === 'passport')
                    Kategori ini butuh nomor paspor tiap peserta saat pemesanan.
                @endif
            </p>

            <ul class="mt-6 flex flex-wrap gap-2">
                @foreach ($categories as $item)
                    <li>
                        <a href="{{ route('categories.show', $item) }}"
                           @class([
                               'inline-block rounded-full px-4 py-2 text-sm transition-colors',
                               'bg-teal-800 text-mist-50' => $item->is($category),
                               'border border-mist-300 text-teal-700 hover:border-teal-400' => ! $item->is($category),
                           ])>{{ $item->name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="lg:grid lg:grid-cols-12 lg:gap-10">
            {{-- Filter: panel tetap di desktop, dilipat di mobile supaya hasil tidak terdorong jauh ke bawah. --}}
            <aside x-data="{ filterTerbuka: false }" class="lg:col-span-4 xl:col-span-3">
                <button type="button" @click="filterTerbuka = !filterTerbuka"
                        class="flex w-full items-center justify-between rounded-xl border border-mist-300 px-4 py-3 text-sm text-teal-700 lg:hidden"
                        :aria-expanded="filterTerbuka" aria-controls="panel-filter">
                    <span>Filter &amp; urutkan</span>
                    <span aria-hidden="true" x-text="filterTerbuka ? '−' : '+'">+</span>
                </button>

                {{-- Kelas toggle, bukan x-show: tanpa JS pun panel tetap tampil di desktop. --}}
                <form id="panel-filter" method="GET" action="{{ route('categories.show', $category) }}"
                      :class="filterTerbuka ? 'block' : 'hidden lg:block'"
                      class="mt-3 hidden space-y-5 rounded-2xl border border-mist-200 bg-white/70 p-5 lg:mt-0 lg:block">
                    {{--
                        Level fisik ditaruh paling atas: di kategori pendakian inilah
                        yang paling menentukan orang jadi ikut atau tidak, jauh di atas
                        soal tanggal. Radio, bukan select — pilihannya cuma empat dan
                        semuanya layak terlihat sekaligus.
                    --}}
                    <fieldset>
                        <legend class="text-xs font-semibold tracking-wide text-teal-500 uppercase">Tingkat kesulitan</legend>

                        @if ($category->slug === 'pendakian')
                            <p class="mt-1.5 text-xs leading-relaxed text-teal-600">
                                Beda jalur beda beratnya. Pilih yang sesuai kondisi fisik Anda sekarang, bukan yang ingin dicapai.
                            </p>
                        @endif

                        <div class="mt-3 space-y-2">
                            <label class="flex items-center gap-2.5">
                                <input type="radio" name="level" value=""
                                       @checked(empty($filters['level']))
                                       class="border-mist-400 text-teal-700 focus:ring-teal-600">
                                <span class="text-sm text-teal-700">Semua tingkat</span>
                            </label>

                            @foreach (App\Enums\TripDifficulty::cases() as $level)
                                <label class="flex items-center gap-2.5">
                                    <input type="radio" name="level" value="{{ $level->value }}"
                                           @checked(($filters['level'] ?? null) === $level->value)
                                           class="border-mist-400 text-teal-700 focus:ring-teal-600">
                                    <span class="text-sm text-teal-700">{{ $level->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xs font-semibold tracking-wide text-teal-500 uppercase">Tanggal berangkat</legend>
                        <div class="mt-3 space-y-3">
                            <label class="block">
                                <span class="text-xs text-teal-600">Dari</span>
                                <input type="date" name="tanggal_mulai" value="{{ $filters['tanggal_mulai'] ?? '' }}"
                                       class="mt-1 w-full rounded-lg border border-mist-300 bg-mist-50 px-3 py-2 text-sm text-teal-800">
                            </label>
                            <label class="block">
                                <span class="text-xs text-teal-600">Sampai</span>
                                <input type="date" name="tanggal_akhir" value="{{ $filters['tanggal_akhir'] ?? '' }}"
                                       class="mt-1 w-full rounded-lg border border-mist-300 bg-mist-50 px-3 py-2 text-sm text-teal-800">
                            </label>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xs font-semibold tracking-wide text-teal-500 uppercase">Rentang harga (Rp)</legend>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs text-teal-600">Minimum</span>
                                <input type="number" name="harga_min" min="0" step="50000" inputmode="numeric"
                                       value="{{ $filters['harga_min'] ?? '' }}"
                                       class="mt-1 w-full rounded-lg border border-mist-300 bg-mist-50 px-3 py-2 text-sm text-teal-800">
                            </label>
                            <label class="block">
                                <span class="text-xs text-teal-600">Maksimum</span>
                                <input type="number" name="harga_max" min="0" step="50000" inputmode="numeric"
                                       value="{{ $filters['harga_max'] ?? '' }}"
                                       class="mt-1 w-full rounded-lg border border-mist-300 bg-mist-50 px-3 py-2 text-sm text-teal-800">
                            </label>
                        </div>
                    </fieldset>

                    <label class="block">
                        <span class="text-xs font-semibold tracking-wide text-teal-500 uppercase">Urutkan</span>
                        <select name="urut" class="mt-2 w-full rounded-lg border border-mist-300 bg-mist-50 px-3 py-2 text-sm text-teal-800">
                            @foreach (['terdekat' => 'Tanggal terdekat', 'termurah' => 'Harga termurah', 'termahal' => 'Harga termahal'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['urut'] ?? 'terdekat') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    @error('tanggal_akhir')
                        <p class="text-xs text-amber-700">{{ $message }}</p>
                    @enderror
                    @error('harga_max')
                        <p class="text-xs text-amber-700">{{ $message }}</p>
                    @enderror

                    <div class="flex gap-3 pt-1">
                        <button type="submit"
                                class="flex-1 rounded-full bg-amber-600 px-4 py-2.5 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                            Terapkan
                        </button>
                        <a href="{{ route('categories.show', $category) }}"
                           class="rounded-full border border-mist-300 px-4 py-2.5 text-sm text-teal-700 transition-colors hover:border-teal-400">
                            Reset
                        </a>
                    </div>
                </form>
            </aside>

            <section class="mt-8 lg:col-span-8 lg:mt-0 xl:col-span-9">
                @if ($trips->isEmpty())
                    <x-empty-state
                        title="Tidak ada trip yang cocok"
                        message="Coba longgarkan rentang tanggal atau harga — jadwal baru terus ditambahkan penyelenggara.">
                        <a href="{{ route('categories.show', $category) }}"
                           class="inline-block rounded-full border border-mist-300 px-5 py-2.5 text-sm text-teal-700 hover:border-teal-400">
                            Hapus filter
                        </a>
                    </x-empty-state>
                @else
                    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($trips as $trip)
                            <x-trip-card :trip="$trip" />
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $trips->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
