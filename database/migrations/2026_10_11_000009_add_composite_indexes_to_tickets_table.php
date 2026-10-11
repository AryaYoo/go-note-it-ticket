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
            $table->index(['company_id', 'status'], 'idx_tickets_company_status');
            $table->index(['company_id', 'tanggal_kejadian'], 'idx_tickets_company_tanggal');
            $table->index(['company_id', 'created_at'], 'idx_tickets_company_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_company_status');
            $table->dropIndex('idx_tickets_company_tanggal');
            $table->dropIndex('idx_tickets_company_created');
        });
    }
};
