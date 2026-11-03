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
    private ?\App\Models\Company $company = null;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.dokumentasi.api_key', '');
        $this->model  = config('services.gemini.dokumentasi.model', 'gemini-flash-lite-latest');
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
        $key = GeminiKeyService::getKeyForCompany($company, 'documentation');
        return !empty($key) ? $key : ($this->apiKey ?: config('services.gemini.dokumentasi.api_key', ''));
    }

    private function getResolvedModel(): string
    {
        $company = $this->company ?? auth()->user()?->company;
        return GeminiKeyService::getModelForCompany($company, 'documentation');
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
        $apiKey = $this->getResolvedApiKey();
        if (empty($apiKey)) {
            $companyName = (auth()->user()?->company?->name) ? ' untuk perusahaan ' . auth()->user()->company->name : '';
            throw new \RuntimeException("GEMINI_API_KEY_DOCUMENTATION{$companyName} belum dikonfigurasi di file .env.");
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

        // 1. Ambil kandidat tiket relevan & statistik dari DB
        $keywords   = $this->extractKeywords($userQuestion);
        $searchData = $this->searchTickets($keywords, $userQuestion);
        $candidates = $searchData['candidates'];
        $stats      = $searchData['stats'];

        // 2. Susun konteks tiket sebagai teks untuk dikirim ke Gemini
        $ticketContext = $this->buildTicketContext($candidates);

        // 3. Bangun prompt
        $prompt = $this->buildPrompt($userQuestion, $ticketContext, $candidates, $stats, count($imageParts));

        // 4. Kirim ke Gemini (multimodal jika ada gambar)
        $responseText = $this->callGemini($prompt, $imageParts);

        // 5. Parse respons — Gemini diinstruksikan kembalikan JSON
        return $this->parseResponse($responseText, $candidates);
    }

    /**
     * Ekstrak kata kunci penting dari pertanyaan user, buang kata hubung/stopword.
     */
    private function extractKeywords(string $question): array
    {
        // Stopword umum bahasa Indonesia & kata tanya
        $stopwords = [
            'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan', 'ini', 'itu',
            'ada', 'tidak', 'bisa', 'bagaimana', 'kenapa', 'mengapa', 'apa',
            'kapan', 'siapa', 'apakah', 'saya', 'kamu', 'kita', 'kami', 'mereka',
            'cara', 'tolong', 'mohon', 'bantu', 'bantuan', 'ingin', 'mau', 'perlu',
            'gimana', 'punya', 'sudah', 'belum', 'sedang', 'akan', 'lagi', 'terus',
            'selalu', 'sering', 'pernah', 'terjadi', 'kendala', 'masalah', 'tiket',
            'helpdesk', 'sistem', 'seperti', 'atau', 'pada', 'saat', 'ketika',
            'kalau', 'jika', 'maka', 'tentang', 'seputar', 'kasus'
        ];

        $words = preg_split('/[\s,\.\?\!\;\:\(\)\[\]\{\}\/\\\"\'\`\+]+/', strtolower($question));
        $cleaned = [];
        foreach ($words as $word) {
            $w = trim(preg_replace('/[^a-z0-9]/', '', $word));
            if (strlen($w) >= 2 && !in_array($w, $stopwords)) {
                $cleaned[] = $w;
            }
        }

        return array_values(array_unique($cleaned));
    }

    /**
     * Cari tiket dari seluruh riwayat database (seluruh rentang waktu)
     * Menggunakan sistem skoring relevansi multi-bobot (Weighted Relevance Ranking)
     *
     * @return array{candidates: \Illuminate\Database\Eloquent\Collection, stats: array}
     */
    private function searchTickets(array $keywords, string $question): array
    {
        // 1. Ringkasan statistik global seluruh data tiket
        $totalTickets = Ticket::count();
        $statusCounts = Ticket::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        $categoryCounts = Ticket::selectRaw('kategori, count(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori')
            ->toArray();
        $latestClosed = Ticket::where('status', 'Closed')->latest('tanggal_penyelesaian')->value('nomor_tiket');

        $stats = [
            'total'          => $totalTickets,
            'open'           => $statusCounts['Open'] ?? 0,
            'closed'         => $statusCounts['Closed'] ?? 0,
            'eskalasi'       => $statusCounts['Eskalasi'] ?? 0,
            'categories'     => $categoryCounts,
            'latest_closed'  => $latestClosed,
        ];

        $lowerQ = strtolower($question);
        $checkOpenOnly = str_contains($lowerQ, 'open') || str_contains($lowerQ, 'terbuka') || str_contains($lowerQ, 'belum selesai');

        // Jika pertanyaan spesifik meminta status Open/Eskalasi
        if ($checkOpenOnly) {
            $candidates = Ticket::select([
                'id', 'nomor_tiket', 'tanggal_kejadian', 'divisi_toko',
                'kategori', 'prioritas', 'deskripsi_kendala', 'dampak_operasional',
                'status', 'tindakan_dilakukan', 'solusi_diberikan', 'root_cause',
                'tanggal_penyelesaian',
            ])
            ->whereIn('status', ['Open', 'Eskalasi'])
            ->orderByDesc('tanggal_kejadian')
            ->limit(30)
            ->get();

            return ['candidates' => $candidates, 'stats' => $stats];
        }

        // 2. Jika ada kata kunci: Cari di SELURUH riwayat tanpa batas tanggal
        if (!empty($keywords)) {
            // Bangun ekspresi skoring relevansi:
            // Solusi/Root Cause/Tindakan yang ada kata kunci diberi bobot tinggi
            // Kategori dan nama toko/divisi diberi bobot pendukung
            $scoreParts = [];
            $bindings   = [];

            foreach ($keywords as $kw) {
                $kwParam = "%{$kw}%";
                // Bobot 5 jika kata kunci cocok di solusi atau root_cause (sangat berharga untuk pemecahan masalah)
                $scoreParts[] = "(CASE WHEN LOWER(solusi_diberikan) LIKE ? THEN 5 ELSE 0 END)";
                $scoreParts[] = "(CASE WHEN LOWER(root_cause) LIKE ? THEN 5 ELSE 0 END)";
                // Bobot 4 jika cocok di deskripsi kendala / tindakan
                $scoreParts[] = "(CASE WHEN LOWER(deskripsi_kendala) LIKE ? THEN 4 ELSE 0 END)";
                $scoreParts[] = "(CASE WHEN LOWER(tindakan_dilakukan) LIKE ? THEN 3 ELSE 0 END)";
                // Bobot 3 jika cocok di kategori atau divisi / toko
                $scoreParts[] = "(CASE WHEN LOWER(kategori) LIKE ? THEN 3 ELSE 0 END)";
                $scoreParts[] = "(CASE WHEN LOWER(divisi_toko) LIKE ? THEN 3 ELSE 0 END)";

                array_push($bindings, $kwParam, $kwParam, $kwParam, $kwParam, $kwParam, $kwParam);
            }

            $scoreRaw = implode(' + ', $scoreParts);

            $matchedTickets = Ticket::select([
                'id', 'nomor_tiket', 'tanggal_kejadian', 'divisi_toko',
                'kategori', 'prioritas', 'deskripsi_kendala', 'dampak_operasional',
                'status', 'tindakan_dilakukan', 'solusi_diberikan', 'root_cause',
                'tanggal_penyelesaian',
            ])
            ->selectRaw("({$scoreRaw}) as relevance_score", $bindings)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('deskripsi_kendala', 'like', "%{$kw}%")
                      ->orWhere('solusi_diberikan', 'like', "%{$kw}%")
                      ->orWhere('tindakan_dilakukan', 'like', "%{$kw}%")
                      ->orWhere('root_cause', 'like', "%{$kw}%")
                      ->orWhere('kategori', 'like', "%{$kw}%")
                      ->orWhere('divisi_toko', 'like', "%{$kw}%");
                }
            })
            // Utamakan relevansi kesesuaian kasus di atas segalanya (bukan sekadar tanggal baru)
            ->having('relevance_score', '>', 0)
            ->orderByDesc('relevance_score')
            ->orderByDesc('tanggal_kejadian')
            ->limit(25)
            ->get();

            // Jika ditemukan tiket yang relevan dari riwayat manapun, kembalikan langsung
            if ($matchedTickets->isNotEmpty()) {
                return [
                    'candidates' => $matchedTickets,
                    'stats'      => $stats,
                ];
            }
        }

        // 3. Fallback jika pertanyaan sangat abstrak / tidak ada kata kunci cocok sama sekali:
        // Ambil tiket sampel beragam (tiket dengan solusi terlengkap dari berbagai kategori)
        $diverseSample = Ticket::select([
            'id', 'nomor_tiket', 'tanggal_kejadian', 'divisi_toko',
            'kategori', 'prioritas', 'deskripsi_kendala', 'dampak_operasional',
            'status', 'tindakan_dilakukan', 'solusi_diberikan', 'root_cause',
            'tanggal_penyelesaian',
        ])
        ->whereNotNull('solusi_diberikan')
        ->where('solusi_diberikan', '!=', '')
        ->orderByDesc('id')
        ->limit(20)
        ->get();

        return [
            'candidates' => $diverseSample,
            'stats'      => $stats,
        ];
    }

    /**
     * Ubah koleksi tiket menjadi teks konteks ringkas untuk prompt.
     */
    private function buildTicketContext($tickets): string
    {
        if ($tickets->isEmpty()) {
            return 'Tidak ada data tiket spesifik yang ditemukan.';
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
     * Bangun prompt lengkap untuk Gemini agar dapat menjawab luas & analitis.
     */
    private function buildPrompt(string $question, string $context, $tickets, array $stats, int $imageCount = 0): string
    {
        $ticketIds = $tickets->pluck('nomor_tiket')->implode(', ');
        $imageNote = $imageCount > 0
            ? "Catatan: Pengguna juga melampirkan {$imageCount} gambar/tangkapan layar (WhatsApp/pesan/foto kendala). Analisis pesan dan detail visual di dalam gambar tersebut untuk memahami kendala secara mendalam."
            : '';

        $statsText = "Ringkasan Sistem Tiket Saat Ini:\n"
            . "- Total Seluruh Tiket: {$stats['total']}\n"
            . "- Status Open: {$stats['open']}\n"
            . "- Status Closed: {$stats['closed']}\n"
            . "- Status Eskalasi: {$stats['eskalasi']}\n"
            . "- Distribusi Kategori: " . json_encode($stats['categories'], JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Kamu adalah Asisten AI IT Helpdesk & Konsultan Teknis senior internal (Go Note IT).
Tugas utamamu adalah membantu staf IT dan karyawan memahami permasalahan operasional/teknis, riwayat tiket, solusi, tren kendala, hingga memberikan panduan troubleshooting dan pencegahan.

{$statsText}

Berikut adalah data riwayat tiket IT yang relevan dari database (masing-masing baris adalah satu tiket):
---
{$context}
---

Nomor tiket yang tersedia untuk dijadikan sitasi: {$ticketIds}

Pertanyaan staf: "{$question}"
{$imageNote}

Pedoman Menjawab (Jawab secara LUAS, AKURAT, dan SOLUTIF):
1. **Analisis Pertanyaan**:
   - Jika staf menanyakan status atau rekap tiket (misal "berapa tiket open", "kendala paling sering", "status cabang X"): Berikan jawaban presisi berdasarkan 'Ringkasan Sistem Tiket' dan data tiket. Jangan membatasi jawaban hanya pada 1 kategori jika pertanyaannya umum.
   - Jika staf menanyakan kendala atau error tertentu (misal POS macet, CCTV mati, scanner error, printer mati):
     a. Tinjau riwayat tiket di atas. Jika pernah terjadi sebelumnya, jelaskan bagaimana kendala itu diselesaikan saat itu (sebutkan nomor tiketnya).
     b. Berikan wawasan troubleshooting teknis yang mendalam dan langkah preventif agar kendala serupa tidak terulang, bahkan jika di riwayat hanya tertulis langkah singkat.
   - Jika staf berkonsultasi mengenai teknis IT, best practice, atau solusi umum: Berikan penjelasan teknis yang profesional, ramah, terstruktur, dan aplikatif.
2. **Sitasi Tiket**:
   - Cantumkan nomor tiket di daftar "citations" jika dan hanya jika tiket tersebut memang relevan dijadikan contoh atau rujukan. Jika menanyakan rekap umum, jangan menumpuk nomor tiket yang tidak perlu di dalam teks penjelasan, cukup sertakan tiket penting di "citations".
3. **Gaya Komunikasi**:
   - Bahasa Indonesia profesional, ramah, jelas, dan rapi menggunakan formatting markdown (seperti bullet points, bold untuk istilah penting, atau langkah bernomor 1, 2, 3).

Kembalikan respons HANYA dalam format JSON murni (tanpa markdown blok ```json):
{
  "answer": "<jawaban lengkap, rapi, dan mudah dibaca>",
  "citations": ["<nomor_tiket_1>", "<nomor_tiket_2>"]
}
PROMPT;
    }

    /**
     * Kirim prompt ke Gemini API dan kembalikan teks respons mentah.
     */
    private function callGemini(string $prompt, array $imageParts = []): string
    {
        $apiKey = $this->getResolvedApiKey();
        $model  = $this->getResolvedModel();
        $url    = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $parts = [['text' => $prompt]];
        if (!empty($imageParts)) {
            $parts = array_merge($parts, $imageParts);
        }

        $response = Http::timeout(60)->post($url, [
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature'     => 0.5,
                'maxOutputTokens' => 2500,
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
        $apiKey = GeminiKeyService::getKeyForCompany(auth()->user()?->company, 'documentation');
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
