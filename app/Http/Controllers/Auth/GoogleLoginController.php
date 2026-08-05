<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleLoginController extends Controller
{
    /**
     * Selama kredensial belum dipasang, route ini tidak boleh ada — lebih
     * baik 404 daripada melempar exception konfigurasi ke wajah pengunjung.
     */
    public function redirect(): RedirectResponse
    {
        abort_unless(filled(config('services.google.client_id')), 404);

        // Stateful (tanpa `stateless()`): parameter `state` OAuth itulah
        // proteksi CSRF-nya, dan Socialite yang mencocokkannya lewat session.
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(filled(config('services.google.client_id')), 404);

        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => 'Login dengan Google gagal. Coba lagi atau masuk pakai kata sandi.',
            ]);
        }

        $email = $socialUser->getEmail();

        // Tanpa email tidak ada identitas yang bisa dipertanggungjawabkan —
        // menautkan berdasarkan nama saja jelas tidak aman.
        if (blank($email)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Google Anda tidak membagikan alamat email, jadi belum bisa dipakai masuk.',
            ]);
        }

        $user = DB::transaction(fn () => $this->cariAtauBuat($socialUser, $email));

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if ($user->role !== UserRole::Customer) {
            return redirect()->to($user->role === UserRole::Admin ? '/admin' : '/vendor');
        }

        return $user->customerProfile()->exists()
            ? redirect()->intended(route('home'))
            : redirect()->to(route('profile.complete'));
    }

    /**
     * Urutan pencarian menentukan keamanannya:
     *
     * 1. `provider_id` — identitas kanonik dari Google, tidak bisa diklaim
     *    orang lain.
     * 2. `email` — menautkan akun Google ke akun manual dengan email sama.
     *    Ini menaruh kepercayaan pada verifikasi email milik Google, dan
     *    risikonya searah: yang menguasai akun Google email X memang berhak
     *    atas akun email X. Sebaliknya, orang yang mendaftar manual memakai
     *    email milik orang lain tidak mendapat apa pun dari jalur ini.
     * 3. Buat baru — `password` null, jadi akun ini tidak bisa dimasuki lewat
     *    form login sampai pemiliknya menyetel kata sandi sendiri.
     *
     * `lockForUpdate` dipakai karena pasangan `provider`/`provider_id` tidak
     * punya unique constraint: dua callback yang datang berbarengan bisa
     * membuat user kembar tanpa kunci baris.
     */
    private function cariAtauBuat(SocialiteUser $socialUser, string $email): User
    {
        $user = User::query()
            ->where('provider', 'google')
            ->where('provider_id', $socialUser->getId())
            ->lockForUpdate()
            ->first()
            ?? User::query()->where('email', $email)->lockForUpdate()->first();

        if ($user === null) {
            return User::create([
                'name' => $socialUser->getName() ?: $email,
                'email' => $email,
                'password' => null,
                'role' => UserRole::Customer,
                'avatar' => $socialUser->getAvatar(),
                'provider' => 'google',
                'provider_id' => $socialUser->getId(),
            ]);
        }

        $user->forceFill([
            'provider' => 'google',
            'provider_id' => $socialUser->getId(),
            'avatar' => $user->avatar ?: $socialUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }
}
