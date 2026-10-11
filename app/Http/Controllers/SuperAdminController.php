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
        return view('superadmin.companies', compact('companies'));
    }

    public function storeCompany(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'nullable|email|max:255',
            'manager_name'     => 'required|string|max:255',
            'manager_email'    => 'required|email|max:255|unique:users,email',
            'manager_password' => 'required|string|min:6',
        ], [
            'manager_email.unique'    => 'Email manager sudah digunakan oleh akun lain.',
            'manager_password.min'    => 'Password manager minimal 6 karakter.',
        ]);

        // Buat perusahaan baru
        $company = Company::create([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name) . '-' . Str::random(4),
            'email'     => $request->email,
            'is_active' => true,
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
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $company->update([
            'name'      => $request->name,
            'email'     => $request->email,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.companies')
            ->with('success', "Perusahaan \"{$company->name}\" berhasil diperbarui.");
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
