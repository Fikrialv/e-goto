<?php

namespace App\Http\Requests;

use App\Enums\TripDifficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filter daftar trip di halaman kategori.
 *
 * Semua nilainya datang dari query string yang bisa diketik siapa saja, jadi
 * divalidasi dulu sebelum menyentuh query builder: tanggal harus benar-benar
 * tanggal, harga harus bilangan bulat, dan `urut` dibatasi ke daftar tertutup
 * supaya tidak ada string bebas yang bisa jatuh ke klausa ORDER BY.
 */
class TripFilterRequest extends FormRequest
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
        // Aturan perbandingan hanya dipasang kalau lawannya benar-benar diisi:
        // `gte:harga_min` terhadap field kosong selalu gagal, dan itu bikin
        // filter "harga maksimum saja" ikut ditolak.
        $tanggalAkhir = ['nullable', 'date'];
        $hargaMax = ['nullable', 'integer', 'min:0'];

        if ($this->filled('tanggal_mulai')) {
            $tanggalAkhir[] = 'after_or_equal:tanggal_mulai';
        }

        if ($this->filled('harga_min')) {
            $hargaMax[] = 'gte:harga_min';
        }

        return [
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_akhir' => $tanggalAkhir,
            'harga_min' => ['nullable', 'integer', 'min:0'],
            'harga_max' => $hargaMax,
            'urut' => ['nullable', 'in:terdekat,termurah,termahal'],
            'level' => ['nullable', Rule::enum(TripDifficulty::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_akhir' => 'tanggal akhir',
            'harga_min' => 'harga minimum',
            'harga_max' => 'harga maksimum',
            'urut' => 'urutan',
            'level' => 'tingkat kesulitan',
        ];
    }
}
