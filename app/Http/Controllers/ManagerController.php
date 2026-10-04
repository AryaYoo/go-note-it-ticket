<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManagerController extends Controller
{
    public function dashboard()
    {
        $totalTickets = \App\Models\Ticket::count();
        $openTickets = \App\Models\Ticket::where('status', 'Open')->count();
        $inProgressTickets = \App\Models\Ticket::where('status', 'In Progress')->count();
        $resolvedTickets = \App\Models\Ticket::where('status', 'Resolved')->count();
        $closedTickets = \App\Models\Ticket::where('status', 'Closed')->count();

        $ticketsByDivisi = \App\Models\Ticket::selectRaw('divisi_toko, count(*) as count')
            ->groupBy('divisi_toko')
            ->get();

        $ticketsByStatus = \App\Models\Ticket::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $ticketsByCategory = \App\Models\Ticket::selectRaw('kategori, count(*) as count')
            ->groupBy('kategori')
            ->get();

        return view('manager.dashboard', compact(
            'totalTickets', 'openTickets', 'inProgressTickets', 'resolvedTickets', 'closedTickets', 'ticketsByDivisi', 'ticketsByStatus', 'ticketsByCategory'
        ));
    }

    public function users()
    {
        $users = \App\Models\User::orderBy('name')->paginate(10);
        return view('manager.users', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users,email',
            'password' => 'required|string|min:2',
            'role' => 'required|in:staff,manager,user',
            'divisi' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Email atau username wajib diisi.',
            'email.unique' => 'Email atau username tersebut sudah digunakan oleh akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 2 karakter.',
        ]);

        \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => $request->role,
            'divisi' => $request->divisi,
        ]);

        return redirect()->route('manager.users')->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, \App\Models\User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:2',
            'role' => 'required|in:staff,manager,user',
            'divisi' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Email atau username wajib diisi.',
            'email.unique' => 'Email atau username tersebut sudah digunakan oleh akun lain.',
            'password.min' => 'Password minimal terdiri dari 2 karakter.',
        ]);

        $data = $request->only('name', 'email', 'role', 'divisi');
        
        if ($request->filled('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('manager.users')->with('success', 'User berhasil diperbarui.');
    }

    public function destroyUser(\App\Models\User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('manager.users')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('manager.users')->with('success', 'User berhasil dihapus.');
    }

    public function settings()
    {
        $showDemoCredentials = \App\Models\Setting::isTrue('show_demo_credentials', true);
        $geminiStats = \App\Services\GeminiService::getUsageStats();
        $docGeminiStats = \App\Services\DocumentationChatService::getUsageStats();
        return view('manager.settings', compact('showDemoCredentials', 'geminiStats', 'docGeminiStats'));
    }

    public function updateSettings(Request $request)
    {
        $showDemo = $request->boolean('show_demo_credentials') ? '1' : '0';
        \App\Models\Setting::set('show_demo_credentials', $showDemo);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'show_demo_credentials' => $showDemo === '1',
                'message' => $showDemo === '1' ? 'Akun demo kini ditampilkan di halaman login.' : 'Akun demo kini disembunyikan dari halaman login.'
            ]);
        }

        return redirect()->route('manager.settings')->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function resetGeminiQuota(Request $request)
    {
        $today = now()->toDateString();
        $type  = $request->input('type', 'all');

        if ($type === 'tiket' || $type === 'all') {
            \App\Models\Setting::set("gemini_req_{$today}", 0);
            \App\Models\Setting::set("gemini_tok_{$today}", 0);
            \App\Models\Setting::set('gemini_last_error', '');
        }

        if ($type === 'documentation' || $type === 'all') {
            \App\Models\Setting::set("doc_gemini_req_{$today}", 0);
            \App\Models\Setting::set("doc_gemini_tok_{$today}", 0);
            \App\Models\Setting::set('doc_gemini_last_error', '');
        }

        $label = $type === 'documentation' ? 'Chat Dokumentasi' : ($type === 'tiket' ? 'Analisis Tiket' : 'Gemini AI');
        return redirect()->route('manager.settings')->with('success', "Statistik kuota {$label} untuk hari ini berhasil di-reset.");
    }
}
