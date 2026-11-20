<?php

namespace App\Imports;

use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TicketsImport
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $errors = [];

    private int $companyId;
    private int $userId;

    public function __construct()
    {
        $this->companyId = (int) Auth::user()->company_id;
        $this->userId    = Auth::id();
    }

    public function import(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();

        for ($r = 4; $r <= $highestRow; $r++) {
            $nomorTiket = trim((string) $sheet->getCell('B' . $r)->getValue());
            if ($nomorTiket === '') {
                continue;
            }

            // Skip jika nomor tiket sudah ada
            if (Ticket::withTrashed()->where('nomor_tiket', $nomorTiket)->exists()) {
                $this->skipped++;
                continue;
            }

            try {
                $tglKejadian = $this->parseDate($sheet->getCell('C' . $r)->getValue());
                $tglSelesai  = $this->parseDate($sheet->getCell('N' . $r)->getValue());

                $statusRaw = trim((string) $sheet->getCell('L' . $r)->getValue());
                $status = match (strtolower($statusRaw)) {
                    'ekskalasi', 'eskalasi' => 'Eskalasi',
                    'closed'                => 'Closed',
                    default                 => $statusRaw ?: 'Open',
                };

                DB::table('tickets')->insert([
                    'company_id'           => $this->companyId,
                    'nomor_tiket'          => $nomorTiket,
                    'tanggal_kejadian'     => $tglKejadian ?: now()->format('Y-m-d'),
                    'nama_pelapor'         => trim((string) $sheet->getCell('D' . $r)->getValue()) ?: 'Pelapor',
                    'divisi_toko'          => trim((string) $sheet->getCell('E' . $r)->getValue()) ?: '-',
                    'kategori'             => trim((string) $sheet->getCell('F' . $r)->getValue()) ?: null,
                    'prioritas'            => trim((string) $sheet->getCell('G' . $r)->getValue()) ?: 'Medium',
                    'deskripsi_kendala'    => trim((string) $sheet->getCell('H' . $r)->getValue()) ?: null,
                    'dampak_operasional'   => trim((string) $sheet->getCell('I' . $r)->getValue()) ?: null,
                    'lampiran_gambar'      => null,
                    'pic'                  => trim((string) $sheet->getCell('K' . $r)->getValue()) ?: null,
                    'status'               => $status,
                    'tindakan_dilakukan'   => trim((string) $sheet->getCell('M' . $r)->getValue()) ?: null,
                    'tanggal_penyelesaian' => $tglSelesai,
                    'solusi_diberikan'     => trim((string) $sheet->getCell('O' . $r)->getValue()) ?: null,
                    'root_cause'           => trim((string) $sheet->getCell('P' . $r)->getValue()) ?: null,
                    'user_id'              => $this->userId,
                    'created_at'           => $tglKejadian ? $tglKejadian . ' 08:00:00' : now(),
                    'updated_at'           => $tglSelesai  ? $tglSelesai  . ' 17:00:00' : now(),
                ]);

                $this->imported++;
            } catch (\Throwable $e) {
                $this->errors[] = "Baris {$r} ({$nomorTiket}): " . $e->getMessage();
            }
        }
    }

    private function parseDate(mixed $raw): ?string
    {
        if (!$raw) return null;
        if (is_numeric($raw)) {
            return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
        }
        $str   = trim((string) $raw);
        $clean = str_replace(['/', '.'], '-', $str);
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})$/', $clean, $m)) {
            $d   = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mth = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $yr  = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            return "{$yr}-{$mth}-{$d}";
        }
        $time = strtotime($str);
        return $time ? date('Y-m-d', $time) : null;
    }
}
