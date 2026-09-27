<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\MonthlyAnalysis;
use App\Models\Ticket;
use App\Services\GeminiService;
use Carbon\Carbon;

class MonthlyAnalysisController extends Controller
{
    public function index()
    {
        $analyses = MonthlyAnalysis::orderBy('year', 'desc')->orderBy('month', 'desc')->paginate(10);
        return view('manager.analysis.index', compact('analyses'));
    }

    public function show($id)
    {
        $analysis = MonthlyAnalysis::findOrFail($id);
        return view('manager.analysis.show', compact('analysis'));
    }

    public function createByAi(Request $request, GeminiService $geminiService)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;

        // Check if already exists
        $existing = MonthlyAnalysis::where('month', $month)->where('year', $year)->first();
        if ($existing) {
            return redirect()->route('manager.analysis.show', $existing->id)->with('error', 'Analisis untuk bulan ini sudah ada.');
        }

        // Get tickets for this month
        $tickets = Ticket::whereMonth('created_at', $month)->whereYear('created_at', $year)->get();
        
        if ($tickets->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada tiket pada bulan ini untuk dianalisis.');
        }

        // Prepare data for AI
        $ticketData = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'no_tiket' => $ticket->nomor_tiket,
                'status' => $ticket->status,
                'priority' => $ticket->prioritas,
                'kategori' => $ticket->kategori,
                'divisi' => $ticket->divisi_toko,
                'user' => $ticket->user ? $ticket->user->name : $ticket->nama_pelapor,
                'waktu_pembuatan' => $ticket->created_at->format('Y-m-d H:i:s'),
                'subject' => $ticket->deskripsi_kendala,
                'detail' => $ticket->dampak_operasional,
            ];
        })->toArray();

        // Prompt for AI
        $monthName = Carbon::create($year, $month, 1)->translatedFormat('F');
        $prompt = "Buatkan analisis kinerja IT Support untuk bulan $monthName $year berdasarkan data tiket berikut. "
            . "Berikan ringkasan total tiket, breakdown berdasarkan status dan kategori, serta temukan pola atau masalah yang sering muncul. "
            . "Berikan juga rekomendasi perbaikan. Format output dalam bentuk Markdown yang rapi dan mudah dibaca (gunakan heading, list, dan tabel jika perlu).\n\nData Tiket:\n" 
            . json_encode($ticketData, JSON_PRETTY_PRINT);

        try {
            $analysisContent = $geminiService->analyzeTicket($prompt, []);
            
            $analysis = MonthlyAnalysis::create([
                'title' => "Analisis Kinerja Bulan $monthName $year",
                'month' => $month,
                'year' => $year,
                'content' => $analysisContent,
                'raw_data' => json_encode($ticketData)
            ]);

            return redirect()->route('manager.analysis.show', $analysis->id)->with('success', 'Analisis bulanan berhasil dibuat oleh AI.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat analisis: ' . $e->getMessage());
        }
    }
}
