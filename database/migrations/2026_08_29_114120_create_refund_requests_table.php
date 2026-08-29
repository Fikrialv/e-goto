<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();

            /*
             * Satu pengajuan aktif per booking ditegakkan di Action, bukan lewat
             * unique index: booking yang pengajuan pertamanya DITOLAK harus tetap
             * bisa mengajukan ulang dengan opsi berbeda, dan unique index pada
             * booking_id akan menutup jalan itu diam-diam.
             */
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->string('status')->default('diajukan')->index();

            // Alasan dari customer. Bukan wajib — kebijakan tidak menuntut
            // customer beralasan untuk mendapat haknya.
            $table->text('customer_note')->nullable();

            // Wajib saat menolak. Alasan itu satu-satunya petunjuk customer
            // untuk memperbaiki pengajuannya — penjaga yang sama dengan
            // penolakan bukti bayar di D5 dan penolakan trip mitra di D9.
            $table->text('admin_note')->nullable();

            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            // Antrean admin selalu dibaca "yang belum diproses, terlama dulu".
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
