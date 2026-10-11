<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop global unique index
            $table->dropUnique('tickets_nomor_tiket_unique');
            
            // Tambahkan composite unique index per perusahaan
            $table->unique(['company_id', 'nomor_tiket'], 'tickets_company_nomor_tiket_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique('tickets_company_nomor_tiket_unique');
            $table->unique('nomor_tiket', 'tickets_nomor_tiket_unique');
        });
    }
};
