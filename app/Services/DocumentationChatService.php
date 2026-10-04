<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class DocumentationChatService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.dokumentasi.api_key', '');
        $this->model  = config('services.gemini.dokumentasi.model', 'gemini-2.0-flash');
    }

    /**
     * Cari tiket-tiket yang relevan dengan pertanyaan user,
     * lalu kirim ke Gemini untuk dijawab dengan sitasi (mendukung hingga 3 lampiran gambar).
     *
     * @param  string[] $imagePaths Array path gambar lokal untuk dianalisis multimodal
     * @return array{answer: string, citations: array}
     */
    public function chat(string $userQuestion, array $imagePaths = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY_DOCUMENTATION belum dikonfigurasi di file .env.');
        }

        // Siapkan part gambar untuk Gemini multimodal
        $imageParts = [];
        foreach (array_slice($imagePaths, 0, 3) as $path) {
            if (file_exists($path)) {
                $mimeType = mime_content_type($path) ?: 'image/jpeg';
                $imageParts[] = [
                    'inline_data' => [
                        'mime_type' => $mimeType,
                        'data'      => base64_encode(file_get_contents($path)),
                    ],
                ];
            }
        }

        // 1. Ambil kandidat tiket relevan dari DB (pencarian keyword sederhana)
        $keywords = $this->extractKeywords($userQuestion);
        $candidates = $this->searchTickets($keywords, $userQuestion);

        // 2. Susun konteks tiket sebagai teks untuk dikirim ke Gemini
        $ticketContext = $this->buildTicketContext($candidates);

        // 3. Bangun prompt
        $prompt = $this->buildPrompt($userQuestion, $ticketContext, $candidates, count($imageParts));

        // 4. Kirim ke Gemini (multimodal jika ada gambar)
        $responseText = $this->callGemini($prompt, $imageParts);

        // 5. Parse respons — Gemini diinstruksikan kembalikan JSON
        return $this->parseResponse($responseText, $candidates);
    }

    /**
     * Ekstrak kata kunci penting dari pertanyaan user.
     */
    private function extractKeywords(string $question): array
    {
        // Buang stopword umum bahasa Indonesia
        $stopwords = ['yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan', 'ini',
                      'itu', 'ada', 'tidak', 'bisa', 'bagaimana', 'kenapa', 'mengapa',
                      'apa', 'kapan', 'siapa', 'apakah', 'saya', 'kamu', 'kita',
                      'cara', 'tolong', 'mohon', 'bantu', 'ingin', 'mau', 'perlu',
                      'gimana', 'gimana', 'punya', 'sudah', 'belum', 'sedang'];

        $words = preg_split('/\s+/', strtolower($question));
        return array_values(array_filter($words, function ($word) use ($stopwords) {
            $clean = preg_replace('/[^a-z0-9]/', '', $word);
            return strlen($clean) >= 3 && !in_array($clean, $stopwords);
        }));
    }

    /**
     * Cari tiket dari DB yang relevan berdasarkan keyword + fallback top recent closed.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function searchTickets(array $keywords, string $question)
    {
        $query = Ticket::select([
            'id', 'nomor_tiket', 'tanggal_kejadian', 'divisi_toko',
            'kategori', 'prioritas', 'deskripsi_kendala', 'dampak_operasional',
            'status', 'tindakan_dilakukan', 'solusi_diberikan', 'root_cause',
            'tanggal_penyelesaian',
        ]);

        if (!empty($keywords)) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('deskripsi_kendala', 'like', "%{$kw}%")
                      ->orWhere('solusi_diberikan', 'like', "%{$kw}%")
                      ->orWhere('tindakan_dilakukan', 'like', "%{$kw}%")
                      ->orWhere('root_cause', 'like', "%{$kw}%")
                      ->orWhere('kategori', 'like', "%{$kw}%")
                      ->orWhere('divisi_toko', 'like', "%{$kw}%");
                }
            });
        }

        $results = $query->orderByRaw("CASE WHEN status = 'Closed' THEN 0 ELSE 1 END")
                         ->orderBy('tanggal_kejadian', 'desc')
                         ->limit(20)
                         ->get();

        // Jika hasil terlalu sedikit, tambahkan tiket Closed terbaru sebagai konteks fallback
        if ($results->count() < 5) {
            $fallback = Ticket::select([
                'id', 'nomor_tiket', 'tanggal_kejadian', 'divisi_toko',
                'kategori', 'prioritas', 'deskripsi_kendala', 'dampak_operasional',
                'status', 'tindakan_dilakukan', 'solusi_diberikan', 'root_cause',
                'tanggal_penyelesaian',
            ])
            ->where('status', 'Closed')
            ->whereNotIn('id', $results->pluck('id'))
            ->orderBy('tanggal_penyelesaian', 'desc')
            ->limit(10)
            ->get();

            $results = $results->merge($fallback)->take(20);
        }

        return $results;
    }

    /**
     * Ubah koleksi tiket menjadi teks konteks ringkas untuk prompt.
     */
    private function buildTicketContext($tickets): string
    {
        if ($tickets->isEmpty()) {
            return 'Tidak ada data tiket yang tersedia saat ini.';
        }

        $lines = [];
        foreach ($tickets as $t) {
            $tgl = $t->tanggal_kejadian ? $t->tanggal_kejadian->format('d M Y') : '-';
            $sol = $t->solusi_diberikan ? "Solusi: {$t->solusi_diberikan}" : '';
            $rc  = $t->root_cause      ? "Root Cause: {$t->root_cause}" : '';
            $tind = $t->tindakan_dilakukan ? "Tindakan: {$t->tindakan_dilakukan}" : '';

            $lines[] = implode(' | ', array_filter([
                "[{$t->nomor_tiket}]",
                "Tgl: {$tgl}",
                "Toko/Divisi: {$t->divisi_toko}",
                "Kategori: {$t->kategori}",
                "Prioritas: {$t->prioritas}",
                "Status: {$t->status}",
                "Kendala: {$t->deskripsi_kendala}",
                $tind,
                $sol,
                $rc,
            ]));
        }

        return implode("\n", $lines);
    }

    /**
     * Bangun prompt lengkap untuk Gemini.
     */
    private function buildPrompt(string $question, string $context, $tickets, int $imageCount = 0): string
    {
        $ticketIds = $tickets->pluck('nomor_tiket')->implode(', ');
        $imageNote = $imageCount > 0
            ? "Catatan: Pengguna juga melampirkan {$imageCount} gambar/tangkapan layar percakapan WhatsApp atau pesan kendala. Analisis teks dan percakapan di dalam gambar tersebut untuk memahami kendala yang dilaporkan."
            : '';

        return <<<PROMPT
Kamu adalah asisten IT Helpdesk internal yang bertugas menjawab pertanyaan staf berdasarkan riwayat tiket IT yang sudah pernah dicatat dalam sistem.

Berikut adalah data tiket IT yang relevan dari database (masing-masing baris adalah satu tiket):
---
{$context}
---

Nomor tiket yang tersedia untuk dijadikan sitasi: {$ticketIds}

Pertanyaan staf: "{$question}"
{$imageNote}

Tugasmu:
1. Jawab pertanyaan tersebut berdasarkan data tiket yang diberikan di atas serta informasi dari gambar lampiran jika ada.
2. Jika ada tiket yang relevan, sebutkan nomor tiketnya sebagai sitasi.
3. Jika tidak ada tiket yang relevan sama sekali, jawab dengan jujur bahwa belum ada riwayat serupa.
4. Gunakan Bahasa Indonesia yang ramah dan mudah dipahami.
5. Berikan solusi atau langkah penanganan jika tersedia dari data tiket.

Kembalikan respons HANYA dalam format JSON murni (tanpa markdown, tanpa ```json):
{
  "answer": "<jawaban lengkap dalam Bahasa Indonesia, boleh menggunakan markdown ringan seperti **bold** atau bullet - >",
  "citations": ["<nomor_tiket_1>", "<nomor_tiket_2>"]
}

Kolom "citations" hanya berisi nomor tiket yang benar-benar relevan dan kamu jadikan referensi dalam jawaban. Boleh kosong array [] jika tidak ada.
PROMPT;
    }

    /**
     * Kirim prompt ke Gemini API dan kembalikan teks respons mentah.
     */
    private function callGemini(string $prompt, array $imageParts = []): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $parts = [['text' => $prompt]];
        if (!empty($imageParts)) {
            $parts = array_merge($parts, $imageParts);
        }

        $response = Http::timeout(60)->post($url, [
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature'     => 0.3,
                'maxOutputTokens' => 2048,
            ],
        ]);

        if (!$response->successful()) {
            $msg = $response->json('error.message') ?? $response->body();
            self::recordError("Error HTTP {$response->status()}: " . \Illuminate\Support\Str::limit($msg, 80));
            Log::error('DocumentationChat Gemini error', ['status' => $response->status(), 'msg' => $msg]);
            throw new \RuntimeException("Gemini API error ({$response->status()}): {$msg}");
        }

        $tokens = (int) $response->json('usageMetadata.totalTokenCount', 0);
        self::recordUsage($tokens);

        return data_get($response->json(), 'candidates.0.content.parts.0.text', '');
    }

    /**
     * Catat penggunaan request dan token hari ini untuk Chat Dokumentasi.
     */
    public static function recordUsage(int $tokens = 0): void
    {
        try {
            $today  = now()->toDateString();
            $reqKey = "doc_gemini_req_{$today}";
            $tokKey = "doc_gemini_tok_{$today}";

            $currReq = (int) Setting::get($reqKey, 0);
            Setting::set($reqKey, $currReq + 1);

            $currTok = (int) Setting::get($tokKey, 0);
            Setting::set($tokKey, $currTok + $tokens);

            Setting::set('doc_gemini_last_used', now()->toDateTimeString());
            Setting::set('doc_gemini_last_error', '');
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat statistik penggunaan Gemini Dokumentasi: ' . $e->getMessage());
        }
    }

    /**
     * Catat status error terakhir dari Gemini API Chat Dokumentasi.
     */
    public static function recordError(string $error): void
    {
        try {
            Setting::set('doc_gemini_last_error', $error);
            Setting::set('doc_gemini_last_error_time', now()->toDateTimeString());
        } catch (\Throwable $e) {}
    }

    /**
     * Ambil ringkasan statistik penggunaan kuota Gemini Dokumentasi untuk dashboard settings.
     */
    public static function getUsageStats(): array
    {
        $today  = now()->toDateString();
        $apiKey = config('services.gemini.dokumentasi.api_key', '');
        $hasKey = !empty(trim($apiKey));

        $dailyLimit   = 1500; // 1500 request/hari (Gemini Free Tier)
        $usedRequests = (int) Setting::get("doc_gemini_req_{$today}", 0);
        $usedTokens   = (int) Setting::get("doc_gemini_tok_{$today}", 0);
        $remaining    = max(0, $dailyLimit - $usedRequests);
        $percent      = min(100, (int) round(($usedRequests / $dailyLimit) * 100));

        return [
            'model'              => config('services.gemini.dokumentasi.model', 'gemini-2.0-flash'),
            'daily_limit'        => $dailyLimit,
            'used_requests'      => $usedRequests,
            'remaining_requests' => $remaining,
            'used_tokens'        => $usedTokens,
            'percentage'         => $percent,
            'has_api_key'        => $hasKey,
            'last_used'          => Setting::get('doc_gemini_last_used'),
            'last_error'         => Setting::get('doc_gemini_last_error'),
            'last_error_time'    => Setting::get('doc_gemini_last_error_time'),
        ];
    }

    /**
     * Parse teks JSON dari Gemini dan gabungkan data tiket untuk tombol sitasi.
     *
     * @return array{answer: string, citations: array}
     */
    private function parseResponse(string $rawText, $candidates): array
    {
        // Bersihkan markdown code block jika ada
        $text = preg_replace('/```json\s*/i', '', $rawText);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        $parsed = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($parsed['answer'])) {
            Log::warning('DocumentationChat: Gemini response tidak valid JSON', ['raw' => $rawText]);
            // Fallback: kembalikan teks mentah sebagai jawaban tanpa sitasi
            return [
                'answer'    => $rawText ?: 'Maaf, tidak dapat memproses jawaban saat ini.',
                'citations' => [],
            ];
        }

        $citedNumbers = (array) ($parsed['citations'] ?? []);

        // Cari data lengkap tiket yang di-cite untuk membangun tombol link
        $citationData = [];
        foreach ($citedNumbers as $nomorTiket) {
            $ticket = $candidates->firstWhere('nomor_tiket', $nomorTiket);
            if ($ticket) {
                $citationData[] = [
                    'nomor_tiket'     => $ticket->nomor_tiket,
                    'deskripsi'       => \Str::limit($ticket->deskripsi_kendala, 60),
                    'status'          => $ticket->status,
                    'kategori'        => $ticket->kategori,
                    'url'             => route('tickets.show', $ticket->id),
                ];
            }
        }

        return [
            'answer'    => $parsed['answer'],
            'citations' => $citationData,
        ];
    }
}
