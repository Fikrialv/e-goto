<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengajuan jadi mitra. Terbuka untuk publik (tanpa login) — calon mitra belum
 * tentu punya akun, dan memaksa daftar dulu cuma menyaring orang yang salah.
 *
 * Berkas dibatasi jenis & ukurannya di sini, bukan dipercayakan ke ekstensi
 * nama: unggahan publik adalah pintu masuk paling gampang untuk file berbahaya.
 */
class StoreVendorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'experience' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array', 'max:'.config('partner.max_documents')],
            'documents.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'business_name' => 'nama usaha',
            'contact_name' => 'nama penanggung jawab',
            'contact_email' => 'email',
            'contact_phone' => 'nomor WhatsApp',
            'experience' => 'pengalaman',
            'documents' => 'dokumen',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documents.*.mimes' => 'Dokumen harus berupa JPG, PNG, WebP, atau PDF.',
            'documents.*.max' => 'Tiap dokumen maksimal 4 MB.',
        ];
    }
}
