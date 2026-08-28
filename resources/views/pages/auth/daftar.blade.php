<x-layouts.auth title="Daftar" description="Buat akun E-GOTO untuk memesan open trip dan menyimpan e-tiket Anda.">
    <x-auth-split title="Buat akun" subtitle="Cukup sekali, dipakai untuk semua trip berikutnya.">
        <x-google-button label="Daftar dengan Google" />

        @if (filled(config('services.google.client_id')))
            <div class="my-7 flex items-center gap-4 text-xs text-teal-500">
                <span class="h-px flex-1 bg-mist-200"></span>
                atau pakai email
                <span class="h-px flex-1 bg-mist-200"></span>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="grid gap-5">
            @csrf

            <x-glass-input name="name" label="Nama" required autocomplete="name"
                           placeholder="Nama sesuai identitas" />
            <x-glass-input name="email" label="Email" type="email" required autocomplete="email"
                           placeholder="nama@email.com" />
            <x-glass-input name="password" label="Kata sandi" type="password" required
                           autocomplete="new-password" help="Minimal 8 karakter." />
            <x-glass-input name="password_confirmation" label="Ulangi kata sandi" type="password" required
                           autocomplete="new-password" />

            <button type="submit"
                    class="rounded-full bg-amber-600 px-6 py-3 text-sm font-medium text-mist-50 transition duration-200 ease-out hover:-translate-y-0.5 hover:bg-amber-700 hover:shadow-lg hover:shadow-amber-600/20">
                Daftar
            </button>
        </form>

        <x-slot:footer>
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-medium text-amber-700 underline underline-offset-4">Masuk</a>
        </x-slot:footer>
    </x-auth-split>
</x-layouts.auth>
