<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('method')->default('qris');
            $table->unsignedBigInteger('amount_declared');
            // Disimpan di disk non-publik, diakses lewat route ber-authorize (PLAN.md §10).
            $table->string('proof_path')->nullable();
            // sha256 file bukti. Kalau hash sama muncul di booking lain, baris itu
            // di-flag untuk ditinjau admin — BUKAN ditolak otomatis (PLAN.md §5.2),
            // karena bisa saja customer salah unggah, dan keputusan tetap di manusia.
            $table->char('proof_hash', 64)->nullable()->index();
            $table->boolean('is_duplicate_flagged')->default(false)->index();
            $table->string('status')->default('pending')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            // Wajib diisi saat reject — alasannya ditampilkan ke customer supaya
            // dia tahu apa yang harus diperbaiki saat unggah ulang.
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
