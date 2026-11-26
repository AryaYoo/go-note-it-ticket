<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManagerController extends Controller
{
    public function dashboard()
    {
        $companyId = auth()->user()->company_id;

        $totalTickets     = \App\Models\Ticket::count(); // sudah di-scope otomatis oleh CompanyScope
        $openTickets      = \App\Models\Ticket::where('status', 'Open')->count();
        $inProgressTickets = \App\Models\Ticket::where('status', 'In Progress')->count();
        $resolvedTickets  = \App\Models\Ticket::where('status', 'Resolved')->count();
        $closedTickets    = \App\Models\Ticket::whereIn('status', ['Closed', 'Selesai'])->count();

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
        // Hanya tampilkan user dari tim manager ini (dirinya sendiri + seluruh stafnya)
        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->where(function ($q) {
                $q->where('manager_id', auth()->id())
                  ->orWhere('id', auth()->id());
            })
            ->orderBy('name')
            ->paginate(10);

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
            'company_id' => auth()->user()->company_id, // ikat ke perusahaan manager ini
            'manager_id' => auth()->id(),               // ikat staf ke tim managernya
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => \Illuminate\Support\Facades\Hash::make($request->password),
            'role'       => $request->role,
            'divisi'     => $request->divisi,
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

        // Pastikan user yang dihapus adalah milik perusahaan yang sama
        if ($user->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $user->delete();

        return redirect()->route('manager.users')->with('success', 'User berhasil dihapus.');
    }

    public function ticketSettings()
    {
        $prefix = \App\Models\Setting::get('ticket_prefix', auth()->user()->divisi === 'General Affairs' ? 'GA' : 'IT');
        $defaultPriority = \App\Models\Setting::get('ticket_default_priority', 'Medium');
        $categories = \App\Models\Setting::get('ticket_categories', 'POS, CCTV, NVR, WiFi, Modem, Akun, Laptop, Print Thermal, Email, Lainnya');
        $slaHigh = \App\Models\Setting::get('ticket_sla_high', '24');
        $slaMedium = \App\Models\Setting::get('ticket_sla_medium', '48');
        $slaLow = \App\Models\Setting::get('ticket_sla_low', '72');
        $autoAiSuggestion = \App\Models\Setting::isTrue('ticket_auto_ai_suggestion', true);
        $requireEvidence = \App\Models\Setting::isTrue('ticket_require_evidence', false);
        $shareReportHeader = \App\Models\Setting::get('ticket_share_report_header', 'Open Ticket');

        $companyId = auth()->user()->company_id;
        $priorityRules = \App\Models\Setting::get('gemini_priority_rules', null, $companyId)
            ?: \App\Services\GeminiService::getDefaultPriorityRules($companyId);
        $statusRules = \App\Models\Setting::get('gemini_status_rules', null, $companyId)
            ?: \App\Services\GeminiService::getDefaultStatusRules($companyId);
        $actionRules = \App\Models\Setting::get('gemini_action_rules', null, $companyId)
            ?: \App\Services\GeminiService::getDefaultActionRules($companyId);

        return view('manager.ticket-settings', compact(
            'prefix', 'defaultPriority', 'categories', 'slaHigh', 'slaMedium', 'slaLow',
            'autoAiSuggestion', 'requireEvidence', 'shareReportHeader',
            'priorityRules', 'statusRules', 'actionRules'
        ));
    }

    public function updateTicketSettings(Request $request)
    {
        $request->validate([
            'ticket_prefix' => 'required|string|max:10',
            'ticket_default_priority' => 'required|in:Low,Medium,High,Urgent',
            'ticket_categories' => 'required|string',
            'ticket_sla_high' => 'required|numeric|min:1',
            'ticket_sla_medium' => 'required|numeric|min:1',
            'ticket_sla_low' => 'required|numeric|min:1',
            'gemini_priority_rules' => 'nullable|string',
            'gemini_status_rules' => 'nullable|string',
            'gemini_action_rules' => 'nullable|string',
        ]);

        \App\Models\Setting::set('ticket_prefix', strtoupper(trim($request->ticket_prefix)));
        \App\Models\Setting::set('ticket_default_priority', $request->ticket_default_priority);
        \App\Models\Setting::set('ticket_categories', trim($request->ticket_categories));
        \App\Models\Setting::set('ticket_sla_high', $request->ticket_sla_high);
        \App\Models\Setting::set('ticket_sla_medium', $request->ticket_sla_medium);
        \App\Models\Setting::set('ticket_sla_low', $request->ticket_sla_low);
        \App\Models\Setting::set('ticket_auto_ai_suggestion', $request->boolean('ticket_auto_ai_suggestion') ? '1' : '0');
        \App\Models\Setting::set('ticket_require_evidence', $request->boolean('ticket_require_evidence') ? '1' : '0');
        \App\Models\Setting::set('ticket_share_report_header', trim($request->input('ticket_share_report_header', 'Open Ticket')));

        if ($request->has('gemini_priority_rules')) {
            \App\Models\Setting::set('gemini_priority_rules', trim($request->input('gemini_priority_rules')));
        }
        if ($request->has('gemini_status_rules')) {
            \App\Models\Setting::set('gemini_status_rules', trim($request->input('gemini_status_rules')));
        }
        if ($request->has('gemini_action_rules')) {
            \App\Models\Setting::set('gemini_action_rules', trim($request->input('gemini_action_rules')));
        }

        return redirect()->route('manager.ticket-settings')->with('success', 'Pengaturan tiket dan panduan rules AI berhasil diperbarui.');
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
