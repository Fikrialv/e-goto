<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            // null = trip milik E-GOTO langsung, bukan mitra.
            // Sengaja TANPA foreign key: tabel `vendors` baru dibuat di D8 (V1.5).
            // Constraint-nya ditambahkan lewat migration terpisah saat itu.
            $table->unsignedBigInteger('vendor_id')->nullable()->index();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('itinerary')->nullable();
            $table->text('includes')->nullable();
            $table->text('excludes')->nullable();
            $table->string('meeting_point')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
