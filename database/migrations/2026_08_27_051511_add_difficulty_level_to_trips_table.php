<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // Tingkat kesulitan melekat per trip, bukan per kategori (PLAN.md §4):
            // dua trip pendakian dalam satu kategori bisa jauh berbeda beratnya.
            // Nullable — trip lama dan trip non-fisik memang tidak punya level.
            $table->enum('difficulty_level', ['pemula', 'menengah', 'lanjutan'])
                ->nullable()
                ->after('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('difficulty_level');
        });
    }
};
