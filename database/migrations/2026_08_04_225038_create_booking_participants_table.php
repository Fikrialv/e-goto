<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_leader')->default(false);
            $table->string('full_name');
            $table->string('phone')->nullable();
            // none | nik | passport, mengikuti categories.id_requirement.
            $table->string('id_type')->default('none');
            // NIK/paspor: cast `encrypted` di model, jadi yang tersimpan adalah
            // ciphertext yang jauh lebih panjang dari 16 karakter — karena itu
            // `text`, bukan varchar. Konsekuensinya kolom ini TIDAK BISA di-where.
            $table->text('id_number')->nullable();
            // Jalur pencarian identitas: sha256 dari nomor asli. Semua lookup
            // lewat sini, jangan pernah where ke id_number (PLAN.md §4).
            $table->char('id_number_hash', 64)->nullable()->index();
            $table->date('dob')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_participants');
    }
};
