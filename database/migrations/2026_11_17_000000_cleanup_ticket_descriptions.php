<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ambil semua tiket dengan deskripsi versi lama (verbose template)
        $tickets = DB::table('tickets')
            ->whereNotNull('deskripsi_kendala')
            ->where(function ($q) {
                $q->where('deskripsi_kendala', 'like', '%•%')
                  ->orWhere('dampak_operasional', 'like', '%•%')
                  ->orWhere('tindakan_dilakukan', 'like', '%•%')
                  ->orWhere('solusi_diberikan',   'like', '%•%')
                  ->orWhere('root_cause',          'like', '%•%');
            })
            ->get();

        foreach ($tickets as $ticket) {
            $update = [];

            // Kolom yang perlu diproses
            $cols = [
                'deskripsi_kendala',
                'dampak_operasional',
                'tindakan_dilakukan',
                'solusi_diberikan',
                'root_cause',
            ];

            foreach ($cols as $col) {
                $val = $ticket->$col;
                if (!$val) continue;

                // 1. Ganti • dengan -
                $val = str_replace('•', '-', $val);

                // 2. Bersihkan kalimat pembuka & penutup verbose dari deskripsi_kendala
                if ($col === 'deskripsi_kendala') {
                    // Hapus paragraf pembuka bertele-tele
                    $val = preg_replace(
                        '/Saat pengecekan harian pada ([^\n,]+?), ditemukan (perangkat|beberapa perangkat) dalam kondisi offline di ([^\n]+?)\.\s*\n\nBerikut rincian perangkat yang terdeteksi bermasalah:\s*\n/u',
                        'Offline saat pengecekan harian $1 di $3:' . "\n",
                        $val
                    );
                    // Hapus kalimat penutup
                    $val = preg_replace('/\s*\nMohon segera ditindaklanjuti\. Terima kasih\.$/u', '', $val);
                }

                $update[$col] = trim($val);
            }

            if (!empty($update)) {
                DB::table('tickets')->where('id', $ticket->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Tidak di-rollback karena data sudah berubah
    }
};

