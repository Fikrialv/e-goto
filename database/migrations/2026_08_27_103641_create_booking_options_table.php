<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_option_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('qty');
            // Harga dibekukan saat booking dibuat. Kalau mitra menaikkan harga
            // opsi besok, total yang sudah disepakati tidak boleh ikut berubah.
            $table->unsignedBigInteger('unit_price');
            $table->timestamps();

            $table->unique(['booking_id', 'trip_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_options');
    }
};
