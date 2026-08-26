<?php

use App\Enums\TripStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rapikan nilai `trip_schedules.status` yang di luar Enum `TripStatus`.
     *
     * Kolomnya dulu diisi lewat TextInput teks bebas, jadi salah ketik bisa
     * tersimpan tanpa ada yang menangkap. Sejak status disunting lewat Select
     * berbasis Enum + di-cast di model, nilai asing akan melempar galat saat
     * baris dibaca — bukan saat ditulis, yang jauh lebih sulit dilacak.
     */
    public function up(): void
    {
        $sah = array_column(TripStatus::cases(), 'value');

        DB::table('trip_schedules')
            ->whereNotIn('status', $sah)
            ->update(['status' => TripStatus::Published->value]);
    }

    /**
     * Tidak bisa dibalik: nilai asing yang lama tidak disimpan di mana pun,
     * dan mengembalikannya pun tidak ada gunanya.
     */
    public function down(): void {}
};
