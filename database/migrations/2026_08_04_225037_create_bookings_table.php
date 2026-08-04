<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // Ditampilkan besar di halaman pembayaran dan wajib ditulis customer
            // di catatan transfer — jadi harus unik dan tidak mudah salah baca.
            $table->string('code')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('trip_schedule_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('pax_count');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_amount')->default(0);
            // 3 digit acak yang membuat nominal tiap booking berbeda, supaya admin
            // bisa mencocokkan mutasi bank. Regenerate kalau bentrok (PLAN.md §5.1).
            $table->unsignedSmallInteger('unique_code');
            $table->unsignedBigInteger('total_amount');
            $table->string('status')->default('pending_payment')->index();
            // Kuota ditahan sejak booking dibuat, dilepas kalau lewat batas ini.
            $table->timestamp('expires_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Dipakai bookings:expire (D4) untuk menyapu booking kedaluwarsa.
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
