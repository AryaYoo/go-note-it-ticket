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
            $table->text('gemini_api_key_tiket')->nullable()->change();
            $table->text('gemini_api_key_documentation')->nullable()->change();
            $table->string('gemini_model_tiket', 60)->nullable()->after('gemini_api_key_tiket');
            $table->string('gemini_model_documentation', 60)->nullable()->after('gemini_api_key_documentation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['gemini_model_tiket', 'gemini_model_documentation']);
            $table->string('gemini_api_key_tiket', 100)->nullable()->change();
            $table->string('gemini_api_key_documentation', 100)->nullable()->change();
        });
    }
};
