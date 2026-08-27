<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            // Kode diketik manusia di checkout, jadi disimpan huruf besar dan
            // dicari case-insensitive lewat normalisasi di Action, bukan LIKE.
            $table->string('code')->unique();
            $table->string('type');
            // Persen (1-100) atau rupiah utuh, tergantung `type`. Rupiah tanpa
            // desimal, sama seperti trip_prices.price.
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('min_spend')->nullable();
            // Null = tanpa batas pemakaian.
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->string('scope')->default('global');
            // Id kategori atau trip, tergantung `scope`. Sengaja tanpa foreign
            // key: satu kolom melayani dua tabel, dan constraint ke salah
            // satunya akan menolak yang lain.
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
