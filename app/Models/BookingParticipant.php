<?php

namespace App\Models;

use App\Enums\IdType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'is_leader',
        'full_name',
        'phone',
        'id_type',
        'id_number',
        'id_number_hash',
        'dob',
        'emergency_contact',
    ];

    /**
     * id_number disembunyikan dari serialisasi supaya NIK/paspor tidak ikut
     * terbawa saat model di-toArray/toJson — misalnya ke response, log, atau
     * payload notifikasi (checklist keamanan PLAN.md §10).
     *
     * @var list<string>
     */
    protected $hidden = [
        'id_number',
        'id_number_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_leader' => 'boolean',
            'id_type' => IdType::class,
            // Terenkripsi at-rest. Konsekuensi: kolom ini tidak bisa di-where —
            // pencarian identitas lewat id_number_hash (lihat hashFor()).
            'id_number' => 'encrypted',
            'dob' => 'date',
        ];
    }

    /**
     * Satu-satunya cara membuat nilai id_number_hash. Dipusatkan di sini supaya
     * penulisan dan pencarian dijamin memakai algoritma yang sama — kalau
     * dihitung ad-hoc di dua tempat, sekali beda maka lookup diam-diam gagal.
     */
    public static function hashFor(string $idNumber): string
    {
        return hash('sha256', $idNumber);
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
