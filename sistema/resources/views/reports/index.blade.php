@extends('template.administrador')

@section('title', 'REPORTES')
@section('name-section', 'REPORTES')

@section('description', 'Reporte de ventas por periodo')
@section('item-breadcrumb', 'Inicio')
@section('section-breadcrumb', 'Reportes')
@section('module-breadcrumb', 'Principal')

@push('styles')
<style>
@media print {
    #sidebar, .app-header, .no-print { display: none !important; }
    .app-content { overflow: visible !important; }
}
</style>
@endpush

@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary mb-4 no-print">
    <div class="card-body">
        <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="from" class="form-label">Desde</label>
                <input type="date" name="from" id="from" class="form-control @error('from') is-invalid @enderror" value="{{ $from->toDateString() }}">
                @error('from')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-3">
                <label for="to" class="form-label">Hasta</label>
                <input type="date" name="to" id="to" class="form-control @error('to') is-invalid @enderror" value="{{ $to->toDateString() }}">
                @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-3">
                <label for="user_id" class="form-label">Vendedor</label>
                <select name="user_id" id="user_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($users as $user)<option value="{{ $user->id }}" @selected((string) $selectedUser === (string) $user->id)>{{ $user->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="customer_id" class="form-label">Cliente</label>
                <select name="customer_id" id="customer_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string) $selectedCustomer === (string) $customer->id)>{{ $customer->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-primary text-white w-100">Generar reporte</button>
            </div>
            <div class="col-12 col-md-3">
                <a href="{{ route('reports.pdf', request()->query()) }}" class="btn btn-outline-danger w-100"><i class="bi bi-printer me-1"></i>Imprimir PDF</a>
            </div>
            <div class="col-12 col-md-3">
                <a href="{{ route('reports.excel', request()->query()) }}" class="btn btn-outline-success w-100"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a>
            </div>
        </form>
    </div>
</div>

<p class="text-muted small mb-3">Periodo: {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p>

<div class="row mb-4">
    <div class="col-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted text-uppercase">Ventas</div>
            <div class="fs-4 fw-bold">{{ $summary['count'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted text-uppercase">Total vendido</div>
            <div class="fs-4 fw-bold">S/ {{ number_format($summary['total'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted text-uppercase">IGV</div>
            <div class="fs-4 fw-bold">S/ {{ number_format($summary['igv'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted text-uppercase">Ticket promedio</div>
            <div class="fs-4 fw-bold">S/ {{ number_format($summary['average'], 2) }}</div>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-6 mb-4">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Medicamentos más vendidos</div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm table-hover w-100">
                    <thead><tr>
                        <th class="small text-uppercase">Medicamento</th>
                        <th class="small text-uppercase text-center">Unidades</th>
                        <th class="small text-uppercase text-end">Ingresos</th>
                    </tr></thead>
                    <tbody>
                        @forelse($topMedicines as $row)
                            <tr>
                                <td>{{ $row->name }}</td>
                                <td class="text-center">{{ $row->units }}</td>
                                <td class="text-end">S/ {{ number_format($row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">Sin ventas en el periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6 mb-4">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Ventas por vendedor</div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm table-hover w-100">
                    <thead><tr>
                        <th class="small text-uppercase">Vendedor</th>
                        <th class="small text-uppercase text-center">Ventas</th>
                        <th class="small text-uppercase text-end">Total</th>
                        <th class="small text-uppercase text-end">Promedio</th>
                    </tr></thead>
                    <tbody>
                        @forelse($bySeller as $row)
                            <tr>
                                <td>{{ $row->name }}</td>
                                <td class="text-center">{{ $row->sales_count }}</td>
                                <td class="text-end">S/ {{ number_format($row->total, 2) }}</td>
                                <td class="text-end">S/ {{ number_format($row->average, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Sin ventas en el periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card bg-white border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Ventas del periodo</div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-sm table-hover w-100">
            <thead><tr>
                <th class="small text-uppercase text-center">Nº</th>
                <th class="small text-uppercase text-center">Fecha</th>
                <th class="small text-uppercase">Cliente</th>
                <th class="small text-uppercase">Vendedor</th>
                <th class="small text-uppercase text-end">IGV</th>
                        <th class="small text-uppercase text-end">Subtotal</th>
                <th class="small text-uppercase text-end">Total</th>
            </tr></thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td class="text-center">{{ $sale->id }}</td>
                        <td class="text-center">{{ $sale->date->format('d/m/Y H:i') }}</td>
                        <td>{{ $sale->customer->name }}</td>
                        <td>{{ $sale->user->name }}</td>
                        <td class="text-end">S/ {{ number_format($sale->igv, 2) }}</td>
                        <td class="text-end">S/ {{ number_format($sale->total - $sale->igv, 2) }}</td>
                        <td class="text-end">S/ {{ number_format($sale->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Sin ventas en el periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="card bg-white border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Stock bajo (≤ {{ $lowStockLimit }} unidades)</div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-sm table-hover w-100">
            <thead><tr>
                <th class="small text-uppercase">Medicamento</th>
                <th class="small text-uppercase">Categoría</th>
                <th class="small text-uppercase text-center">Stock</th>
                <th class="small text-uppercase text-end">Precio venta</th>
                <th class="small text-uppercase text-center">Estado</th>
            </tr></thead>
            <tbody>
                @forelse($medicines as $medicine)
                    <tr>
                        <td>{{ $medicine->name }}</td>
                        <td>{{ $medicine->category->name }}</td>
                        <td class="text-center {{ $medicine->stock == 0 ? 'text-danger fw-bold' : '' }}">{{ $medicine->stock }}</td>
                        <td class="text-end">S/ {{ number_format($medicine->price, 2) }}</td>
                        <td class="text-center"><span class="badge {{ $medicine->stock === 0 ? 'bg-danger' : ($medicine->stock <= $lowStockLimit ? 'bg-warning text-dark' : 'bg-success') }}">{{ $medicine->stock === 0 ? 'Agotado' : ($medicine->stock <= $lowStockLimit ? 'Stock bajo' : 'Disponible') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No hay medicamentos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
