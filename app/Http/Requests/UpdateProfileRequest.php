<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Route-nya sudah dijaga `auth` + `role:customer`, dan profil yang
     * disimpan selalu milik user yang sedang login (tidak ada id dari input).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Hanya `full_name` yang wajib — sisanya dilengkapi bertahap. Data yang
     * benar-benar mengikat (NIK/paspor peserta) baru dikumpulkan saat booking
     * di D4, jadi memaksa semuanya di sini cuma menambah gesekan.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:laki-laki,perempuan'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'phone' => 'nomor HP',
            'dob' => 'tanggal lahir',
            'gender' => 'jenis kelamin',
            'address' => 'alamat',
            'emergency_contact_name' => 'nama kontak darurat',
            'emergency_contact_phone' => 'nomor kontak darurat',
        ];
    }
}
