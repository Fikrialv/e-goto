<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pembagian tugas di dalam role Admin.
     *
     * Nullable dan default null disengaja: admin yang sudah ada tetap berakses
     * penuh setelah migration jalan. Migration yang diam-diam mempersempit hak
     * akun yang sedang dipakai adalah cara tercepat mengunci pemilik project
     * keluar dari panelnya sendiri.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('admin_scope')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_scope');
        });
    }
};
