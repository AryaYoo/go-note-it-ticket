@extends('layouts.app')
@section('title', $analysis->title)
@section('page-title', 'Detail Analisis AI')

@push('head')
<style>
    .analysis-detail-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .analysis-detail-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .analysis-detail-body {
        padding: 28px 24px;
        font-size: 14px;
        line-height: 1.7;
        color: var(--text);
    }
    .analysis-detail-body h1, 
    .analysis-detail-body h2, 
    .analysis-detail-body h3 {
        margin-top: 20px;
        margin-bottom: 10px;
        color: var(--text);
    }
    .analysis-detail-body ul, 
    .analysis-detail-body ol {
        margin-left: 20px;
        margin-bottom: 16px;
    }
    .analysis-detail-body p {
        margin-bottom: 14px;
    }
    .analysis-detail-footer {
        padding: 14px 24px;
        background: #F9FAFB;
        border-top: 1px solid var(--border);
        font-size: 12px;
        color: var(--muted);
    }
</style>
@endpush

@section('content')
<div class="analysis-detail-card">
    <div class="analysis-detail-header">
        <div>
            <h1 style="font-size: 18px; font-weight: 700; color: var(--text);">{{ $analysis->title }}</h1>
            <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">Laporan Ringkasan Kinerja & Rekomendasi</div>
        </div>
        <a href="{{ route('manager.analysis.index') }}" class="btn btn-secondary btn-sm">
            &larr; Kembali ke Daftar
        </a>
    </div>

    <div class="analysis-detail-body">
        {!! Str::markdown($analysis->content) !!}
    </div>

    <div class="analysis-detail-footer">
        Digenerate otomatis oleh Gemini AI pada {{ $analysis->created_at->format('d M Y, H:i:s') }}
    </div>
</div>
@endsection
