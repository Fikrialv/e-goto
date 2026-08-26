<x-layouts.app title="Daftar" description="Buat akun E-GOTO untuk memesan open trip dan menyimpan e-tiket Anda.">
    <x-auth-card title="Buat akun" subtitle="Cukup sekali, dipakai untuk semua trip berikutnya.">
        <x-google-button label="Daftar dengan Google" />

        @if (filled(config('services.google.client_id')))
            <div class="my-6 flex items-center gap-4 text-xs text-teal-500">
                <span class="h-px flex-1 bg-mist-200"></span>
                atau pakai email
                <span class="h-px flex-1 bg-mist-200"></span>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="grid gap-5">
            @csrf

            <x-form-field name="name" label="Nama" required autocomplete="name" />
            <x-form-field name="email" label="Email" type="email" required autocomplete="email" />
            <x-form-field name="password" label="Kata sandi" type="password" required
                          autocomplete="new-password" help="Minimal 8 karakter." />
            <x-form-field name="password_confirmation" label="Ulangi kata sandi" type="password" required
                          autocomplete="new-password" />

            <button type="submit"
                    class="rounded-full bg-amber-600 px-6 py-3 text-sm font-medium text-mist-50 transition-colors hover:bg-amber-700">
                Daftar
            </button>
        </form>

        <x-slot:footer>
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-medium text-amber-700 underline underline-offset-4">Masuk</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
