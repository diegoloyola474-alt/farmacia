<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ventas</title>
    <style>
        @page { margin: 28px 32px; size: A4; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10px; }
        .header { border-bottom: 3px solid #0d6efd; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { color: #0d6efd; font-size: 18px; font-weight: bold; }
        .muted { color: #6b7280; }
        .summary { width: 100%; margin-bottom: 16px; }
        .summary td { background: #f1f5f9; border: 1px solid #dbe3ec; padding: 8px; text-align: center; }
        .summary strong { display: block; font-size: 14px; color: #111827; }
        table.data { border-collapse: collapse; width: 100%; }
        table.data th { background: #0d6efd; color: white; padding: 7px 5px; text-align: left; }
        table.data td { border-bottom: 1px solid #e5e7eb; padding: 6px 5px; }
        .right { text-align: right !important; }
        .footer { margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 8px; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header"><div class="brand">Farmacia San Juan Bautista</div><div class="muted">Reporte de ventas · Generado el {{ now()->format('d/m/Y H:i') }}</div></div>
    <p><strong>Periodo:</strong> {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p>
    <table class="summary"><tr><td>Operaciones<strong>{{ $summary['count'] }}</strong></td><td>Ingresos<strong>S/ {{ number_format($summary['total'], 2) }}</strong></td><td>IGV<strong>S/ {{ number_format($summary['igv'], 2) }}</strong></td><td>Ticket promedio<strong>S/ {{ number_format($summary['average'], 2) }}</strong></td></tr></table>
    <table class="data"><thead><tr><th>N.º</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th class="right">Subtotal</th><th class="right">IGV</th><th class="right">Total</th></tr></thead><tbody>
        @forelse($sales as $sale)<tr><td>{{ $sale->id }}</td><td>{{ $sale->date->format('d/m/Y H:i') }}</td><td>{{ $sale->customer->name }}</td><td>{{ $sale->user->name }}</td><td class="right">S/ {{ number_format($sale->total - $sale->igv, 2) }}</td><td class="right">S/ {{ number_format($sale->igv, 2) }}</td><td class="right">S/ {{ number_format($sale->total, 2) }}</td></tr>@empty<tr><td colspan="7">No hay ventas en el periodo seleccionado.</td></tr>@endforelse
    </tbody></table>
    <div class="footer">Documento generado por el Sistema de Venta de Medicamentos · Farmacia San Juan Bautista</div>
</body>
</html>
