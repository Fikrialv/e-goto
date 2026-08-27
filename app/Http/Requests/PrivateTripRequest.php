<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request private trip (D12). Tidak menyimpan apa pun ke database — isinya
 * langsung dirangkai jadi pesan WhatsApp ke admin, jadi yang perlu dijaga
 * cuma bentuk dan panjangnya.
 */
class PrivateTripRequest extends FormRequest
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
            'contact_name' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'depart_on' => ['nullable', 'date', 'after_or_equal:today'],
            'pax' => ['nullable', 'integer', 'min:1', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_name' => 'nama',
            'destination' => 'tujuan',
            'depart_on' => 'perkiraan tanggal berangkat',
            'pax' => 'jumlah peserta',
            'notes' => 'catatan',
        ];
    }
}
