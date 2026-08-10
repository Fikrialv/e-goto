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
        <a href="{{ route('trips.show', $schedule->trip) }}" class="text-sm text-forest-600 hover:text-terracotta-600">
            &larr; Kembali ke detail trip
        </a>

        <p class="mt-6 text-xs font-semibold tracking-widest text-forest-500 uppercase">
            {{ $schedule->trip->category->name }}
        </p>

        <h1 class="mt-2 font-display text-3xl leading-tight font-semibold text-forest-900 sm:text-4xl">
            {{ $schedule->trip->title }}
        </h1>

        <div class="mt-8 rounded-3xl border border-sand-200 bg-white/70 p-6 sm:p-8">
            <dl class="grid gap-5 sm:grid-cols-3">
                <div>
                    <dt class="text-xs tracking-wide text-forest-500 uppercase">Tanggal berangkat</dt>
                    <dd class="mt-1 font-display text-lg font-semibold text-forest-900">
                        {{ $schedule->start_date->translatedFormat('j F Y') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs tracking-wide text-forest-500 uppercase">Sisa kuota</dt>
                    <dd class="mt-1 font-display text-lg font-semibold text-forest-900">
                        {{ $schedule->remainingQuota() }} kursi
                    </dd>
                </div>

                <div>
                    <dt class="text-xs tracking-wide text-forest-500 uppercase">Mulai dari</dt>
                    <dd class="mt-1">
                        <x-price-tag :amount="$schedule->prices->min('price')" />
                    </dd>
                </div>
            </dl>

            @if ($schedule->prices->count() > 1)
                <div class="mt-6 rounded-2xl bg-sand-100 px-5 py-4">
                    <p class="text-xs font-semibold tracking-widest text-forest-500 uppercase">Harga bertingkat</p>
                    <ul class="mt-2 space-y-1 text-sm text-forest-700">
                        @foreach ($schedule->prices->sortBy('min_pax') as $harga)
                            <li>
                                {{ $harga->label }} ({{ $harga->min_pax }}@if ($harga->max_pax)&ndash;{{ $harga->max_pax }}@else+ @endif orang):
                                <span class="font-medium">Rp{{ number_format($harga->price, 0, ',', '.') }}</span> / orang
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-forest-500">
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
                  maks: {{ min(20, $schedule->remainingQuota()) }},
                  tambah() { if (this.peserta.length < this.maks) this.peserta.push({ full_name: '', phone: '', id_number: '', dob: '', emergency_contact: '' }) },
                  hapus(i) { if (this.peserta.length > 1) this.peserta.splice(i, 1) },
              }">
            @csrf

            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-2xl font-semibold text-forest-900">Data peserta</h2>
                    <p class="mt-1 text-sm text-forest-600">
                        Peserta pertama menjadi ketua rombongan dan penanggung jawab pemesanan.
                    </p>
                </div>

                <p class="text-sm text-forest-600">
                    <span x-text="peserta.length">1</span> dari maksimal <span x-text="maks">1</span> kursi
                </p>
            </div>

            <div class="mt-6 space-y-5">
                <template x-for="(orang, i) in peserta" :key="i">
                    <fieldset class="rounded-3xl border border-sand-200 bg-white/70 p-5 sm:p-6">
                        <legend class="px-2 text-xs font-semibold tracking-widest text-forest-500 uppercase"
                                x-text="i === 0 ? 'Ketua rombongan' : 'Peserta ' + (i + 1)"></legend>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label :for="'peserta-nama-' + i" class="block text-sm font-medium text-forest-800">Nama lengkap</label>
                                <input :id="'peserta-nama-' + i" :name="'participants[' + i + '][full_name]'" type="text"
                                       x-model="orang.full_name" required maxlength="255"
                                       class="mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 focus:border-forest-500">
                            </div>

                            <div>
                                <label :for="'peserta-hp-' + i" class="block text-sm font-medium text-forest-800">
                                    Nomor HP <span class="ml-1 text-xs font-normal text-forest-500">(opsional)</span>
                                </label>
                                <input :id="'peserta-hp-' + i" :name="'participants[' + i + '][phone]'" type="tel"
                                       x-model="orang.phone" maxlength="30"
                                       class="mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 focus:border-forest-500">
                            </div>

                            <div>
                                <label :for="'peserta-lahir-' + i" class="block text-sm font-medium text-forest-800">
                                    Tanggal lahir <span class="ml-1 text-xs font-normal text-forest-500">(opsional)</span>
                                </label>
                                <input :id="'peserta-lahir-' + i" :name="'participants[' + i + '][dob]'" type="date"
                                       x-model="orang.dob"
                                       class="mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 focus:border-forest-500">
                            </div>

                            @if ($labelIdentitas)
                                <div class="sm:col-span-2">
                                    <label :for="'peserta-identitas-' + i" class="block text-sm font-medium text-forest-800">
                                        {{ $labelIdentitas }}
                                    </label>
                                    <input :id="'peserta-identitas-' + i" :name="'participants[' + i + '][id_number]'" type="text"
                                           x-model="orang.id_number" required
                                           inputmode="{{ $idRequirement === IdType::Nik ? 'numeric' : 'text' }}"
                                           maxlength="{{ $idRequirement === IdType::Nik ? 16 : 12 }}"
                                           class="mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 focus:border-forest-500">
                                    <p class="mt-1.5 text-xs text-forest-500">
                                        Diwajibkan kategori {{ Str::lower($schedule->trip->category->name) }} untuk pendataan peserta oleh
                                        penyelenggara. Nomor disimpan terenkripsi dan tidak ditampilkan kembali.
                                    </p>
                                </div>
                            @endif

                            <div class="sm:col-span-2">
                                <label :for="'peserta-darurat-' + i" class="block text-sm font-medium text-forest-800">
                                    Kontak darurat <span class="ml-1 text-xs font-normal text-forest-500">(opsional)</span>
                                </label>
                                <input :id="'peserta-darurat-' + i" :name="'participants[' + i + '][emergency_contact]'" type="text"
                                       x-model="orang.emergency_contact" maxlength="255"
                                       class="mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 focus:border-forest-500">
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
                    class="mt-5 rounded-full border border-dashed border-sand-400 px-5 py-2.5 text-sm text-forest-700 hover:border-forest-500">
                + Tambah peserta
            </button>

            <div class="mt-8">
                <label for="catatan" class="block text-sm font-medium text-forest-800">
                    Catatan untuk penyelenggara <span class="ml-1 text-xs font-normal text-forest-500">(opsional)</span>
                </label>
                <textarea id="catatan" name="notes" rows="3" maxlength="500"
                          placeholder="Misal: alergi makanan, titik jemput, permintaan khusus."
                          class="mt-2 w-full rounded-2xl border border-sand-300 bg-sand-50 px-4 py-2.5 text-sm text-forest-900 placeholder:text-forest-400 focus:border-forest-500">{{ old('notes') }}</textarea>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <button type="submit"
                        class="rounded-full bg-terracotta-600 px-7 py-3 text-sm font-medium text-sand-50 transition-colors hover:bg-terracotta-700">
                    Lanjut ke pembayaran
                </button>
                <p class="text-sm text-forest-600">
                    Kursi ditahan {{ (int) (config('booking.expiry_minutes') / 60) }} jam setelah pemesanan dibuat.
                </p>
            </div>
        </form>
    </div>
</x-layouts.app>
