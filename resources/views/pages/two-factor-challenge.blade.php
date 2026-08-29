<x-layouts.app title="Verifikasi dua langkah">
    <div class="mx-auto flex w-full max-w-md flex-col justify-center px-4 py-16 sm:px-6 lg:py-24">
        <img src="{{ asset('images/logo2.svg') }}" alt="E-GOTO" width="1536" height="1024"
             class="h-12 w-auto self-start">

        <p class="mt-10 text-xs font-semibold tracking-widest text-teal-500 uppercase">Langkah kedua</p>

        <h1 class="mt-2 font-display text-3xl leading-tight font-bold text-teal-900">
            Masukkan kode dari aplikasi
        </h1>

        <p class="mt-3 text-sm leading-relaxed text-teal-600">
            Buka aplikasi authenticator di HP kamu dan ketik enam digit yang tampil untuk E-GOTO.
            Kodenya berganti tiap 30 detik.
        </p>

        @error('code')
            <p role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                {{ $message }}
            </p>
        @enderror

        <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="mt-8">
            @csrf

            <label for="code" class="block text-sm font-medium text-teal-800">Kode verifikasi</label>
            <input id="code" name="code" type="text" required autofocus
                   inputmode="numeric" autocomplete="one-time-code"
                   placeholder="123456"
                   class="mt-2 w-full rounded-2xl border border-mist-300 bg-white px-4 py-3 text-center font-mono text-2xl tracking-[0.3em] text-teal-900 focus:border-teal-500 focus:outline-none">

            <button type="submit"
                    class="mt-5 w-full rounded-full bg-amber-600 px-7 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                Verifikasi
            </button>
        </form>

        <p class="mt-8 border-t border-mist-200 pt-6 text-sm leading-relaxed text-teal-600">
            HP-nya hilang atau aplikasinya terhapus? Ketik salah satu kode pemulihan yang kamu simpan
            saat menyalakan fitur ini — bentuknya <span class="font-mono">ABCDE-FGHIJ</span>. Tiap kode
            hanya bisa dipakai sekali.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="text-sm text-teal-700 underline underline-offset-4 hover:text-amber-600">
                Keluar dan masuk sebagai akun lain
            </button>
        </form>
    </div>
</x-layouts.app>
