<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('pages.auth.masuk');
    }

    /**
     * Login manual.
     *
     * Satu pesan gagal untuk semua sebab (email tidak terdaftar, password
     * salah, akun Google tanpa password) — membedakannya akan mengubah
     * halaman login jadi alat cek "email ini terdaftar atau tidak".
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $berhasil = Auth::attempt(
            ['email' => $request->validated('email'), 'password' => $request->validated('password')],
            (bool) $request->validated('ingat_saya', false)
        );

        if (! $berhasil) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        $request->session()->regenerate();

        return $this->redirectSetelahMasuk();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Admin & vendor tidak punya halaman customer — mereka langsung diantar
     * ke panelnya. Customer dikembalikan ke halaman yang tadi dituju
     * (`url.intended`, diisi otomatis oleh middleware `auth`).
     */
    private function redirectSetelahMasuk(): RedirectResponse
    {
        return match (Auth::user()?->role) {
            UserRole::Admin => redirect()->to('/admin'),
            UserRole::Vendor => redirect()->to('/vendor'),
            default => redirect()->intended(route('home')),
        };
    }
}
