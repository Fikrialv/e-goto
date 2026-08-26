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
        Schema::table('categories', function (Blueprint $table) {
            // Checklist perlengkapan per kategori (D7.6). Dibaca utuh lalu
            // dirender — dilarang query JSON-path (PLAN.md §6 blok D7.6).
            $table->json('gear_checklist')->nullable()->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('gear_checklist');
        });
    }
};
