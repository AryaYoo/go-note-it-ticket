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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('gemini_api_key_tiket', 100)->nullable()->after('email');
            $table->string('gemini_api_key_documentation', 100)->nullable()->after('gemini_api_key_tiket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['gemini_api_key_tiket', 'gemini_api_key_documentation']);
        });
    }
};
