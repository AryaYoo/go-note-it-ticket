<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan company_id ke tabel users dan tickets,
     * lalu backfill semua data lama ke company_id = 1.
     * Role 'superadmin' dibiarkan company_id = null (platform level).
     */
    public function up(): void
    {
        // --- Users ---
        Schema::table('users', function (Blueprint $table) {
            // nullable: superadmin tidak punya perusahaan
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        // Backfill: semua user eksisting masuk ke Perusahaan #1
        DB::table('users')->whereNull('company_id')->update(['company_id' => 1]);

        // --- Tickets ---
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->cascadeOnDelete();
        });

        // Backfill: semua tiket eksisting masuk ke Perusahaan #1
        DB::table('tickets')->whereNull('company_id')->update(['company_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
