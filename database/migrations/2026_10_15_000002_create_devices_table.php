<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('type', 50);           // CCTV, POS, Laptop, dll
            $table->string('alias', 100)->nullable(); // Nama/alias device
            $table->string('model', 100)->nullable(); // Model/tipe merek
            $table->string('serial_number', 100)->nullable();
            $table->unsignedSmallInteger('channel_count')->nullable(); // Jumlah kanal
            $table->unsignedSmallInteger('camera_count')->nullable();  // Jumlah kamera terpasang
            $table->text('modem_notes')->nullable(); // Catatan modem/kuota
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
