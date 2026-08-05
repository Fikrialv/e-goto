<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Layar "lengkapi profil" sesudah mendaftar. Isinya form yang sama dengan
     * halaman profil, hanya framing-nya berbeda dan ada jalan keluar
     * ("Nanti saja") — data ini baru benar-benar mengikat saat mengisi
     * peserta booking, jadi memaksanya sekarang cuma menghambat tepat di
     * titik user paling siap bertransaksi.
     */
    public function complete(Request $request): View
    {
        return view('pages.profil', [
            'profile' => $request->user()->customerProfile,
            'awal' => true,
        ]);
    }

    public function edit(Request $request): View
    {
        return view('pages.profil', [
            'profile' => $request->user()->customerProfile,
            'awal' => false,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Nomor HP tinggal di `users` (dipakai lintas peran), sisanya di
        // `customer_profiles`.
        $user->update(['phone' => $data['phone'] ?? null]);

        $user->customerProfile()->updateOrCreate([], [
            'full_name' => $data['full_name'],
            'dob' => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
        ]);

        // Baru di sini `url.intended` dikonsumsi: kalau user tadi datang dari
        // tombol booking, dia mendarat kembali di jadwal yang sama.
        return redirect()
            ->intended(route('profile.edit'))
            ->with('status', 'Profil tersimpan.');
    }

    /**
     * "Nanti saja" — tetap harus lewat sini supaya `url.intended` ikut
     * dikonsumsi dan user kembali ke booking yang tadi diklik.
     */
    public function skip(): RedirectResponse
    {
        return redirect()->intended(route('home'));
    }
}
