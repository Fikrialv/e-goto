<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            // Akun panel mitra. Nullable supaya profil mitra bisa dibuat lebih
            // dulu (mis. hasil approve yang akunnya menyusul) tanpa baris yatim.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            // Disiapkan untuk V2 (dashboard pemasukan vendor) — belum dipakai
            // di V1.5, dan sengaja tidak ada UI-nya sekarang.
            $table->unsignedTinyInteger('commission_percent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
