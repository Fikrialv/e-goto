<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            // Rupiah utuh, bukan desimal — rupiah tidak punya satuan pecahan dalam
            // praktik, dan nominal unik (PLAN.md §5.1) menambah digit satuan
            // langsung ke angka ini. Desimal cuma mengundang galat pembulatan.
            $table->unsignedBigInteger('price');
            $table->unsignedSmallInteger('min_pax')->default(1);
            $table->unsignedSmallInteger('max_pax')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_prices');
    }
};
