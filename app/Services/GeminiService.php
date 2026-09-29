<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $model = config('services.gemini.model', 'gemini-3.8-flash');
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }

    /**
     * Analisis satu atau beberapa tangkapan layar WhatsApp (maks 3 gambar).
     *
     * @param  string[] $imagePaths  Array path file gambar
     */
    public function analyzeWhatsAppScreenshot(array $imagePaths): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY belum dikonfigurasi.');
        }

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

        $prompt = <<<PROMPT
Kamu adalah asisten IT Helpdesk. {$keteranganGambar} Analisis seluruh gambar secara menyeluruh.

Ekstrak informasi dan kembalikan HANYA dalam format JSON murni (tanpa markdown, tanpa ```json, tanpa teks tambahan apapun):

{
  "kategori": "<salah satu: POS | Akun | Device | Lainnya>",
  "prioritas": "<salah satu: Low | Medium | High>",
  "deskripsi_kendala": "<ringkasan singkat kendala yang dilaporkan>",
  "dampak_operasional": "<dampak nyata terhadap operasional toko>",
  "status": "<salah satu: Open | Closed | Eskalasi>",
  "tindakan_dilakukan": "<tindakan penanganan yang sudah dilakukan jika ada, kosongkan jika tidak ada>",
  "tanggal_penyelesaian": "<tanggal selesai format YYYY-MM-DD jika masalah sudah selesai, null jika belum>",
  "solusi_diberikan": "<solusi yang diberikan jika sudah selesai, kosongkan jika belum>",
  "root_cause": "<akar penyebab masalah jika diketahui, kosongkan jika belum>"
}

Panduan penentuan prioritas:
- High: menghambat operasional toko secara total (kasir mati, tidak bisa transaksi)
- Medium: mengganggu sebagian operasional (fitur tertentu error tapi toko bisa beroperasi)
- Low: gangguan kecil, tidak menghambat operasional

Panduan kategori:
- POS: masalah pada sistem kasir / Point of Sale
- Akun: masalah login, password, hak akses
- Device: masalah hardware (komputer, printer, scanner, dll)
- Lainnya: masalah jaringan, internet, atau lainnya

Jika informasi tidak cukup untuk menentukan suatu field, gunakan nilai terbaik yang dapat kamu simpulkan dari konteks.
PROMPT;

        // Gabungkan semua gambar + teks prompt dalam satu request
        $parts = array_merge($imageParts, [['text' => $prompt]]);

        $response = Http::timeout(45)->post("{$this->apiUrl}?key={$this->apiKey}", [
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature'     => 0.2,
                'maxOutputTokens' => 1024,
            ],
        ]);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->json();
            $msg = $body['error']['message'] ?? $response->body();
            if ($status === 429) {
                self::recordError('Rate Limit (429)');
            }
            Log::error('Gemini API error', ['status' => $status, 'message' => $msg]);
            throw new \RuntimeException("Gemini API error ({$status}): {$msg}");
        }

        // Catat penggunaan token & request
        $tokens = (int) $response->json('usageMetadata.totalTokenCount', 0);
        self::recordUsage($tokens);

        $text = $response->json('candidates.0.content.parts.0.text', '');
        // Bersihkan markdown code block jika ada
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Gemini response tidak valid JSON', ['text' => $text]);
            throw new \RuntimeException('Respons AI tidak dapat diproses. Coba ulangi analisis.');
        }

        return $data;
    }

    /**
     * Generate rekomendasi operasional berbasis statistik tiket.
     */
    public function generateRecommendations(array $stats): string
    {
        if (empty($this->apiKey)) {
            return 'API Key belum dikonfigurasi.';
        }

        $statsText = json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Kamu adalah analis IT Operations. Berdasarkan data statistik tiket IT toko berikut:

{$statsText}

Berikan 3-4 rekomendasi operasional yang konkret dan actionable dalam Bahasa Indonesia. 
Format setiap rekomendasi sebagai satu kalimat yang dimulai dengan kata kerja.
Kembalikan HANYA daftar rekomendasi, satu per baris, tanpa penomoran, tanpa bullet, tanpa teks tambahan.
PROMPT;

        $response = Http::timeout(30)->post("{$this->apiUrl}?key={$this->apiKey}", [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature'     => 0.4,
                'maxOutputTokens' => 512,
            ],
        ]);

        if (!$response->successful()) {
            return 'Rekomendasi tidak tersedia saat ini.';
        }

        $tokens = (int) $response->json('usageMetadata.totalTokenCount', 0);
        self::recordUsage($tokens);

        return $response->json('candidates.0.content.parts.0.text', 'Tidak ada rekomendasi.');
    }

    /**
     * Analisis komprehensif untuk tiket atau analisis bulanan menggunakan prompt teks.
     */
    public function analyzeTicket(string $prompt, array $images = []): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY belum dikonfigurasi di file .env.');
        }

        $parts = [['text' => $prompt]];

        $response = Http::timeout(60)->post("{$this->apiUrl}?key={$this->apiKey}", [
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature'     => 0.4,
                'maxOutputTokens' => 4096,
            ],
        ]);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->json();
            $msg = $body['error']['message'] ?? $response->body();
            if ($status === 429) {
                self::recordError('Rate Limit (429)');
            }
            Log::error('Gemini API error on analyzeTicket', ['status' => $status, 'message' => $msg]);
            throw new \RuntimeException("Gemini API error ({$status}): {$msg}");
        }

        $tokens = (int) $response->json('usageMetadata.totalTokenCount', 0);
        self::recordUsage($tokens);

        return $response->json('candidates.0.content.parts.0.text', 'Tidak ada konten analisis yang dihasilkan.');
    }

    /**
     * Catat penggunaan request dan token hari ini.
     */
    public static function recordUsage(int $tokens = 0): void
    {
        try {
            $today = now()->toDateString();
            $reqKey = "gemini_req_{$today}";
            $tokKey = "gemini_tok_{$today}";

            $currReq = (int) \App\Models\Setting::get($reqKey, 0);
            \App\Models\Setting::set($reqKey, $currReq + 1);

            $currTok = (int) \App\Models\Setting::get($tokKey, 0);
            \App\Models\Setting::set($tokKey, $currTok + $tokens);

            \App\Models\Setting::set('gemini_last_used', now()->toDateTimeString());
            \App\Models\Setting::set('gemini_last_error', '');
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
            \App\Models\Setting::set('gemini_last_error', $error);
            \App\Models\Setting::set('gemini_last_error_time', now()->toDateTimeString());
        } catch (\Throwable $e) {}
    }

    /**
     * Ambil ringkasan statistik penggunaan kuota Gemini untuk dashboard settings.
     */
    public static function getUsageStats(): array
    {
        $today = now()->toDateString();
        $dailyLimit = 20; // Batas standar Free Tier per hari/window untuk model terbaru
        $usedRequests = (int) \App\Models\Setting::get("gemini_req_{$today}", 0);
        $usedTokens = (int) \App\Models\Setting::get("gemini_tok_{$today}", 0);
        $remaining = max(0, $dailyLimit - $usedRequests);
        $percent = min(100, (int) round(($usedRequests / $dailyLimit) * 100));

        return [
            'model'              => config('services.gemini.model', 'gemini-3.8-flash'),
            'daily_limit'        => $dailyLimit,
            'used_requests'      => $usedRequests,
            'remaining_requests' => $remaining,
            'used_tokens'        => $usedTokens,
            'percentage'         => $percent,
            'has_api_key'        => !empty(config('services.gemini.api_key')),
            'last_used'          => \App\Models\Setting::get('gemini_last_used'),
            'last_error'         => \App\Models\Setting::get('gemini_last_error'),
            'last_error_time'    => \App\Models\Setting::get('gemini_last_error_time'),
        ];
    }
}
