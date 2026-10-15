<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom tambahan di tabel tickets untuk fitur preventive
        Schema::table('tickets', function (Blueprint $table) {
            // Sumber tiket: null = biasa, 'preventive' = dari pengecekan harian
            $table->string('source', 30)->nullable()->after('root_cause');
            // Relasi ke toko (opsional)
            $table->foreignId('store_id')->nullable()->after('source')
                  ->constrained('stores')->nullOnDelete();
            // Relasi ke item pengecekan harian (traceability)
            $table->foreignId('daily_check_item_id')->nullable()->after('store_id')
                  ->constrained('daily_check_items')->nullOnDelete();
        });

        // Pivot tabel tiket ↔ device (satu tiket bisa mencakup banyak device)
        Schema::create('ticket_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ticket_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_devices');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['daily_check_item_id']);
            $table->dropForeign(['store_id']);
            $table->dropColumn(['source', 'store_id', 'daily_check_item_id']);
        });
    }
};
