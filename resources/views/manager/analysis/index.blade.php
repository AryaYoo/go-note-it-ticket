@extends('layouts.app')
@section('title', 'Analisis Bulanan (AI)')
@section('page-title', 'Analisis Bulanan (AI)')

@push('head')
<style>
    .analysis-form-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .analysis-form-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 14px;
    }
    .analysis-form-row {
        display: flex;
        align-items: flex-end;
        gap: 14px;
        flex-wrap: wrap;
    }
    .analysis-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .analysis-input-group label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text);
    }
    .analysis-input-group select {
        padding: 8px 12px;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 13px;
        outline: none;
        background: #fff;
        min-width: 140px;
    }

    .analysis-table-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .analysis-table-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
    }
    .analysis-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }
    .analysis-table th {
        background: #F9FAFB;
        padding: 12px 16px;
        font-weight: 600;
        font-size: 11.5px;
        text-transform: uppercase;
        color: var(--muted);
        border-bottom: 1px solid var(--border);
    }
    .analysis-table td {
        padding: 13px 16px;
        border-bottom: 1px solid #F3F4F6;
        color: var(--text);
    }
    .analysis-table tr:last-child td {
        border-bottom: none;
    }
</style>
@endpush

@section('content')
<div class="page-header" style="margin-bottom: 20px;">
    <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--text);">Analisis Bulanan AI</h1>
        <div class="page-header-sub">Generate evaluasi performa operasional IT toko menggunakan kecerdasan buatan</div>
    </div>
</div>

{{-- Generate AI Analysis Card --}}
<div class="analysis-form-card">
    <div class="analysis-form-title">Buat Analisis Laporan Baru</div>
    <form action="{{ route('manager.analysis.create') }}" method="POST" class="analysis-form-row">
        @csrf
        <div class="analysis-input-group">
            <label for="month">Bulan</label>
            <select name="month" id="month">
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ date('n') == $i ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
        </div>
        <div class="analysis-input-group">
            <label for="year">Tahun</label>
            <select name="year" id="year">
                @for($i = date('Y'); $i >= 2020; $i--)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                Generate dengan AI
            </button>
        </div>
    </form>
</div>

{{-- History Table --}}
<div class="analysis-table-card">
    <div class="analysis-table-title">Riwayat Analisis Bulanan</div>
    <div style="overflow-x: auto;">
        <table class="analysis-table">
            <thead>
                <tr>
                    <th>Judul Analisis</th>
                    <th>Waktu Dibuat</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($analyses as $analysis)
                <tr>
                    <td style="font-weight: 600;">{{ $analysis->title }}</td>
                    <td style="color: var(--muted);">{{ $analysis->created_at->format('d M Y, H:i') }}</td>
                    <td style="text-align: right;">
                        <a href="{{ route('manager.analysis.show', $analysis->id) }}" class="btn btn-secondary btn-sm">Lihat Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: var(--muted); padding: 24px;">Belum ada analisis bulanan yang digenerate.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($analyses->hasPages())
    <div style="padding: 14px 20px; border-top: 1px solid var(--border);">
        {{ $analyses->links() }}
    </div>
    @endif
</div>
@endsection
