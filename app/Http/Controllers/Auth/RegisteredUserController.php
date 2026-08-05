<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('pages.auth.daftar');
    }

    /**
     * Daftar manual (email + kata sandi).
     *
     * Field diisi satu per satu, bukan disebar dari `validated()`: kolom
     * `role` ada di `$fillable` User, jadi request yang menyelipkan
     * `role=admin` akan langsung menaikkan hak akses kalau array-nya dioper
     * mentah. Password di-hash lewat cast `hashed` pada model.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => UserRole::Customer,
        ]);

        Auth::login($user);

        // Session fixation: id sesi yang dipakai saat masih tamu tidak boleh
        // ikut terbawa setelah user punya identitas.
        $request->session()->regenerate();

        /*
         * Sengaja `to()`, bukan `intended()` — `intended()` mengonsumsi
         * `url.intended`, sedangkan tujuan booking-nya masih dibutuhkan
         * setelah layar "lengkapi profil" selesai/dilewati.
         */
        return redirect()->to(route('profile.complete'));
    }
}
