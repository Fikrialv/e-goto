<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verifikasi dua langkah untuk akun staf.
     *
     * Rahasia TOTP dan kode pemulihan dienkripsi lewat cast `encrypted` di
     * model — aturan yang sama dengan NIK peserta (CLAUDE.md §5). Rahasia TOTP
     * yang tersimpan polos membuat dump database setara dengan kunci ke seluruh
     * akun admin: siapa pun yang memegangnya bisa membangkitkan kode yang sah
     * kapan saja, dan pemiliknya tidak akan pernah tahu.
     *
     * `two_factor_confirmed_at` terpisah dari `two_factor_secret` dengan
     * sengaja. Rahasia dibuat saat pendaftaran dimulai; ia baru berlaku setelah
     * pemiliknya membuktikan aplikasi authenticator-nya benar-benar bisa
     * membaca kode. Tanpa pemisahan ini, orang bisa terkunci keluar oleh
     * rahasia yang tidak pernah berhasil ia pindai.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
