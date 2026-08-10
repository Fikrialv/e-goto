<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentProofRequest extends FormRequest
{
    /**
     * Kepemilikan booking diperiksa di controller (403, bukan 404) — bukti
     * bayar orang lain tidak boleh bisa ditimpa lewat menebak kode booking.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validasi berkas dikunci di dua sisi: `image` memeriksa isi berkas, `mimes`
     * memeriksa ekstensi. Keduanya dipakai bersama supaya skrip PHP yang
     * dinamai ulang jadi .jpg tidak lolos masuk ke penyimpanan.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'amount_declared' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proof.required' => 'Unggah tangkapan layar atau foto bukti transfer Anda.',
            'proof.image' => 'Berkas harus berupa gambar (JPG, PNG, atau WebP).',
            'proof.max' => 'Ukuran gambar maksimal 4 MB.',
        ];
    }
}
