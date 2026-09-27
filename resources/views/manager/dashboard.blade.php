@extends('layouts.app')
@section('title', 'Dashboard Manager')
@section('page-title', 'Dashboard Manager')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .manager-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 18px 20px;
        border-left: 4px solid var(--border);
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .stat-card.total { border-left-color: var(--muted); }
    .stat-card.open { border-left-color: var(--primary); }
    .stat-card.progress { border-left-color: var(--warning); }
    .stat-card.resolved { border-left-color: var(--success); }
    
    .stat-label {
        font-size: 11.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--muted);
        margin-bottom: 6px;
    }
    .stat-value {
        font-size: 26px;
        font-weight: 700;
        color: var(--text);
    }
    .stat-card.open .stat-value { color: var(--primary); }
    .stat-card.progress .stat-value { color: var(--warning); }
    .stat-card.resolved .stat-value { color: var(--success); }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    .chart-box {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .chart-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 16px;
    }
    .chart-container {
        position: relative;
        height: 260px;
        width: 100%;
    }

    .table-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .table-clean {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }
    .table-clean th {
        background: #F9FAFB;
        padding: 10px 14px;
        font-weight: 600;
        font-size: 11.5px;
        text-transform: uppercase;
        color: var(--muted);
        border-bottom: 1px solid var(--border);
    }
    .table-clean td {
        padding: 12px 14px;
        border-bottom: 1px solid #F3F4F6;
        color: var(--text);
    }
    .table-clean tr:last-child td {
        border-bottom: none;
    }
</style>
@endpush

@section('content')
<div class="page-header" style="margin-bottom: 20px;">
    <div>
        <h1 style="font-size: 20px; font-weight: 700; color: var(--text);">Ringkasan Tiket IT Operasional</h1>
        <div class="page-header-sub">Ikhtisar metrik tiket, progres pengerjaan, dan distribusi toko</div>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="manager-stats-grid">
    <div class="stat-card total">
        <div class="stat-label">Total Tiket</div>
        <div class="stat-value">{{ $totalTickets }}</div>
    </div>
    <div class="stat-card open">
        <div class="stat-label">Tiket Open</div>
        <div class="stat-value">{{ $openTickets }}</div>
    </div>
    <div class="stat-card progress">
        <div class="stat-label">Sedang Diproses</div>
        <div class="stat-value">{{ $inProgressTickets }}</div>
    </div>
    <div class="stat-card resolved">
        <div class="stat-label">Selesai / Ditutup</div>
        <div class="stat-value">{{ $resolvedTickets + $closedTickets }}</div>
    </div>
</div>

{{-- Charts Row --}}
<div class="charts-grid">
    <div class="chart-box">
        <div class="chart-title">Berdasarkan Status</div>
        <div class="chart-container">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Berdasarkan Kategori</div>
        <div class="chart-container">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

{{-- Division / Toko Breakdown --}}
<div class="table-card">
    <div class="chart-title" style="margin-bottom: 12px;">Distribusi Tiket Berdasarkan Divisi / Toko</div>
    <div style="overflow-x: auto;">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Divisi / Toko</th>
                    <th style="text-align: right;">Jumlah Tiket</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ticketsByDivisi as $stat)
                <tr>
                    <td style="font-weight: 500;">{{ $stat->divisi_toko ?: 'Umum / Tidak Disebutkan' }}</td>
                    <td style="text-align: right;">
                        <span class="badge" style="background: #EFF6FF; color: var(--primary); font-weight: 600; padding: 3px 10px; border-radius: 12px;">
                            {{ $stat->count }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" style="text-align: center; color: var(--muted); padding: 24px;">Tidak ada data tiket.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Status Chart
        const statusData = @json($ticketsByStatus);
        const statusLabels = statusData.map(item => item.status || 'Lainnya');
        const statusCounts = statusData.map(item => item.count);

        const statusColorMap = {
            'open': '#3b82f6',
            'in progress': '#f59e0b',
            'resolved': '#10b981',
            'closed': '#059669',
            'eskalasi': '#ef4444'
        };

        const statusColors = statusLabels.map(s => statusColorMap[s.toLowerCase()] || '#9ca3af');

        if (document.getElementById('statusChart')) {
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: statusColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
        }

        // Category Chart
        const categoryData = @json($ticketsByCategory);
        const categoryLabels = categoryData.map(item => item.kategori || 'Tanpa Kategori');
        const categoryCounts = categoryData.map(item => item.count);

        if (document.getElementById('categoryChart')) {
            new Chart(document.getElementById('categoryChart'), {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Jumlah Tiket',
                        data: categoryCounts,
                        backgroundColor: '#2563EB',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
