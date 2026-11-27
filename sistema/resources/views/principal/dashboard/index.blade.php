@extends('template.administrador')

@section('title', 'DASHBOARD')
@section('name-section', 'DASHBOARD')
@section('description', 'Resumen actualizado del sistema')
@section('item-breadcrumb', 'Inicio')
@section('section-breadcrumb', 'Dashboard')
@section('module-breadcrumb', 'Principal')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h5 class="mb-1">Resumen general</h5><p class="text-muted small mb-0">Información calculada desde la base de datos.</p></div>
    <form method="GET" class="d-flex gap-2"><label for="period" class="visually-hidden">Periodo</label><select name="period" id="period" class="form-select form-select-sm" onchange="this.form.submit()"><option value="7" @selected($period === 7)>Últimos 7 días</option><option value="30" @selected($period === 30)>Últimos 30 días</option></select></form>
</div>
@if($monthSales['previousTotal'] > 0)
    @php($monthChange = (($monthSales['total'] - $monthSales['previousTotal']) / $monthSales['previousTotal']) * 100)
    <p class="small text-muted mb-3"><i class="bi {{ $monthChange >= 0 ? 'bi-arrow-up text-success' : 'bi-arrow-down text-danger' }}"></i> {{ number_format(abs($monthChange), 1) }}% respecto al mes anterior.</p>
@endif
<div class="row g-3 mb-4">
    @php($cards = [['Ventas de hoy', $dailySales['count'], 'Operaciones registradas', 'bi-cart-check', 'primary'], ['Ingresos de hoy', 'S/ '.number_format($dailySales['total'], 2), 'Ventas del día', 'bi-cash-stack', 'success'], ['Ingresos del mes', 'S/ '.number_format($monthSales['total'], 2), 'Mes actual', 'bi-graph-up-arrow', 'info']])
    @if(!$isCashier) @php($cards = array_merge($cards, [['Clientes registrados', $customersCount, 'Clientes en el sistema', 'bi-people', 'secondary'], ['Medicamentos', $medicinesCount, 'Productos registrados', 'bi-capsule', 'warning'], ['Stock bajo', $lowStockCount, 'Hasta 10 unidades', 'bi-exclamation-triangle', 'danger'], ['Agotados', $outOfStockCount, 'Sin existencias', 'bi-box-seam', 'dark']])) @endif
    @foreach($cards as [$label, $value, $description, $icon, $color])
        <div class="col-6 col-xl-3"><div class="card bg-white border-0 shadow-sm h-100"><div class="card-body d-flex justify-content-between align-items-start"><div><div class="small text-muted text-uppercase">{{ $label }}</div><div class="fs-4 fw-bold mt-2">{{ $value }}</div><div class="small text-muted mt-1">{{ $description }}</div></div><span class="rounded-3 bg-{{ $color }}-subtle text-{{ $color }} p-2"><i class="bi {{ $icon }} fs-5"></i></span></div></div></div>
    @endforeach
</div>
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-8"><div class="card bg-white border-0 shadow-sm h-100"><div class="card-header bg-white fw-semibold">Ingresos diarios</div><div class="card-body"><div style="height:280px"><canvas id="dailySalesChart"></canvas></div></div></div></div>
    <div class="col-12 col-xl-4"><div class="card bg-white border-0 shadow-sm h-100"><div class="card-header bg-white fw-semibold">Ventas por categoría</div><div class="card-body"><div style="height:280px"><canvas id="categorySalesChart"></canvas></div></div></div></div>
    <div class="col-12 col-xl-6"><div class="card bg-white border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Ingresos mensuales</div><div class="card-body"><div style="height:280px"><canvas id="monthlySalesChart"></canvas></div></div></div></div>
    <div class="col-12 col-xl-6"><div class="card bg-white border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Medicamentos más vendidos</div><div class="card-body"><div style="height:280px"><canvas id="topMedicinesChart"></canvas></div></div></div></div>
</div>
<div class="row g-3"><div class="col-12 col-xl-8"><div class="card bg-white border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Últimas ventas</div><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>N.º</th><th>Cliente</th><th>Vendedor</th><th>Fecha</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
    @forelse($recentSales as $sale)<tr><td>{{ $sale->id }}</td><td>{{ $sale->customer->name }}</td><td>{{ $sale->user->name }}</td><td>{{ $sale->date->format('d/m/Y H:i') }}</td><td class="text-end">S/ {{ number_format($sale->total, 2) }}</td><td><a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-secondary">Ver</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No hay ventas registradas.</td></tr>@endforelse
</tbody></table></div></div></div>
@if(!$isCashier)<div class="col-12 col-xl-4"><div class="card bg-white border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Alertas de inventario</div><div class="list-group list-group-flush">@forelse($lowStock as $medicine)<a href="{{ route('medicines.edit', $medicine) }}" class="list-group-item list-group-item-action d-flex justify-content-between"><span>{{ $medicine->name }}<small class="d-block text-muted">{{ $medicine->category->name }}</small></span><span class="badge {{ $medicine->stock === 0 ? 'bg-danger' : 'bg-warning text-dark' }} align-self-center">{{ $medicine->stock }}</span></a>@empty<div class="p-3 text-muted small">No hay alertas de stock.</div>@endforelse</div></div></div>@endif
</div>
@endsection

@push('vendor-scripts')<script src="{{ asset('js/chart.umd.min.js') }}"></script>@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;
    const colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d'];
    const common = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
    const data = { daily: @json($daily), monthly: @json($monthly->map(fn($row) => ['label' => $row->label, 'total' => (float) $row->total])->values()), medicines: @json($topMedicines->map(fn($row) => ['label' => $row->label, 'units' => (int) $row->units])->values()), categories: @json($byCategory->map(fn($row) => ['label' => $row->label, 'total' => (float) $row->total])->values()) };
    const currency = (value) => `S/ ${Number(value).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
    const tooltip = { callbacks: { label: (context) => `${context.dataset.label}: ${currency(context.raw)}` } };
    const dailyContext = document.getElementById('dailySalesChart').getContext('2d');
    const dailyGradient = dailyContext.createLinearGradient(0, 0, 0, 280);
    dailyGradient.addColorStop(0, 'rgba(13, 110, 253, .30)');
    dailyGradient.addColorStop(1, 'rgba(13, 110, 253, .02)');
    new Chart(dailyContext, { type: 'line', data: { labels: data.daily.map(x => x.label), datasets: [{ label: 'Ingresos', data: data.daily.map(x => x.total), borderColor: colors[0], backgroundColor: dailyGradient, fill: true, tension: 0.4, borderWidth: 3, pointRadius: 5, pointHoverRadius: 7, pointBackgroundColor: '#fff', pointBorderWidth: 3 }] }, options: { ...common, plugins: { ...common.plugins, tooltip } } });
    new Chart(document.getElementById('monthlySalesChart'), { type: 'bar', data: { labels: data.monthly.map(x => x.label), datasets: [{ label: 'Ingresos', data: data.monthly.map(x => x.total), backgroundColor: colors[1], borderRadius: 8, borderSkipped: false }] }, options: { ...common, plugins: { ...common.plugins, tooltip } } });
    new Chart(document.getElementById('topMedicinesChart'), { type: 'bar', data: { labels: data.medicines.map(x => x.label), datasets: [{ label: 'Unidades', data: data.medicines.map(x => x.units), backgroundColor: colors[2], borderRadius: 8, borderSkipped: false }] }, options: { ...common, indexAxis: 'y', plugins: { ...common.plugins, tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${context.raw} unidades` } } } } });
    new Chart(document.getElementById('categorySalesChart'), { type: 'doughnut', data: { labels: data.categories.map(x => x.label), datasets: [{ label: 'Ventas', data: data.categories.map(x => x.total), backgroundColor: ['#0d6efd', '#d63384', '#6f42c1', '#fd7e14', '#20c997', '#6610f2', '#198754'], borderColor: '#fff', borderWidth: 3, hoverOffset: 8 }] }, options: { ...common, cutout: '64%', plugins: { ...common.plugins, tooltip } } });
});
</script>
@endpush
