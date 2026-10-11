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
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_unique');
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->unique(['company_id', 'key'], 'settings_company_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_company_key_unique');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->unique('key', 'settings_key_unique');
        });
    }
};
