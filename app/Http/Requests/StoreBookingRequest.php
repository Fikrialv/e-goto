<?php

namespace App\Http\Requests;

use App\Enums\IdType;
use App\Models\TripSchedule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Route sudah dijaga `auth` + `role:customer`, dan jadwal yang dipesan
     * diambil dari route binding — bukan dari input, jadi tidak ada yang bisa
     * dialihkan ke jadwal lain lewat field tersembunyi.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan identitas ditentukan kategori jadwal yang dipesan, BUKAN oleh
     * nilai yang dikirim browser. Kalau id_type ikut dipercaya dari form,
     * peserta pendakian tinggal mengirim "none" untuk melewati kewajiban NIK.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $aturan = [
            // Cap keras 12 peserta (PLAN.md §5.6). Ditegakkan di sini, bukan cuma
            // di form: batas yang hanya hidup di UI bisa dilewati siapa pun yang
            // mengirim POST sendiri. Kuota jadwal tetap dicek terpisah di
            // CreateBooking — yang lebih kecil di antara keduanya yang menang.
            'participants' => ['required', 'array', 'min:1', 'max:'.$this->maxPax()],
            'participants.*.full_name' => ['required', 'string', 'max:255'],
            'participants.*.phone' => ['nullable', 'string', 'max:30'],
            'participants.*.dob' => ['nullable', 'date', 'before:today'],
            'participants.*.emergency_contact' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        $aturan['participants.*.id_number'] = match ($this->idRequirement()) {
            // 16 digit persis, angka semua — NIK yang salah panjang selalu
            // salah ketik, dan operator trip memakainya untuk perizinan.
            IdType::Nik => ['required', 'string', 'digits:16'],
            IdType::Passport => ['required', 'string', 'alpha_num', 'min:6', 'max:12'],
            IdType::None => ['nullable'],
        };

        return $aturan;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Pesan menyebut jalur keluarnya, bukan cuma angkanya — kalau tidak,
            // rombongan besar mentok tanpa tahu harus ke mana (PLAN.md §5.6).
            'participants.max' => 'Satu pemesanan maksimal '.$this->maxPax().' peserta. Untuk rombongan lebih besar, ajukan Request Private Trip lewat kontak admin.',
            'participants.*.full_name.required' => 'Nama lengkap peserta wajib diisi.',
            'participants.*.id_number.required' => $this->idRequirement() === IdType::Nik
                ? 'NIK peserta wajib diisi untuk kategori trip ini.'
                : 'Nomor paspor peserta wajib diisi untuk kategori trip ini.',
            'participants.*.id_number.digits' => 'NIK harus 16 digit angka.',
        ];
    }

    public function maxPax(): int
    {
        return (int) config('booking.max_pax_per_booking');
    }

    public function idRequirement(): IdType
    {
        /** @var TripSchedule $schedule */
        $schedule = $this->route('schedule');

        return $schedule->trip->category->id_requirement;
    }

    /**
     * Peserta yang dikirim, sudah dibersihkan dari kunci yang tidak dikenal —
     * daftar ini yang masuk ke CreateBooking, jadi tidak ada field liar yang
     * bisa menyelinap ke mass assignment.
     *
     * @return array<int, array<string, string|null>>
     */
    public function participants(): array
    {
        return array_values(array_map(
            fn (array $peserta) => [
                'full_name' => $peserta['full_name'],
                'phone' => $peserta['phone'] ?? null,
                'id_number' => $peserta['id_number'] ?? null,
                'dob' => $peserta['dob'] ?? null,
                'emergency_contact' => $peserta['emergency_contact'] ?? null,
            ],
            $this->validated('participants'),
        ));
    }
}
