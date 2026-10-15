<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel sesi pengecekan harian (satu sesi per hari per company)
        Schema::create('daily_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('check_date');
            $table->text('notes')->nullable(); // catatan umum sesi
            $table->timestamps();

            $table->unique(['company_id', 'check_date']); // satu sesi per hari per perusahaan
            $table->index(['company_id', 'check_date']);
        });

        // Tabel item per-device dalam satu sesi pengecekan
        Schema::create('daily_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_check_id')->constrained('daily_checks')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->boolean('is_offline')->default(false);
            $table->dateTime('offline_since')->nullable();
            $table->text('affected_channels')->nullable(); // kanal yang terdampak
            // result: null = belum diproses, fixed = diperbaiki cepat, no_fault = bukan kerusakan, ticket_created = tiket dibuat
            $table->string('result', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('daily_check_id');
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_check_items');
        Schema::dropIfExists('daily_checks');
    }
};
