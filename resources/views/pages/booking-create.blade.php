@php
    use App\Enums\IdType;

    $idRequirement = $schedule->trip->category->id_requirement;
    $labelIdentitas = match ($idRequirement) {
        IdType::Nik => 'NIK (16 digit)',
        IdType::Passport => 'Nomor paspor',
        IdType::None => null,
    };

    /*
     * Alpine memegang daftar peserta supaya baris bisa ditambah/dikurangi tanpa
     * reload. Isi awalnya diambil dari input sebelumnya kalau validasi gagal —
     * kalau tidak, satu baris ketua rombongan yang sudah diisi dari profil.
     */
    $pesertaAwal = old('participants', [[
        'full_name' => $profil?->full_name ?? auth()->user()->name,
        'phone' => auth()->user()->phone,
        'id_number' => '',
        'dob' => optional($profil?->dob)->toDateString(),
        'emergency_contact' => $profil?->emergency_contact_phone,
    ]]);
@endphp

<x-layouts.app :title="'Pesan '.$schedule->trip->title">
    <div class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <a href="{{ route('trips.show', $schedule->trip) }}" class="text-sm text-teal-600 hover:text-amber-600">
            &larr; Kembali ke detail trip
        </a>

        <p class="mt-6 text-xs font-semibold tracking-widest text-teal-500 uppercase">
            {{ $schedule->trip->category->name }}
        </p>

        <h1 class="mt-2 font-display text-3xl leading-tight font-bold text-teal-900 sm:text-4xl">
            {{ $schedule->trip->title }}
        </h1>

        <div class="mt-8 rounded-3xl border border-mist-200 bg-white p-6 sm:p-8">
            <dl class="grid gap-5 sm:grid-cols-3">
                <div>
                    <dt class="inline-flex items-center gap-1.5 text-xs tracking-wide text-teal-500 uppercase"><x-lucide-calendar class="size-3.5" aria-hidden="true" />Tanggal berangkat</dt>
                    <dd class="mt-1 font-display text-lg font-bold text-teal-900">
                        {{ $schedule->start_date->translatedFormat('j F Y') }}
                    </dd>
                </div>

                <div>
                    <dt class="inline-flex items-center gap-1.5 text-xs tracking-wide text-teal-500 uppercase"><x-lucide-armchair class="size-3.5" aria-hidden="true" />Sisa kuota</dt>
                    <dd class="mt-1 font-display text-lg font-bold text-teal-900">
                        {{ $schedule->remainingQuota() }} kursi
                    </dd>
                </div>

                <div>
                    <dt class="inline-flex items-center gap-1.5 text-xs tracking-wide text-teal-500 uppercase"><x-lucide-wallet class="size-3.5" aria-hidden="true" />Mulai dari</dt>
                    <dd class="mt-1">
                        <x-price-tag :amount="$schedule->prices->min('price')" />
                    </dd>
                </div>
            </dl>

            @if ($schedule->prices->count() > 1)
                <div class="mt-6 rounded-2xl bg-mist-100 px-5 py-4">
                    <p class="text-xs font-semibold tracking-widest text-teal-500 uppercase">Harga bertingkat</p>
                    <ul class="mt-2 space-y-1 text-sm text-teal-700">
                        @foreach ($schedule->prices->sortBy('min_pax') as $harga)
                            <li>
                                {{ $harga->label }} ({{ $harga->min_pax }}@if ($harga->max_pax)&ndash;{{ $harga->max_pax }}@else+ @endif orang):
                                <span class="font-medium">Rp{{ number_format($harga->price, 0, ',', '.') }}</span> / orang
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-teal-500">
                        Harga menyesuaikan otomatis dengan jumlah peserta yang Anda isi.
                    </p>
                </div>
            @endif
        </div>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                <p class="font-medium">Ada yang perlu diperbaiki:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bookings.store', $schedule) }}" class="mt-8"
              x-data="{
                  peserta: @js(array_values($pesertaAwal)),
                  {{-- Cap keras 12 vs sisa kuota — yang lebih kecil menang. Angka
                       12-nya dari config, sama dengan yang dipakai validasi server. --}}
                  maks: {{ min(config('booking.max_pax_per_booking'), $schedule->remainingQuota()) }},
                  tambah() { if (this.peserta.length < this.maks) this.peserta.push({ full_name: '', phone: '', id_number: '', dob: '', emergency_contact: '' }) },
                  hapus(i) { if (this.peserta.length > 1) this.peserta.splice(i, 1) },
              }">
            @csrf
            <x-submit-overlay message="Menyiapkan booking…" />

            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-2xl font-bold text-teal-900">Data peserta</h2>
                    <p class="mt-1 text-sm text-teal-600">
                        Peserta pertama menjadi ketua rombongan dan penanggung jawab pemesanan.
                    </p>
                </div>

                <p class="text-sm text-teal-600">
                    <span x-text="peserta.length">1</span> dari maksimal <span x-text="maks">1</span> kursi
                </p>
            </div>

            {{-- Yang membatasi di sini cap 12, bukan kuota — jadi rombongan besar
                 perlu tahu jalan keluarnya, bukan cuma tahu dia mentok. --}}
            @if ($schedule->remainingQuota() > config('booking.max_pax_per_booking'))
                <p class="mt-4 rounded-2xl border border-mist-200 bg-mist-100 px-4 py-3 text-sm leading-relaxed text-teal-700">
                    Satu pemesanan maksimal {{ config('booking.max_pax_per_booking') }} peserta, walau kursi tersisa
                    {{ $schedule->remainingQuota() }}. Rombongan lebih besar kami tangani sebagai
                    <a href="{{ app(\App\Contracts\MessagingService::class)->requestPrivateTrip($schedule->trip) }}"
                       target="_blank" rel="noopener"
                       class="font-medium text-amber-600 underline underline-offset-2 hover:text-amber-700">private trip</a>
                    supaya transport dan titik jemputnya bisa diatur khusus.
                </p>
            @endif

            <div class="mt-6 space-y-5">
                <template x-for="(orang, i) in peserta" :key="i">
                    <fieldset class="rounded-3xl border border-mist-200 bg-white p-5 sm:p-6">
                        <legend class="px-2 text-xs font-semibold tracking-widest text-teal-500 uppercase"
                                x-text="i === 0 ? 'Ketua rombongan' : 'Peserta ' + (i + 1)"></legend>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label :for="'peserta-nama-' + i" class="block text-sm font-medium text-teal-800">Nama lengkap</label>
                                <input :id="'peserta-nama-' + i" :name="'participants[' + i + '][full_name]'" type="text"
                                       x-model="orang.full_name" required maxlength="255"
                                       class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 focus:border-teal-500">
                            </div>

                            <div>
                                <label :for="'peserta-hp-' + i" class="block text-sm font-medium text-teal-800">
                                    Nomor HP <span class="ml-1 text-xs font-normal text-teal-500">(opsional)</span>
                                </label>
                                <input :id="'peserta-hp-' + i" :name="'participants[' + i + '][phone]'" type="tel"
                                       x-model="orang.phone" maxlength="30"
                                       class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 focus:border-teal-500">
                            </div>

                            <div>
                                <label :for="'peserta-lahir-' + i" class="block text-sm font-medium text-teal-800">
                                    Tanggal lahir <span class="ml-1 text-xs font-normal text-teal-500">(opsional)</span>
                                </label>
                                <input :id="'peserta-lahir-' + i" :name="'participants[' + i + '][dob]'" type="date"
                                       x-model="orang.dob"
                                       class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 focus:border-teal-500">
                            </div>

                            @if ($labelIdentitas)
                                <div class="sm:col-span-2">
                                    <label :for="'peserta-identitas-' + i" class="block text-sm font-medium text-teal-800">
                                        {{ $labelIdentitas }}
                                    </label>
                                    <input :id="'peserta-identitas-' + i" :name="'participants[' + i + '][id_number]'" type="text"
                                           x-model="orang.id_number" required
                                           inputmode="{{ $idRequirement === IdType::Nik ? 'numeric' : 'text' }}"
                                           maxlength="{{ $idRequirement === IdType::Nik ? 16 : 12 }}"
                                           class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 focus:border-teal-500">
                                    <p class="mt-1.5 text-xs text-teal-500">
                                        Diwajibkan kategori {{ Str::lower($schedule->trip->category->name) }} untuk pendataan peserta oleh
                                        penyelenggara. Nomor disimpan terenkripsi dan tidak ditampilkan kembali.
                                    </p>
                                </div>
                            @endif

                            <div class="sm:col-span-2">
                                <label :for="'peserta-darurat-' + i" class="block text-sm font-medium text-teal-800">
                                    Kontak darurat <span class="ml-1 text-xs font-normal text-teal-500">(opsional)</span>
                                </label>
                                <input :id="'peserta-darurat-' + i" :name="'participants[' + i + '][emergency_contact]'" type="text"
                                       x-model="orang.emergency_contact" maxlength="255"
                                       class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 focus:border-teal-500">
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end" x-show="i > 0">
                            <button type="button" @click="hapus(i)"
                                    class="text-sm text-red-700 underline underline-offset-4 hover:text-red-800">
                                Hapus peserta ini
                            </button>
                        </div>
                    </fieldset>
                </template>
            </div>

            <button type="button" @click="tambah()" x-show="peserta.length < maks"
                    class="mt-5 rounded-full border border-dashed border-mist-400 px-5 py-2.5 text-sm text-teal-700 hover:border-teal-500">
                + Tambah peserta
            </button>

            @if ($opsi->isNotEmpty())
                <section class="mt-10 rounded-3xl border border-mist-200 bg-white p-6">
                    <h2 class="font-display text-xl font-bold text-teal-900">Tambahan opsional</h2>
                    <p class="mt-1.5 text-sm text-teal-600">
                        Harga per orang, boleh dilewati. Jumlahnya tidak boleh melebihi jumlah peserta.
                    </p>

                    <div class="mt-5 space-y-4">
                        @foreach ($opsi as $pilihan)
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-mist-200 pb-4 last:border-0 last:pb-0">
                                <div>
                                    <p class="text-sm font-medium text-teal-900">{{ $pilihan->name }}</p>
                                    @if ($pilihan->description)
                                        <p class="mt-0.5 text-xs leading-relaxed text-teal-600">{{ $pilihan->description }}</p>
                                    @endif
                                    <p class="mt-1 text-sm text-teal-700">
                                        + Rp{{ number_format($pilihan->extra_price, 0, ',', '.') }} / orang
                                    </p>
                                </div>

                                <label class="flex items-center gap-2 text-sm text-teal-700">
                                    <span>Jumlah</span>
                                    <input type="number" name="options[{{ $pilihan->id }}]" min="0"
                                           :max="peserta.length" inputmode="numeric"
                                           value="{{ old('options.'.$pilihan->id, 0) }}"
                                           class="w-20 rounded-lg border border-mist-300 bg-mist-50 px-3 py-2 text-sm text-teal-900">
                                </label>
                            </div>
                        @endforeach
                    </div>

                    @error('options')
                        <p role="alert" class="mt-3 text-xs text-amber-700">{{ $message }}</p>
                    @enderror
                </section>
            @endif

            <div class="mt-8">
                <label for="voucher" class="block text-sm font-medium text-teal-800">
                    Kode voucher <span class="ml-1 text-xs font-normal text-teal-500">(opsional)</span>
                </label>
                <input id="voucher" name="voucher_code" type="text" maxlength="50"
                       value="{{ old('voucher_code') }}" autocomplete="off"
                       placeholder="Punya kode promo? Tulis di sini."
                       class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm tracking-wide text-teal-900 uppercase placeholder:text-teal-400 placeholder:normal-case focus:border-teal-500">

                @error('voucher_code')
                    <p role="alert" class="mt-2 text-xs text-amber-700">{{ $message }}</p>
                @enderror

                <p class="mt-1.5 text-xs text-teal-500">Potongan dihitung saat pemesanan disimpan, dan tampil di halaman pembayaran.</p>
            </div>

            <div class="mt-8">
                <label for="catatan" class="block text-sm font-medium text-teal-800">
                    Catatan untuk penyelenggara <span class="ml-1 text-xs font-normal text-teal-500">(opsional)</span>
                </label>
                <textarea id="catatan" name="notes" rows="3" maxlength="500"
                          placeholder="Misal: alergi makanan, titik jemput, permintaan khusus."
                          class="mt-2 w-full rounded-2xl border border-mist-300 bg-mist-50 px-4 py-2.5 text-sm text-teal-900 placeholder:text-teal-400 focus:border-teal-500">{{ old('notes') }}</textarea>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <button type="submit"
                        class="rounded-full bg-amber-600 px-7 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                    Lanjut ke pembayaran
                </button>
                <p class="text-sm text-teal-600">
                    Kursi ditahan {{ (int) (config('booking.expiry_minutes') / 60) }} jam setelah pemesanan dibuat.
                </p>
            </div>
        </form>
    </div>
</x-layouts.app>
