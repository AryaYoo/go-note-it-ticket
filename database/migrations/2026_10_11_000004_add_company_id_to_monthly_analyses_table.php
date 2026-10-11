<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monthly_analyses', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->cascadeOnDelete();
        });

        // Backfill data lama jika ada ke company default (1)
        DB::table('monthly_analyses')->whereNull('company_id')->update(['company_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_analyses', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
