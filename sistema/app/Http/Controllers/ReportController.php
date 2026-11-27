<?php

namespace App\Http\Controllers;

use App\Exports\VentasExport;
use App\Models\DetailSale;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /** Se considera stock bajo cuando quedan estas unidades o menos. */
    private const LOW_STOCK = 10;

    public function index(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'user_id' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $query = Sale::with(['customer', 'user'])->whereBetween('date', [$from, $to]);
        $this->applySaleFilters($query, $request);
        $sales = $query
            ->orderBy('date')
            ->get();

        $summary = [
            'count' => $sales->count(),
            'total' => $sales->sum('total'),
            'igv' => $sales->sum('igv'),
            'average' => $sales->count() ? $sales->sum('total') / $sales->count() : 0,
        ];

        $topMedicinesQuery = DetailSale::query()
            ->join('sales', 'sales.id', '=', 'detail_sales.sale_id')
            ->join('medicines', 'medicines.id', '=', 'detail_sales.medicine_id')
            ->whereBetween('sales.date', [$from, $to]);
        $this->applySaleFilters($topMedicinesQuery, $request, 'sales.');
        $topMedicines = $topMedicinesQuery
            ->groupBy('medicines.id', 'medicines.name')
            ->selectRaw('medicines.name, SUM(detail_sales.amount) as units, SUM(detail_sales.subtotal) as revenue')
            ->orderByDesc('units')
            ->limit(10)
            ->get();

        $bySellerQuery = Sale::query()
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->whereBetween('sales.date', [$from, $to]);
        $this->applySaleFilters($bySellerQuery, $request, 'sales.');
        $bySeller = $bySellerQuery
            ->groupBy('users.id', 'users.name')
            ->selectRaw('users.name, COUNT(*) as sales_count, SUM(sales.total) as total, AVG(sales.total) as average')
            ->orderByDesc('total')
            ->get();

        $lowStock = Medicine::with('category')
            ->where('stock', '<=', self::LOW_STOCK)
            ->orderBy('stock')
            ->get();
        $medicines = Medicine::with('category')->orderBy('name')->get();

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'summary' => $summary,
            'topMedicines' => $topMedicines,
            'bySeller' => $bySeller,
            'lowStock' => $lowStock,
            'medicines' => $medicines,
            'lowStockLimit' => self::LOW_STOCK,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'selectedUser' => $request->input('user_id'),
            'selectedCustomer' => $request->input('customer_id'),
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from',
            'user_id' => 'nullable|exists:users,id', 'customer_id' => 'nullable|exists:customers,id',
        ]);
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        $query = Sale::with(['customer', 'user'])->whereBetween('date', [$from, $to]);
        $this->applySaleFilters($query, $request);
        $sales = $query->orderBy('date')->get();

        // Genera un CSV UTF-8 compatible con Excel sin instalar dependencias.
        return response()->streamDownload(function () use ($sales, $from, $to) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Reporte de ventas', "Periodo: {$from->format('d/m/Y')} - {$to->format('d/m/Y')}"]);
            fputcsv($handle, ['N.º venta', 'Fecha', 'Cliente', 'Vendedor', 'Subtotal', 'IGV', 'Total']);
            foreach ($sales as $sale) {
                fputcsv($handle, [$sale->id, $sale->date->format('d/m/Y H:i'), $sale->customer->name, $sale->user->name,
                    number_format($sale->total - $sale->igv, 2, '.', ''), number_format($sale->igv, 2, '.', ''), number_format($sale->total, 2, '.', '')]);
            }
            fclose($handle);
        }, 'reporte-ventas-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request)
    {
        [$from, $to, $sales, $summary] = $this->filteredSalesData($request);
        $pdf = Pdf::loadView('reports.pdf', compact('from', 'to', 'sales', 'summary'))->setPaper('a4', 'portrait');

        return $pdf->download('reporte-ventas-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf');
    }

    public function excel(Request $request)
    {
        [$from, $to, $sales] = $this->filteredSalesData($request);

        return Excel::download(new VentasExport($sales, $from->format('d/m/Y'), $to->format('d/m/Y')), 'reporte-ventas-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx');
    }

    private function filteredSalesData(Request $request): array
    {
        $request->validate([
            'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from',
            'user_id' => 'nullable|exists:users,id', 'customer_id' => 'nullable|exists:customers,id',
        ]);
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        $query = Sale::with(['customer', 'user'])->whereBetween('date', [$from, $to]);
        $this->applySaleFilters($query, $request);
        $sales = $query->orderBy('date')->get();
        $summary = [
            'count' => $sales->count(),
            'total' => $sales->sum('total'),
            'igv' => $sales->sum('igv'),
            'average' => $sales->count() ? $sales->sum('total') / $sales->count() : 0,
        ];

        return [$from, $to, $sales, $summary];
    }

    private function applySaleFilters($query, Request $request, string $prefix = ''): void
    {
        if ($request->filled('user_id')) $query->where($prefix.'user_id', $request->input('user_id'));
        if ($request->filled('customer_id')) $query->where($prefix.'customer_id', $request->input('customer_id'));
    }
}
