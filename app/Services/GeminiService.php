<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private ?\App\Models\Company $company = null;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.tiket.api_key', '');
        $this->model  = config('services.gemini.tiket.model', 'gemini-flash-lite-latest');
    }

    public function forCompany(?\App\Models\Company $company): self
    {
        $clone = clone $this;
        $clone->company = $company;
        return $clone;
    }

    public function setCompany(?\App\Models\Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    private function getResolvedApiKey(): string
    {
        $company = $this->company ?? auth()->user()?->company;
        $key = GeminiKeyService::getKeyForCompany($company, 'tiket');
        return !empty($key) ? $key : ($this->apiKey ?: config('services.gemini.tiket.api_key', ''));
    }

    private function getResolvedModel(): string
    {
        $company = $this->company ?? auth()->user()?->company;
        return GeminiKeyService::getModelForCompany($company, 'tiket');
    }

    /**
     * Eksekusi request ke Gemini API.
     */
    private function execute(array $payload, int $timeout = 45): array
    {
        $apiKey = $this->getResolvedApiKey();
        if (empty($apiKey)) {
            $companyName = (auth()->user()?->company?->name) ? ' untuk perusahaan ' . auth()->user()->company->name : '';
            throw new \RuntimeException("API Key Gemini Tiket{$companyName} belum dikonfigurasi di file .env.");
        }

        $model = $this->getResolvedModel();
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout($timeout)->post($url, $payload);

        if ($response->successful()) {
            $tokens = (int) $response->json('usageMetadata.totalTokenCount', 0);
            self::recordUsage($tokens);
            return $response->json();
        }

        $status = $response->status();
        $body   = $response->json();
        $msg    = $body['error']['message'] ?? $response->body();

        self::recordError("Error {$status}: {$msg}");
        Log::error("Gemini Tiket API error ({$status})", ['message' => $msg]);
        throw new \RuntimeException("Gemini API error ({$status}): {$msg}");
    }

    /**
     * Default rules untuk prioritas AI berdasarkan ID company / divisi.
     */
    public static function getDefaultPriorityRules(?int $companyId = null): string
    {
        if ($companyId === 2) {
            return "- High:\n"
                . "  * HDD atau media penyimpanan CCTV / sistem tidak terdeteksi.\n"
                . "  * Minimal 2 kamera CCTV tidak terdeteksi / offline dan berdampak pada operasional divisi lain.\n"
                . "  * Gangguan modem atau koneksi internet yang menghambat kelancaran transaksi toko.\n"
                . "  * Perangkat masih mati meskipun langkah pertolongan pertama (troubleshooting awal) sudah dilakukan.\n"
                . "  * Printer macet (khusus jika perangkat harus segera digunakan saat itu juga untuk operasional).\n"
                . "- Medium:\n"
                . "  * Kesalahan pengisian paket data/kuota modem, namun koneksi masih dapat dialihkan (switch) ke jalur cadangan (backup).\n"
                . "- Low:\n"
                . "  * Gangguan minor atau kendala kecil yang tidak menghambat transaksi utama dan operasional toko.";
        }

        return "- High: menghambat operasional toko secara total (kasir mati, tidak bisa transaksi)\n"
            . "- Medium: mengganggu sebagian operasional (fitur tertentu error tapi toko bisa beroperasi)\n"
            . "- Low: gangguan kecil, tidak menghambat operasional";
    }

    /**
     * Default rules untuk klasifikasi status AI berdasarkan ID company / divisi.
     */
    public static function getDefaultStatusRules(?int $companyId = null): string
    {
        if ($companyId === 2) {
            return "- Open: Kendala baru dilaporkan atau masih dalam proses identifikasi.\n"
                . "- Closed: Penanganan selesai cukup melalui pengarahan atau panduan jarak jauh (remote).\n"
                . "- Selesai: Penanganan membutuhkan tindakan fisik langsung di lokasi (hands-on).\n"
                . "- Eskalasi: Penanganan diteruskan ke pihak terkait (Mujib, Adit, atau Vendor) dengan koordinasi follow-up melalui Kak Gilang.";
        }

        return "- Open: Kendala baru atau sedang berlangsung.\n"
            . "- Closed: Penanganan selesai cukup melalui pengarahan/panduan jarak jauh (remote).\n"
            . "- Selesai: Penanganan membutuhkan tindakan langsung di lokasi (hands-on).\n"
            . "- Eskalasi: Kendala diteruskan ke pihak spesialis / vendor pihak ketiga.";
    }

    /**
     * Default rules untuk SOP tindakan & alur operasional AI berdasarkan ID company / divisi.
     */
    public static function getDefaultActionRules(?int $companyId = null): string
    {
        if ($companyId === 2) {
            return "- Fokus Penanganan di Head Office: Seluruh penanganan dipusatkan dari Head Office terlebih dahulu. Kunjungan lokasi (visit) hanya dilakukan jika kendala bersifat urgent dan membutuhkan penanganan fisik langsung.\n"
                . "- Batas Waktu (SLA) Visit: Jika perangkat tetap mati selama 2 hari berturut-turut, tim wajib menjadwalkan visit fisik.\n"
                . "- Penjadwalan: Seluruh pengaturan jadwal (schedule) visit dan penugasan menjadi tanggung jawab Kak Gilang.\n"
                . "- Pengajuan Jaringan 5G: Seluruh permintaan (request) layanan/perangkat 5G wajib diajukan melalui sistem Ajuin.\n"
                . "- Monitoring Modem: Lakukan pemantauan rutin terhadap progres pengadaan atau aktivasi modem 5G.\n"
                . "- Jika kendala terkait modem mati, offline, atau internet toko terputus, rekomendasi tindakan mencakup: isi ulang kuota modem / cek sisa paket data, lalu coba restart modem.";
        }

        return "- Jika kendala terkait modem mati, offline, atau internet toko terputus, pastikan rekomendasi tindakan mencakup: isi ulang kuota modem / cek sisa paket data, lalu coba restart modem.";
    }

    /**
     * Analisis satu atau beberapa tangkapan layar WhatsApp (maks 3 gambar).
     *
     * @param  string[] $imagePaths Array path file gambar
     */
    public function analyzeWhatsAppScreenshot(array $imagePaths): array
    {
        if (empty($imagePaths)) {
            throw new \RuntimeException('Tidak ada gambar yang dikirim untuk dianalisis.');
        }

        // Bangun bagian gambar untuk setiap file (maks 3)
        $imageParts = [];
        foreach (array_slice($imagePaths, 0, 3) as $path) {
            $mimeType     = mime_content_type($path) ?: 'image/png';
            $imageParts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data'      => base64_encode(file_get_contents($path)),
                ],
            ];
        }

        $jumlahGambar = count($imageParts);
        $keteranganGambar = $jumlahGambar > 1
            ? "Terdapat {$jumlahGambar} tangkapan layar percakapan WhatsApp yang menggambarkan laporan kendala yang sama atau berkesinambungan."
            : 'Terdapat 1 tangkapan layar percakapan WhatsApp.';

        $company = $this->company ?? auth()->user()?->company;
        $companyId = $company?->id ?? auth()->user()?->company_id;

        $priorityRules = Setting::get('gemini_priority_rules', null, $companyId)
            ?: self::getDefaultPriorityRules($companyId);
        $statusRules   = Setting::get('gemini_status_rules', null, $companyId)
            ?: self::getDefaultStatusRules($companyId);
        $actionRules   = Setting::get('gemini_action_rules', null, $companyId)
            ?: self::getDefaultActionRules($companyId);

        $prompt = <<<PROMPT
Kamu adalah asisten IT Helpdesk & Operasional cerdas. {$keteranganGambar} Analisis seluruh gambar secara menyeluruh.

Ekstrak informasi dan kembalikan HANYA dalam format JSON murni (tanpa markdown, tanpa ```json, tanpa teks tambahan apapun):

{
  "kategori": "<salah satu: POS | Akun | Device | Laptop | CCTV | Lainnya>",
  "prioritas": "<salah satu: Low | Medium | High>",
  "deskripsi_kendala": "<ringkasan singkat kendala yang dilaporkan>",
  "dampak_operasional": "<dampak nyata terhadap operasional toko>",
  "status": "<salah satu: Open | Closed | Selesai | Eskalasi>",
  "tindakan_dilakukan": "<tindakan penanganan yang sudah dilakukan jika ada, atau rekomendasi tindakan berdasarkan SOP>",
  "tanggal_penyelesaian": "<tanggal selesai format YYYY-MM-DD jika masalah sudah selesai (Closed/Selesai), null jika belum>",
  "solusi_diberikan": "<solusi yang diberikan jika sudah selesai, kosongkan jika belum>",
  "root_cause": "<akar penyebab masalah jika diketahui, kosongkan jika belum>"
}

ATURAN TINGKAT PRIORITAS DIVISI (Ikuti aturan ini secara ketat):
{$priorityRules}

ATURAN KLASIFIKASI STATUS:
{$statusRules}

SOP TINDAKAN & ALUR PENANGANAN OPERASIONAL:
{$actionRules}

Panduan kategori:
- POS: masalah pada sistem kasir / Point of Sale
- Akun: masalah login, password, hak akses
- Device: masalah hardware umum (komputer PC, printer, scanner, dll)
- Laptop: masalah pada unit laptop atau notebook
- CCTV: masalah pada kamera pengawas CCTV, DVR/NVR, atau monitoring video
- Lainnya: masalah jaringan, internet, atau lainnya

Jika informasi tidak cukup untuk menentukan suatu field, gunakan nilai terbaik yang dapat kamu simpulkan dari konteks dan aturan di atas.
PROMPT;

        $parts = array_merge($imageParts, [['text' => $prompt]]);

        $responseJson = $this->execute([
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature'     => 0.2,
                'maxOutputTokens' => 1024,
            ],
        ], 45);

        $text = data_get($responseJson, 'candidates.0.content.parts.0.text', '');
        // Bersihkan markdown code block jika ada
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Gemini response tidak valid JSON', ['text' => $text]);
            throw new \RuntimeException('Respons AI tidak dapat diproses. Coba ulangi analisis.');
        }

        // Tanggal penyelesaian selalu default ke tanggal saat ini (hari ini) jika status Closed atau Selesai
        if (isset($data['status']) && in_array(strtolower($data['status']), ['closed', 'selesai'])) {
            $data['tanggal_penyelesaian'] = now()->format('Y-m-d');
        } else {
            $data['tanggal_penyelesaian'] = null;
        }

        return $data;
    }

    /**
     * Analisis bukti gambar dan/atau catatan internal untuk merumuskan formulir penyelesaian kendala tiket.
     *
     * @param  string[] $imagePaths Array path file gambar (opsional, bisa kosong jika hanya berbasis catatan & konteks)
     * @param  \App\Models\Ticket|null $ticket Objek tiket terkait
     * @param  string|null $customNotes Catatan kustom tambahan jika ada
     * @return array
     */
    public function analyzeResolutionScreenshot(array $imagePaths = [], ?\App\Models\Ticket $ticket = null, ?string $customNotes = null): array
    {
        $imageParts = [];
        if (!empty($imagePaths)) {
            foreach (array_slice($imagePaths, 0, 3) as $path) {
                if (@file_exists($path)) {
                    $mimeType     = mime_content_type($path) ?: 'image/png';
                    $imageParts[] = [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data'      => base64_encode(file_get_contents($path)),
                        ],
                    ];
                }
            }
        }

        $catatan = trim($customNotes ?? ($ticket?->catatan ?? ''));

        $contextInfo = '';
        if ($ticket) {
            $contextInfo = "Konteks Laporan Kendala Tiket Saat Ini:\n"
                . "- Nomor Tiket: {$ticket->nomor_tiket}\n"
                . "- Kategori: {$ticket->kategori}\n"
                . "- Divisi / Toko: {$ticket->divisi_toko}\n"
                . "- Deskripsi Kendala: " . ($ticket->deskripsi_kendala ?: 'Tidak ada deskripsi kendala') . "\n"
                . "- Dampak Operasional: " . ($ticket->dampak_operasional ?: 'Tidak ada catatan dampak') . "\n"
                . "- Tindakan Penanganan Sementara: " . ($ticket->tindakan_dilakukan ?: 'Belum dicatat') . "\n"
                . "- PIC: " . ($ticket->pic ?: 'Belum ditentukan') . "\n"
                . "- CATATAN INTERNAL / PROGRES TIM (PENTING): " . (!empty($catatan) ? "\"{$catatan}\"" : '(Tidak ada catatan internal)') . "\n";
        } elseif (!empty($catatan)) {
            $contextInfo = "Konteks Tiket:\n- Catatan Internal Tim (PENTING): \"{$catatan}\"\n";
        }

        $hasImages = !empty($imageParts);

        $promptInstruction = $hasImages
            ? "Gambar yang dilampirkan adalah foto bukti fisik, screenshot percakapan perbaikan (WhatsApp), atau kondisi perangkat/sistem yang sudah kembali normal.\n"
              . "Gunakan informasi dari bukti gambar DAN Catatan Internal / Progres Tim secara bersamaan untuk menyimpulkan penyelesaian secara utuh dan logis."
            : "Saat ini tidak ada foto bukti yang dilampirkan. Analisis dan rumuskan penyelesaian secara cerdas dan logis berdasarkan Catatan Internal / Progres Tim dan Deskripsi Kendala yang tercatat di atas.";

        $prompt = <<<PROMPT
Kamu adalah asisten IT Helpdesk profesional senior.
Tugasmu adalah menganalisis kendala dan merumuskan formulir penyelesaian tiket secara otomatis.

{$contextInfo}

{$promptInstruction}

PANDUAN UTAMA:
1. SANGAT PENTING - PERTIMBANGKAN CATATAN INTERNAL: Catatan internal berisi progres aktual dari teknisi atau staff di lapangan (misalnya: sparepart yang diganti, kabel yang diperbaiki, konfigurasi ulang, pemindahan perangkat, koordinasi vendor, dll). Solusi dan tindakan yang kamu rumuskan WAJIB selaras dan mencerminkan catatan tersebut.
2. Klasifikasi Status Akhir:
   - Closed: jika penanganan selesai cukup melalui arahan / remote / panduan jarak jauh.
   - Selesai: jika penanganan membutuhkan tindakan fisik langsung di lokasi (hands-on / pergantian hardware / visit).
3. Rumuskan formulir penyelesaian tiket dalam format JSON murni (tanpa markdown, tanpa ```json, tanpa teks pengantar atau penutup):

{
  "status": "<salah satu: Closed | Selesai>",
  "tindakan_dilakukan": "<tindakan atau langkah teknis penanganan yang telah dilakukan untuk memperbaiki kendala, selaras dengan catatan internal dan kendala>",
  "solusi_diberikan": "<solusi final yang diberikan dan konfirmasi bahwa perangkat/sistem kini telah berfungsi normal>",
  "root_cause": "<akar penyebab terjadinya masalah/kendala berdasarkan analisis teknis IT, catatan internal, dan bukti gambar jika ada>",
  "tanggal_penyelesaian": "<tanggal selesai dalam format YYYY-MM-DD jika dapat disimpulkan dari waktu/tanggal bukti atau catatan, jika tidak kembalikan null>"
}

Aturan Pengisian:
1. Berikan narasi yang jelas, profesional, ringkas, dan relevan dengan kendala dan catatan yang dilaporkan.
2. JANGAN gunakan tanda strip '-', 'belum diselesaikan', 'belum ada solusi', atau teks kosong. Ekstrak dan simpulkan solusi nyata yang komprehensif.
3. Pastikan bahasa Indonesia yang digunakan baku, lugas, dan sesuai standar pelaporan IT Helpdesk.
PROMPT;

        $parts = $hasImages
            ? array_merge($imageParts, [['text' => $prompt]])
            : [['text' => $prompt]];

        $responseJson = $this->execute([
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature'     => 0.2,
                'maxOutputTokens' => 1024,
            ],
        ], 45);

        $text = data_get($responseJson, 'candidates.0.content.parts.0.text', '');
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Gemini response tidak valid JSON untuk analisis penyelesaian', ['text' => $text]);
            throw new \RuntimeException('Respons AI tidak dapat diproses. Coba ulangi analisis.');
        }

        if (empty($data['tanggal_penyelesaian'])) {
            $data['tanggal_penyelesaian'] = now()->format('Y-m-d');
        }

        return $data;
    }

    /**
     * Generate rekomendasi operasional berbasis statistik tiket.
     */
    public function generateRecommendations(array $stats): string
    {
        $statsText = json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Kamu adalah analis IT Operations. Berdasarkan data statistik tiket IT toko berikut:

{$statsText}

Berikan 3-4 rekomendasi operasional yang konkret dan actionable dalam Bahasa Indonesia. 
Format setiap rekomendasi sebagai satu kalimat yang dimulai dengan kata kerja.
Kembalikan HANYA daftar rekomendasi, satu per baris, tanpa penomoran, tanpa bullet, tanpa teks tambahan.
PROMPT;

        try {
            $responseJson = $this->execute([
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature'     => 0.4,
                    'maxOutputTokens' => 512,
                ],
            ], 30);

            return data_get($responseJson, 'candidates.0.content.parts.0.text', 'Tidak ada rekomendasi.');
        } catch (\Throwable $e) {
            return 'Rekomendasi tidak tersedia saat ini.';
        }
    }

    /**
     * Analisis komprehensif untuk tiket atau analisis bulanan menggunakan prompt teks.
     */
    public function analyzeTicket(string $prompt, array $images = []): string
    {
        $parts = [['text' => $prompt]];

        $responseJson = $this->execute([
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature'     => 0.4,
                'maxOutputTokens' => 4096,
            ],
        ], 60);

        return data_get($responseJson, 'candidates.0.content.parts.0.text', 'Tidak ada konten analisis yang dihasilkan.');
    }

    /**
     * Catat penggunaan request dan token hari ini.
     */
    public static function recordUsage(int $tokens = 0): void
    {
        try {
            $today  = now()->toDateString();
            $reqKey = "gemini_req_{$today}";
            $tokKey = "gemini_tok_{$today}";

            $currReq = (int) Setting::get($reqKey, 0);
            Setting::set($reqKey, $currReq + 1);

            $currTok = (int) Setting::get($tokKey, 0);
            Setting::set($tokKey, $currTok + $tokens);

            Setting::set('gemini_last_used', now()->toDateTimeString());
            Setting::set('gemini_last_error', '');
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat statistik penggunaan Gemini: ' . $e->getMessage());
        }
    }

    /**
     * Catat status error terakhir dari Gemini API.
     */
    public static function recordError(string $error): void
    {
        try {
            Setting::set('gemini_last_error', $error);
            Setting::set('gemini_last_error_time', now()->toDateTimeString());
        } catch (\Throwable $e) {}
    }

    /**
     * Ambil ringkasan statistik penggunaan kuota Gemini untuk dashboard settings.
     */
    public static function getUsageStats(): array
    {
        $today  = now()->toDateString();
        $apiKey = GeminiKeyService::getKeyForCompany(auth()->user()?->company, 'tiket');
        $hasKey = !empty(trim($apiKey));

        $dailyLimit   = 1500; // 1500 request/hari (Gemini Free Tier)
        $usedRequests = (int) Setting::get("gemini_req_{$today}", 0);
        $usedTokens   = (int) Setting::get("gemini_tok_{$today}", 0);
        $remaining    = max(0, $dailyLimit - $usedRequests);
        $percent      = min(100, (int) round(($usedRequests / $dailyLimit) * 100));

        return [
            'model'              => config('services.gemini.tiket.model', 'gemini-2.0-flash'),
            'daily_limit'        => $dailyLimit,
            'used_requests'      => $usedRequests,
            'remaining_requests' => $remaining,
            'used_tokens'        => $usedTokens,
            'percentage'         => $percent,
            'has_api_key'        => $hasKey,
            'last_used'          => Setting::get('gemini_last_used'),
            'last_error'         => Setting::get('gemini_last_error'),
            'last_error_time'    => Setting::get('gemini_last_error_time'),
        ];
    }
}
