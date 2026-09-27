<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'total'    => Ticket::count(),
            'open'     => Ticket::where('status', 'Open')->count(),
            'closed'   => Ticket::where('status', 'Closed')->count(),
            'eskalasi' => Ticket::where('status', 'Eskalasi')->count(),
        ];

        $recentTickets = Ticket::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact('stats', 'recentTickets', 'user'));
    }
}
