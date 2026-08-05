<x-layouts.app :title="$awal ? 'Lengkapi profil' : 'Profil'">
    <div class="mx-auto w-full max-w-2xl px-4 py-12 sm:px-6 lg:py-16">
        @if ($awal)
            <p class="text-xs font-semibold tracking-widest text-terracotta-700 uppercase">Langkah terakhir</p>
        @endif

        <h1 class="mt-2 font-display text-3xl leading-tight font-semibold text-forest-900 sm:text-4xl">
            {{ $awal ? 'Lengkapi profil Anda' : 'Profil' }}
        </h1>

        <p class="mt-3 max-w-lg text-sm leading-relaxed text-forest-600">
            {{ $awal
                ? 'Data ini dipakai untuk mengisi peserta saat memesan trip. Bisa dilengkapi nanti — tidak ada yang hilang kalau dilewati sekarang.'
                : 'Data diri Anda. Nomor kontak darurat dipakai penyelenggara trip kalau terjadi sesuatu di lapangan.' }}
        </p>

        @if (session('status'))
            <p class="mt-6 rounded-2xl bg-forest-50 px-4 py-3 text-sm text-forest-700">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="mt-8 grid gap-5 rounded-3xl border border-sand-200 bg-white/70 p-6 sm:p-8">
            @csrf
            @method('PUT')

            <x-form-field name="full_name" label="Nama lengkap" required
                          :value="$profile?->full_name ?? auth()->user()->name"
                          help="Sesuai kartu identitas — dipakai saat penyelenggara mencocokkan peserta." />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form-field name="phone" label="Nomor HP" type="tel" :value="auth()->user()->phone" autocomplete="tel" />
                <x-form-field name="dob" label="Tanggal lahir" type="date" :value="$profile?->dob?->format('Y-m-d')" />
            </div>

            <x-form-field name="gender" label="Jenis kelamin" type="select">
                <option value="">Tidak disebutkan</option>
                <option value="laki-laki" @selected(old('gender', $profile?->gender) === 'laki-laki')>Laki-laki</option>
                <option value="perempuan" @selected(old('gender', $profile?->gender) === 'perempuan')>Perempuan</option>
            </x-form-field>

            <x-form-field name="address" label="Alamat" type="textarea" :value="$profile?->address" />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form-field name="emergency_contact_name" label="Nama kontak darurat" :value="$profile?->emergency_contact_name" />
                <x-form-field name="emergency_contact_phone" label="Nomor kontak darurat" type="tel" :value="$profile?->emergency_contact_phone" />
            </div>

            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="submit"
                        class="rounded-full bg-terracotta-600 px-6 py-3 text-sm font-medium text-sand-50 transition-colors hover:bg-terracotta-700">
                    Simpan profil
                </button>

                @if ($awal)
                    <a href="{{ route('profile.skip') }}" class="text-center text-sm text-forest-600 underline underline-offset-4 sm:text-left">
                        Nanti saja
                    </a>
                @endif
            </div>
        </form>
    </div>
</x-layouts.app>
