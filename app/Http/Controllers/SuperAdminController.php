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
            ->with('company')
            ->orderBy('name')
            ->paginate(10);

        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('superadmin.managers', compact('managers', 'companies'));
    }

    public function storeManager(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name'       => 'required|string|max:255',
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
            'divisi'     => null,
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
}
