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
        Schema::table('documentation_conversations', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->cascadeOnDelete();
        });

        Schema::table('documentation_chats', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->cascadeOnDelete();
        });

        // Backfill company_id dari tabel users
        DB::statement("
            UPDATE documentation_conversations dc
            JOIN users u ON dc.user_id = u.id
            SET dc.company_id = COALESCE(u.company_id, 1)
        ");

        DB::statement("
            UPDATE documentation_chats dc
            JOIN users u ON dc.user_id = u.id
            SET dc.company_id = COALESCE(u.company_id, 1)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentation_chats', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('documentation_conversations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
