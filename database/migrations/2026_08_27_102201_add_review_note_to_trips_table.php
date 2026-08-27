<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // Alasan admin menolak pengajuan trip mitra (D9). Wajib diisi saat
            // menolak — tanpa itu mitra tidak tahu apa yang harus diperbaiki,
            // dan keputusan lama tidak bisa ditelusuri ulang.
            $table->text('review_note')->nullable()->after('published_at');
            $table->foreignId('reviewed_by')->nullable()->after('review_note')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_note', 'reviewed_at']);
        });
    }
};
