@extends('layouts.app')
@section('title', 'Home')
@section('page-title', 'Home')

@section('content')
<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: calc(100vh - 80px); text-align: center;">
    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 20px;"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    
    <h1 style="font-size: 28px; font-weight: 700; color: var(--text); margin-bottom: 8px;">Selamat datang, {{ Auth::user()->name }} 👋</h1>
    <div style="color: var(--muted); font-size: 15px; margin-bottom: 32px;">Ringkasan tiket IT operasional toko</div>
    
    <a href="{{ route('tickets.create') }}" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(37, 99, 235, 0.3)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(37, 99, 235, 0.25)';">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Catat Tiket
    </a>
</div>

@endsection
