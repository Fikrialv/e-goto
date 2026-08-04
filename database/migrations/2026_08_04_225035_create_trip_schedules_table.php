<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('quota');
            // Ditambah saat booking dibuat, dikurangi saat booking expired/batal.
            // Selalu diubah dalam transaksi + lockForUpdate (PLAN.md §5.3) — kalau
            // tidak, dua booking bersamaan bisa menembus kuota (overbooking).
            $table->unsignedInteger('booked_count')->default(0);
            $table->string('status')->default('published')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_schedules');
    }
};
