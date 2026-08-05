<x-layouts.app title="Masuk" description="Masuk ke akun E-GOTO untuk memesan trip dan melihat e-tiket Anda.">
    <x-auth-card title="Masuk" subtitle="Lanjutkan pemesanan trip Anda.">
        @if (session('status'))
            <p class="mb-5 rounded-2xl bg-forest-50 px-4 py-3 text-sm text-forest-700">{{ session('status') }}</p>
        @endif

        <x-google-button label="Masuk dengan Google" />

        @if (filled(config('services.google.client_id')))
            <div class="my-6 flex items-center gap-4 text-xs text-forest-500">
                <span class="h-px flex-1 bg-sand-200"></span>
                atau pakai email
                <span class="h-px flex-1 bg-sand-200"></span>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="grid gap-5">
            @csrf

            <x-form-field name="email" label="Email" type="email" required autocomplete="email" />
            <x-form-field name="password" label="Kata sandi" type="password" required autocomplete="current-password" />

            <label class="flex items-center gap-2 text-sm text-forest-700">
                <input type="checkbox" name="ingat_saya" value="1" class="size-4 rounded border-sand-300 text-forest-600">
                Ingat saya di perangkat ini
            </label>

            <button type="submit"
                    class="rounded-full bg-terracotta-600 px-6 py-3 text-sm font-medium text-sand-50 transition-colors hover:bg-terracotta-700">
                Masuk
            </button>
        </form>

        <x-slot:footer>
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-medium text-terracotta-700 underline underline-offset-4">Daftar sekarang</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
