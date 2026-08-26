<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_applications', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('contact_name');
            $table->string('contact_email')->index();
            $table->string('contact_phone');
            $table->text('experience')->nullable();
            // Path berkas di disk NON-publik (akta, KTP penanggung jawab, dsb).
            // Aturan JSON §4: dibaca utuh lalu dirender, dilarang query JSON-path.
            $table->json('documents')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('meeting_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_applications');
    }
};
