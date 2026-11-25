<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function dashboard()
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('is_active', true)->count();
        $totalUsers = User::whereNotNull('company_id')->count();
        $totalManagers = User::where('role', 'manager')->count();

        $companies = Company::withCount(['users', 'tickets'])->latest()->get();

        return view('superadmin.dashboard', compact(
            'totalCompanies',
            'activeCompanies',
            'totalUsers',
            'totalManagers',
            'companies'
        ));
    }

    // -------------------------------------------------------------------------
    // Companies
    // -------------------------------------------------------------------------

    public function companies()
    {
        $companies = Company::withCount(['users', 'tickets'])->latest()->paginate(10);
        $envKeys   = \App\Services\GeminiKeyService::getAvailableEnvKeys();
        $models    = \App\Services\GeminiKeyService::getAvailableModels();

        return view('superadmin.companies', compact('companies', 'envKeys', 'models'));
    }

    public function storeCompany(Request $request)
    {
        $request->validate([
            'name'                              => 'required|string|max:255',
            'email'                             => 'nullable|email|max:255',
            'gemini_api_key_tiket'              => 'nullable|string|max:100',
            'gemini_custom_key_tiket'           => 'nullable|string|max:500',
            'gemini_model_tiket'                => 'nullable|string|max:60',
            'gemini_custom_model_tiket'         => 'nullable|string|max:60',
            'gemini_api_key_documentation'      => 'nullable|string|max:100',
            'gemini_custom_key_documentation'   => 'nullable|string|max:500',
            'gemini_model_documentation'        => 'nullable|string|max:60',
            'gemini_custom_model_documentation' => 'nullable|string|max:60',
            'manager_name'                      => 'required|string|max:255',
            'manager_email'                     => 'required|email|max:255|unique:users,email',
            'manager_password'                  => 'required|string|min:6',
        ], [
            'manager_email.unique'    => 'Email manager sudah digunakan oleh akun lain.',
            'manager_password.min'    => 'Password manager minimal 6 karakter.',
        ]);

        // Resolusi API Key Tiket
        $keyTiket = null;
        if ($request->gemini_api_key_tiket === '__CUSTOM__' && !empty($request->gemini_custom_key_tiket)) {
            $keyTiket = 'custom_enc:' . \Illuminate\Support\Facades\Crypt::encryptString(trim($request->gemini_custom_key_tiket));
        } elseif (!empty($request->gemini_api_key_tiket) && $request->gemini_api_key_tiket !== '__CUSTOM__') {
            $keyTiket = trim($request->gemini_api_key_tiket);
        }

        // Resolusi Model Tiket
        $modelTiket = $request->gemini_model_tiket === '__CUSTOM__'
            ? trim($request->gemini_custom_model_tiket)
            : trim($request->gemini_model_tiket);

        // Resolusi API Key Dokumentasi
        $keyDoc = null;
        if ($request->gemini_api_key_documentation === '__CUSTOM__' && !empty($request->gemini_custom_key_documentation)) {
            $keyDoc = 'custom_enc:' . \Illuminate\Support\Facades\Crypt::encryptString(trim($request->gemini_custom_key_documentation));
        } elseif (!empty($request->gemini_api_key_documentation) && $request->gemini_api_key_documentation !== '__CUSTOM__') {
            $keyDoc = trim($request->gemini_api_key_documentation);
        }

        // Resolusi Model Dokumentasi
        $modelDoc = $request->gemini_model_documentation === '__CUSTOM__'
            ? trim($request->gemini_custom_model_documentation)
            : trim($request->gemini_model_documentation);

        // Buat perusahaan baru
        $company = Company::create([
            'name'                         => $request->name,
            'slug'                         => Str::slug($request->name) . '-' . Str::random(4),
            'email'                        => $request->email,
            'gemini_api_key_tiket'         => $keyTiket ?: null,
            'gemini_model_tiket'           => $modelTiket ?: null,
            'gemini_api_key_documentation' => $keyDoc ?: null,
            'gemini_model_documentation'   => $modelDoc ?: null,
            'is_active'                    => true,
        ]);

        // Buat akun manager untuk perusahaan tersebut
        User::create([
            'company_id' => $company->id,
            'name'       => $request->manager_name,
            'email'      => $request->manager_email,
            'password'   => Hash::make($request->manager_password),
            'role'       => 'manager',
            'divisi'     => null,
        ]);

        return redirect()->route('superadmin.companies')
            ->with('success', "Perusahaan \"{$company->name}\" berhasil ditambahkan beserta akun manager-nya.");
    }

    public function updateCompany(Request $request, Company $company)
    {
        $request->validate([
            'name'                              => 'required|string|max:255',
            'email'                             => 'nullable|email|max:255',
            'gemini_api_key_tiket'              => 'nullable|string|max:100',
            'gemini_custom_key_tiket'           => 'nullable|string|max:500',
            'gemini_model_tiket'                => 'nullable|string|max:60',
            'gemini_custom_model_tiket'         => 'nullable|string|max:60',
            'gemini_api_key_documentation'      => 'nullable|string|max:100',
            'gemini_custom_key_documentation'   => 'nullable|string|max:500',
            'gemini_model_documentation'        => 'nullable|string|max:60',
            'gemini_custom_model_documentation' => 'nullable|string|max:60',
            'is_active'                         => 'boolean',
        ]);

        // Resolusi API Key Tiket
        $keyTiket = $company->gemini_api_key_tiket;
        if ($request->gemini_api_key_tiket === '__CUSTOM__') {
            if (!empty($request->gemini_custom_key_tiket)) {
                $keyTiket = 'custom_enc:' . \Illuminate\Support\Facades\Crypt::encryptString(trim($request->gemini_custom_key_tiket));
            }
        } elseif (!empty($request->gemini_api_key_tiket)) {
            $keyTiket = trim($request->gemini_api_key_tiket);
        } else {
            $keyTiket = null;
        }

        // Resolusi Model Tiket
        $modelTiket = $request->gemini_model_tiket === '__CUSTOM__'
            ? trim($request->gemini_custom_model_tiket)
            : trim($request->gemini_model_tiket);

        // Resolusi API Key Dokumentasi
        $keyDoc = $company->gemini_api_key_documentation;
        if ($request->gemini_api_key_documentation === '__CUSTOM__') {
            if (!empty($request->gemini_custom_key_documentation)) {
                $keyDoc = 'custom_enc:' . \Illuminate\Support\Facades\Crypt::encryptString(trim($request->gemini_custom_key_documentation));
            }
        } elseif (!empty($request->gemini_api_key_documentation)) {
            $keyDoc = trim($request->gemini_api_key_documentation);
        } else {
            $keyDoc = null;
        }

        // Resolusi Model Dokumentasi
        $modelDoc = $request->gemini_model_documentation === '__CUSTOM__'
            ? trim($request->gemini_custom_model_documentation)
            : trim($request->gemini_model_documentation);

        $company->update([
            'name'                         => $request->name,
            'email'                        => $request->email,
            'gemini_api_key_tiket'         => $keyTiket,
            'gemini_model_tiket'           => $modelTiket ?: null,
            'gemini_api_key_documentation' => $keyDoc,
            'gemini_model_documentation'   => $modelDoc ?: null,
            'is_active'                    => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.companies')
            ->with('success', "Perusahaan \"{$company->name}\" berhasil diperbarui.");
    }

    /**
     * Cek validitas API Key dan Model Gemini secara realtime via AJAX.
     */
    public function testGeminiConnection(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'model'   => 'nullable|string',
        ]);

        $result = \App\Services\GeminiKeyService::testConnection(
            $request->api_key,
            $request->model ?: 'gemini-2.0-flash'
        );

        return response()->json($result);
    }

    public function destroyCompany(Company $company)
    {
        // Cegah hapus perusahaan default
        if ($company->id === 1) {
            return redirect()->route('superadmin.companies')
                ->with('error', 'Perusahaan utama tidak dapat dihapus.');
        }

        $company->delete();

        return redirect()->route('superadmin.companies')
            ->with('success', 'Perusahaan berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Managers (users per company)
    // -------------------------------------------------------------------------

    public function managers()
    {
        $managers = User::where('role', 'manager')
            ->with(['company', 'staff'])
            ->orderBy('name')
            ->paginate(10);

        $companies = Company::where('is_active', true)->orderBy('name')->get();

        // Semua staff per company untuk modal assign
        $allStaff = User::where('role', 'staff')
            ->orderBy('name')
            ->get()
            ->groupBy('company_id');

        return view('superadmin.managers', compact('managers', 'companies', 'allStaff'));
    }

    public function storeManager(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name'       => 'required|string|max:255',
            'divisi'     => 'nullable|string|max:255',
            'email'      => 'required|email|max:255|unique:users,email',
            'password'   => 'required|string|min:6',
        ], [
            'email.unique'    => 'Email sudah digunakan oleh akun lain.',
            'password.min'    => 'Password minimal 6 karakter.',
        ]);

        User::create([
            'company_id' => $request->company_id,
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => 'manager',
            'divisi'     => $request->filled('divisi') ? trim($request->divisi) : null,
        ]);

        return redirect()->route('superadmin.managers')
            ->with('success', 'Manager baru berhasil ditambahkan.');
    }

    public function destroyManager(User $user)
    {
        if ($user->role !== 'manager') {
            return redirect()->route('superadmin.managers')
                ->with('error', 'User yang dipilih bukan seorang manager.');
        }

        $user->delete();

        return redirect()->route('superadmin.managers')
            ->with('success', 'Manager berhasil dihapus.');
    }

    public function assignStaff(Request $request, User $user)
    {
        if ($user->role !== 'manager') {
            return redirect()->route('superadmin.managers')->with('error', 'User bukan manager.');
        }

        $staffIds = $request->input('staff_ids', []);

        // Lepas semua staff lama dari manager ini
        User::where('manager_id', $user->id)->update(['manager_id' => null]);

        // Assign staff baru
        if (!empty($staffIds)) {
            User::whereIn('id', $staffIds)
                ->where('role', 'staff')
                ->where('company_id', $user->company_id)
                ->update(['manager_id' => $user->id]);
        }

        return redirect()->route('superadmin.managers')
            ->with('success', "Anggota tim {$user->name} berhasil diperbarui.");
    }

    // -------------------------------------------------------------------------
    // Export Database SQL
    // -------------------------------------------------------------------------

    public function exportDatabase()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Akses ditolak. Fitur ini hanya untuk Super Admin.');
        }

        // Tingkatkan limit eksekusi & memori untuk database dump
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $dbConfig = config('database.connections.mysql');
        $host     = $dbConfig['host'] ?? '127.0.0.1';
        $port     = $dbConfig['port'] ?? '3306';
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'] ?? '';

        $filename = 'backup_' . $database . '_' . date('Y-m-d_His') . '.sql';

        // 1. Coba gunakan mysqldump jika tersedia (performa cepat & standar MySQL)
        $mysqldumpPaths = [
            'mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        $dumpBinary = null;
        foreach ($mysqldumpPaths as $path) {
            if ($path === 'mysqldump') {
                $test = @exec("mysqldump --version 2>&1", $out, $code);
                if ($code === 0) {
                    $dumpBinary = 'mysqldump';
                    break;
                }
            } elseif (@file_exists($path)) {
                $dumpBinary = $path;
                break;
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'db_dump_');

        if ($dumpBinary) {
            $cmd = escapeshellarg($dumpBinary)
                . " --host=" . escapeshellarg($host)
                . " --port=" . escapeshellarg($port)
                . " --user=" . escapeshellarg($username);

            if ($password !== '' && $password !== null) {
                $cmd .= " --password=" . escapeshellarg($password);
            }

            $cmd .= " --default-character-set=utf8mb4 --routines --triggers " . escapeshellarg($database) . " > " . escapeshellarg($tempFile);

            @exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($tempFile) && filesize($tempFile) > 100) {
                return response()->download($tempFile, $filename, [
                    'Content-Type' => 'application/sql',
                ])->deleteFileAfterSend(true);
            }
        }

        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }

        // 2. Fallback: Ekspor SQL Native via PHP PDO
        return response()->streamDownload(function () use ($database) {
            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $tablesResult = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
            $tableKey = "Tables_in_" . $database;

            echo "-- ========================================================\n";
            echo "-- Go-Note Database SQL Dump\n";
            echo "-- Database: {$database}\n";
            echo "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
            echo "-- ========================================================\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n";
            echo "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n";
            echo "SET time_zone = \"+00:00\";\n\n";

            foreach ($tablesResult as $row) {
                $tableName = $row->$tableKey;

                // Structure
                $createTable = \Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $createTable[0]->{'Create Table'} ?? '';

                echo "-- --------------------------------------------------------\n";
                echo "-- Table structure for `{$tableName}`\n";
                echo "-- --------------------------------------------------------\n";
                echo "DROP TABLE IF EXISTS `{$tableName}`;\n";
                echo $createSql . ";\n\n";

                // Data
                $count = \Illuminate\Support\Facades\DB::table($tableName)->count();
                if ($count > 0) {
                    echo "-- Dumping data for table `{$tableName}` ({$count} rows)\n";
                    \Illuminate\Support\Facades\DB::table($tableName)->orderBy(\Illuminate\Support\Facades\DB::raw('1'))->chunk(200, function ($rows) use ($tableName, $pdo) {
                        if ($rows->isEmpty()) return;

                        $firstRow = (array) $rows->first();
                        $columns = array_keys($firstRow);
                        $colList = implode('`, `', $columns);

                        echo "INSERT INTO `{$tableName}` (`{$colList}`) VALUES\n";
                        $valuesArr = [];
                        foreach ($rows as $r) {
                            $rowArr = (array) $r;
                            $valParts = [];
                            foreach ($rowArr as $val) {
                                if (is_null($val)) {
                                    $valParts[] = 'NULL';
                                } elseif (is_numeric($val) && !is_string($val)) {
                                    $valParts[] = $val;
                                } else {
                                    $valParts[] = $pdo->quote($val);
                                }
                            }
                            $valuesArr[] = "(" . implode(', ', $valParts) . ")";
                        }
                        echo implode(",\n", $valuesArr) . ";\n\n";
                    });
                }
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            echo "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";
        }, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
