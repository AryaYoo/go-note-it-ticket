<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel companies dan insert satu perusahaan default
     * untuk menampung seluruh data yang sudah ada saat ini.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert perusahaan default untuk menampung data eksisting
        DB::table('companies')->insert([
            'id'         => 1,
            'name'       => 'Perusahaan Utama',
            'slug'       => 'perusahaan-utama',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
