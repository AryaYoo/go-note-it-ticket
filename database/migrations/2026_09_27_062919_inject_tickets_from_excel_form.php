<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modifikasi kolom kategori dan status agar fleksibel menampung data riil (seperti 'Email')
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('kategori', 50)->nullable()->change();
            $table->string('status', 50)->default('Open')->change();
        });

        // 2. Bersihkan seluruh data tiket yang ada saat ini
        DB::table('tickets')->delete();

        // 3. Cari user default untuk foreign key user_id
        $defaultUser = User::first();
        $userId = $defaultUser ? $defaultUser->id : 1;

        // 4. Baca file "Form Ticket IT as is.xlsx"
        $filePath = base_path('Form Ticket IT as is.xlsx');
        if (!file_exists($filePath)) {
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $records = [];
        $now = now();

        for ($r = 4; $r <= $highestRow; $r++) {
            $nomorTiket = trim((string)$sheet->getCell('B' . $r)->getValue());
            if ($nomorTiket === '') {
                continue;
            }

            // Parsing Tanggal Kejadian (Kolom C)
            $rawC = $sheet->getCell('C' . $r)->getValue();
            $tglKejadian = null;
            if (is_numeric($rawC)) {
                $tglKejadian = Date::excelToDateTimeObject($rawC)->format('Y-m-d');
            } elseif ($rawC) {
                $tglKejadian = date('Y-m-d', strtotime($rawC));
            }

            // Parsing Tanggal Penyelesaian (Kolom N)
            $rawN = $sheet->getCell('N' . $r)->getValue();
            $tglSelesai = null;
            if (is_numeric($rawN)) {
                $tglSelesai = Date::excelToDateTimeObject($rawN)->format('Y-m-d');
            } elseif ($rawN) {
                $tglSelesai = date('Y-m-d', strtotime($rawN));
            }

            // Normalisasi status
            $statusRaw = trim((string)$sheet->getCell('L' . $r)->getValue());
            $status = match(strtolower($statusRaw)) {
                'ekskalasi', 'eskalasi' => 'Eskalasi',
                'closed'                => 'Closed',
                default                 => $statusRaw ?: 'Open',
            };

            $records[] = [
                'nomor_tiket'          => $nomorTiket,
                'tanggal_kejadian'     => $tglKejadian ?: $now->format('Y-m-d'),
                'nama_pelapor'         => trim((string)$sheet->getCell('D' . $r)->getValue()) ?: 'Pelapor',
                'divisi_toko'          => trim((string)$sheet->getCell('E' . $r)->getValue()) ?: '-',
                'kategori'             => trim((string)$sheet->getCell('F' . $r)->getValue()) ?: null,
                'prioritas'            => trim((string)$sheet->getCell('G' . $r)->getValue()) ?: 'Medium',
                'deskripsi_kendala'    => trim((string)$sheet->getCell('H' . $r)->getValue()) ?: null,
                'dampak_operasional'   => trim((string)$sheet->getCell('I' . $r)->getValue()) ?: null,
                'lampiran_gambar'      => null,
                'pic'                  => trim((string)$sheet->getCell('K' . $r)->getValue()) ?: null,
                'status'               => $status,
                'tindakan_dilakukan'   => trim((string)$sheet->getCell('M' . $r)->getValue()) ?: null,
                'tanggal_penyelesaian' => $tglSelesai,
                'solusi_diberikan'     => trim((string)$sheet->getCell('O' . $r)->getValue()) ?: null,
                'root_cause'           => trim((string)$sheet->getCell('P' . $r)->getValue()) ?: null,
                'user_id'              => $userId,
                'created_at'           => $tglKejadian ? $tglKejadian . ' 08:00:00' : $now,
                'updated_at'           => $tglSelesai ? $tglSelesai . ' 17:00:00' : $now,
            ];
        }

        // 5. Inject semua data tiket ke database
        if (!empty($records)) {
            foreach (array_chunk($records, 50) as $chunk) {
                DB::table('tickets')->insert($chunk);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tickets')->delete();
    }
};
