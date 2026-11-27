<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DetailSale;
use App\Models\Medicine;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isCashier = $user->hasRole('Cajero');
        $period = (int) $request->get('period', 7);
        $period = in_array($period, [7, 30], true) ? $period : 7;
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $dailyStart = $now->copy()->subDays($period - 1)->startOfDay();
        $dailyEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();

        // Limita todas las consultas de ventas del cajero a sus propias operaciones.
        $saleScope = fn ($query) => $isCashier ? $query->where('sales.user_id', $user->id) : $query;
        $dailySales = $saleScope(Sale::query())->whereBetween('created_at', [$todayStart, $now])->get(['total']);
        $monthSales = $saleScope(Sale::query())->whereBetween('created_at', [$monthStart, $now])->get(['total']);
        $previousMonthSales = $saleScope(Sale::query())
            ->whereBetween('created_at', [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()])
            ->get(['total']);

        // Construye siempre las siete fechas para conservar los días sin ventas como ceros.
        $dailyDates = collect(range($period - 1, 0))->map(fn (int $daysAgo) => $now->copy()->subDays($daysAgo));
        $dailyRows = $saleScope(Sale::query())
            ->whereDate('created_at', '>=', $dailyStart->toDateString())
            ->whereDate('created_at', '<=', $dailyEnd->toDateString())
            ->selectRaw('DATE(created_at) as label, SUM(total) as total')
            ->groupByRaw('DATE(created_at)')->orderBy('label')->get();
        $dailyTotals = $dailyRows->keyBy('label');
        $daily = $dailyDates->map(function (Carbon $date) use ($dailyTotals): array {
            $row = $dailyTotals->get($date->toDateString());

            return ['label' => $date->format('d/m'), 'total' => $row ? (float) $row->total : 0.0];
        });

        $sales = $saleScope(Sale::query())->whereDate('created_at', '>=', $dailyStart->toDateString());
        $monthly = $saleScope(Sale::query())
            ->whereDate('created_at', '>=', $now->copy()->subMonths(11)->startOfMonth()->toDateString())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as label, SUM(total) as total")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")->orderBy('label')->get();
        $topMedicines = $saleScope(DetailSale::query()->join('sales', 'sales.id', '=', 'detail_sales.sale_id'))
            ->join('medicines', 'medicines.id', '=', 'detail_sales.medicine_id')
            ->whereDate('sales.created_at', '>=', $dailyStart->toDateString())
            ->selectRaw('medicines.name as label, SUM(detail_sales.amount) as units')
            ->groupBy('medicines.id', 'medicines.name')->orderByDesc('units')->limit(10)->get();
        $byCategory = $saleScope(DetailSale::query()->join('sales', 'sales.id', '=', 'detail_sales.sale_id'))
            ->join('medicines', 'medicines.id', '=', 'detail_sales.medicine_id')
            ->join('categories', 'categories.id', '=', 'medicines.category_id')
            ->whereDate('sales.created_at', '>=', $dailyStart->toDateString())
            ->selectRaw('categories.name as label, SUM(detail_sales.subtotal) as total')
            ->groupBy('categories.id', 'categories.name')->orderByDesc('total')->get();

        $recentSales = $saleScope(Sale::with(['customer', 'user']))->latest('date')->limit(8)->get();
        $data = [
            'period' => $period,
            'isCashier' => $isCashier,
            'dailySales' => ['total' => $dailySales->sum('total'), 'count' => $dailySales->count()],
            'monthSales' => ['total' => $monthSales->sum('total'), 'previousTotal' => $previousMonthSales->sum('total')],
            'customersCount' => Customer::count(),
            'medicinesCount' => Medicine::count(),
            'lowStockCount' => Medicine::whereBetween('stock', [1, 10])->count(),
            'outOfStockCount' => Medicine::where('stock', 0)->count(),
            'daily' => $daily,
            'monthly' => $monthly,
            'topMedicines' => $topMedicines,
            'byCategory' => $byCategory,
            'recentSales' => $recentSales,
            'lowStock' => Medicine::with('category')->where('stock', '<=', 10)->orderBy('stock')->limit(8)->get(),
        ];

        return view('principal.dashboard.index', $data);
    }
}
