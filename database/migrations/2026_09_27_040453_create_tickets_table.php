<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tiket', 30)->unique();
            $table->date('tanggal_kejadian');
            $table->string('nama_pelapor', 150);
            $table->string('divisi_toko', 150);

            // Detail Kendala (Gemini auto-fill)
            $table->enum('kategori', ['POS', 'Akun', 'Device', 'Lainnya'])->nullable();
            $table->enum('prioritas', ['Low', 'Medium', 'High'])->nullable();
            $table->text('deskripsi_kendala')->nullable();
            $table->text('dampak_operasional')->nullable();
            $table->text('lampiran_gambar')->nullable(); // JSON array, max 3 paths

            // Penanganan (Gemini auto-fill)
            $table->string('pic', 150)->nullable();
            $table->enum('status', ['Open', 'Closed', 'Eskalasi'])->default('Open');
            $table->text('tindakan_dilakukan')->nullable();

            // Penyelesaian (Gemini auto-fill)
            $table->date('tanggal_penyelesaian')->nullable();
            $table->text('solusi_diberikan')->nullable();
            $table->text('root_cause')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
