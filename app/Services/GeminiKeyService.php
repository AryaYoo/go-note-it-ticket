<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class GeminiKeyService
{
    /**
     * Daftar pilihan model Gemini AI yang didukung.
     */
    public static function getAvailableModels(): array
    {
        return [
            'gemini-2.0-flash' => 'Gemini 2.0 Flash (Direkomendasikan - Sangat Cepat & Cerdas)',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Ringan & Cepat)',
            'gemini-1.5-pro'   => 'Gemini 1.5 Pro (Penalaran Kompleks & Konteks Ekstra Besar)',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash (Model Generasi Terbaru)',
        ];
    }

    /**
     * Parse file .env secara langsung untuk mendapatkan semua variabel
     * yang berkaitan dengan Gemini API Key.
     */
    public static function getAvailableEnvKeys(): array
    {
        $keysMap = [];

        // 1. Kunci default yang selalu didukung
        $defaultKeys = [
            'GEMINI_API_KEY_TIKET',
            'GEMINI_API_KEY_DOCUMENTATION',
        ];

        foreach ($defaultKeys as $dk) {
            $keysMap[$dk] = self::getRawEnvValue($dk) ?? '';
        }

        // 2. Baca file .env baris demi baris
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                if (preg_match('/^(GEMINI_[A-Z0-9_]*KEY[A-Z0-9_]*|GEMINI_API_KEY[A-Z0-9_]*)\s*=\s*(.*)$/i', $line, $matches)) {
                    $keyName = trim($matches[1]);
                    $rawVal  = trim($matches[2]);

                    if (
                        (str_starts_with($rawVal, '"') && str_ends_with($rawVal, '"')) ||
                        (str_starts_with($rawVal, "'") && str_ends_with($rawVal, "'"))
                    ) {
                        $rawVal = substr($rawVal, 1, -1);
                    }

                    $keysMap[$keyName] = $rawVal;
                }
            }
        }

        // 3. Periksa juga getenv() dan $_ENV
        foreach (array_merge($_ENV, getenv()) as $envK => $envV) {
            if (is_string($envK) && preg_match('/^(GEMINI_[A-Z0-9_]*KEY[A-Z0-9_]*|GEMINI_API_KEY[A-Z0-9_]*)$/i', $envK)) {
                if (!isset($keysMap[$envK]) || empty($keysMap[$envK])) {
                    $keysMap[$envK] = is_string($envV) ? $envV : '';
                }
            }
        }

        // 4. Susun daftar array untuk dropdown
        $result = [];
        foreach ($keysMap as $keyName => $value) {
            $val = trim((string)$value);
            $hasValue = !empty($val);

            if ($hasValue) {
                $masked = strlen($val) > 8
                    ? substr($val, 0, 4) . '...' . substr($val, -4)
                    : substr($val, 0, 2) . '...';
            } else {
                $masked = 'Belum diisi';
            }

            $result[] = [
                'key'       => $keyName,
                'value'     => $val,
                'masked'    => $masked,
                'has_value' => $hasValue,
                'label'     => "{$keyName} (" . ($hasValue ? $masked : 'Belum diisi') . ")",
            ];
        }

        usort($result, function ($a, $b) use ($defaultKeys) {
            $aIdx = array_search($a['key'], $defaultKeys);
            $bIdx = array_search($b['key'], $defaultKeys);

            if ($aIdx !== false && $bIdx !== false) {
                return $aIdx <=> $bIdx;
            }
            if ($aIdx !== false) return -1;
            if ($bIdx !== false) return 1;

            return strcmp($a['key'], $b['key']);
        });

        return $result;
    }

    /**
     * Ambil nilai mentah sebuah variabel dari .env / environment secara realtime.
     */
    public static function getRawEnvValue(string $envKeyName): ?string
    {
        $val = getenv($envKeyName);
        if ($val !== false && $val !== null && trim($val) !== '') {
            return trim($val);
        }

        if (isset($_ENV[$envKeyName]) && trim($_ENV[$envKeyName]) !== '') {
            return trim($_ENV[$envKeyName]);
        }

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $pattern = '/^' . preg_quote($envKeyName, '/') . '\s*=\s*(.*)$/m';
            $content = file_get_contents($envPath);
            if (preg_match($pattern, $content, $matches)) {
                $rawVal = trim($matches[1]);
                if (
                    (str_starts_with($rawVal, '"') && str_ends_with($rawVal, '"')) ||
                    (str_starts_with($rawVal, "'") && str_ends_with($rawVal, "'"))
                ) {
                    $rawVal = substr($rawVal, 1, -1);
                }
                return trim($rawVal);
            }
        }

        return null;
    }

    /**
     * Dekripsi atau ekstrak API Key jika disimpan sebagai custom key terenkripsi.
     */
    public static function extractCustomKey(?string $storedVal): ?string
    {
        if (empty($storedVal)) {
            return null;
        }

        $storedVal = trim($storedVal);

        // Jika diawali prefiks custom_enc:
        if (str_starts_with($storedVal, 'custom_enc:')) {
            try {
                return Crypt::decryptString(substr($storedVal, 11));
            } catch (\Throwable $e) {
                return null;
            }
        }

        // Jika terenkripsi langsung
        if (str_starts_with($storedVal, 'eyJ')) {
            try {
                return Crypt::decryptString($storedVal);
            } catch (\Throwable $e) {}
        }

        // Jika diawali custom:
        if (str_starts_with($storedVal, 'custom:')) {
            return substr($storedVal, 7);
        }

        // Jika raw API Key Google (diawali AIza)
        if (str_starts_with($storedVal, 'AIza')) {
            return $storedVal;
        }

        return null;
    }

    /**
     * Resolusi API Key untuk tipe layanan (tiket / documentation) untuk suatu perusahaan.
     */
    public static function getKeyForCompany(?Company $company, string $type = 'tiket'): string
    {
        $defaultKeyName = $type === 'documentation'
            ? 'GEMINI_API_KEY_DOCUMENTATION'
            : 'GEMINI_API_KEY_TIKET';

        $storedKey = null;
        if ($company) {
            $storedKey = $type === 'documentation'
                ? $company->gemini_api_key_documentation
                : $company->gemini_api_key_tiket;
        }

        // 1. Cek apakah ini custom key yang diinput langsung dari UI
        $customKey = self::extractCustomKey($storedKey);
        if (!empty($customKey)) {
            return $customKey;
        }

        // 2. Jika merupakan nama variabel .env (misal GEMINI_API_KEY_*)
        if (!empty($storedKey) && str_starts_with($storedKey, 'GEMINI_')) {
            $resolved = self::getRawEnvValue($storedKey);
            if (!empty($resolved)) {
                return $resolved;
            }
        }

        // 3. Fallback ke default key name di .env
        $fallbackVal = self::getRawEnvValue($defaultKeyName);
        if (!empty($fallbackVal)) {
            return $fallbackVal;
        }

        // 4. Fallback ke config services
        $configKey = $type === 'documentation' ? 'services.gemini.dokumentasi.api_key' : 'services.gemini.tiket.api_key';
        return config($configKey, '');
    }

    /**
     * Resolusi Model Gemini untuk tipe layanan (tiket / documentation) untuk suatu perusahaan.
     */
    public static function getModelForCompany(?Company $company, string $type = 'tiket'): string
    {
        $defaultModel = $type === 'documentation'
            ? config('services.gemini.dokumentasi.model', env('GEMINI_MODEL_DOCUMENTATION', 'gemini-2.0-flash'))
            : config('services.gemini.tiket.model', env('GEMINI_MODEL_TIKET', 'gemini-2.0-flash'));

        if ($company) {
            $chosen = $type === 'documentation'
                ? $company->gemini_model_documentation
                : $company->gemini_model_tiket;

            if (!empty($chosen) && trim($chosen) !== '') {
                return trim($chosen);
            }
        }

        return !empty($defaultModel) ? $defaultModel : 'gemini-2.0-flash';
    }

    /**
     * Format tampilan kunci untuk label kartu / modal.
     */
    public static function formatKeyForDisplay(?string $storedVal): string
    {
        if (empty($storedVal)) {
            return 'Default (.env)';
        }

        $custom = self::extractCustomKey($storedVal);
        if (!empty($custom)) {
            $masked = strlen($custom) > 8
                ? substr($custom, 0, 4) . '...' . substr($custom, -4)
                : '••••••••';
            return "Kustom ({$masked})";
        }

        if (str_starts_with($storedVal, 'GEMINI_')) {
            return $storedVal;
        }

        return 'Default (.env)';
    }

    /**
     * Tes koneksi API Key dan Model ke Google Gemini API secara langsung.
     *
     * @return array{success: bool, message: string}
     */
    public static function testConnection(string $keyInput, string $model = 'gemini-2.0-flash'): array
    {
        $keyInput = trim($keyInput);

        // Jika memilih variabel env
        if (str_starts_with($keyInput, 'GEMINI_')) {
            $apiKey = self::getRawEnvValue($keyInput);
        } else {
            $custom = self::extractCustomKey($keyInput);
            $apiKey = !empty($custom) ? $custom : $keyInput;
        }

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key tidak boleh kosong atau belum memiliki nilai di .env.',
            ];
        }

        $model = trim($model) ?: 'gemini-2.0-flash';

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(15)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Ping. Respond with exactly one word: OK']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 10,
                    'temperature'     => 0.1,
                ]
            ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text') ?? 'OK';
                return [
                    'success' => true,
                    'message' => "Koneksi Berhasil! Model '{$model}' aktif & merespons: \"" . trim($reply) . "\"",
                ];
            }

            $errMsg = $response->json('error.message') ?? $response->body();
            return [
                'success' => false,
                'message' => "Gagal terhubung (HTTP {$response->status()}): {$errMsg}",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal melakukan request: ' . $e->getMessage(),
            ];
        }
    }
}
