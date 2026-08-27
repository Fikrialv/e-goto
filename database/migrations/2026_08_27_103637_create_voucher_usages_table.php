<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cut');
            $table->timestamps();

            // Satu voucher sekali per booking. Penjaga terakhir di lapisan
            // database — validasi di Action bisa kalah balapan kalau dua
            // request checkout tiba bersamaan.
            $table->unique(['voucher_id', 'booking_id']);
            $table->index(['voucher_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
    }
};
