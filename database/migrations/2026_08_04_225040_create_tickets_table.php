<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('booking_participants')->cascadeOnDelete();
            // Isi QR adalah token ini, bukan URL — vendor mengetik/scan token
            // langsung di panel, jadi tidak ada endpoint publik yang bisa diserang.
            $table->string('token', 32)->unique();
            // HMAC-SHA256(token|booking_code|participant_id, APP_KEY). Tiket yang
            // isinya diubah akan gagal verifikasi walau tokennya ada di database.
            $table->string('signature');
            $table->string('qr_path')->nullable();
            $table->string('status')->default('issued')->index();
            // Diisi sekali saat check-in. Perubahan status wajib dalam transaksi +
            // lockForUpdate supaya satu tiket tidak bisa dipakai dua kali bersamaan.
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
