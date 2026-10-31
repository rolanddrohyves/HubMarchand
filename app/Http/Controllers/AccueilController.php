<?php

namespace App\Http\Controllers;

use App\Models\B2bDocument;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccueilController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->isSuperAdmin()) {
            return redirect()->route('super-admin.index');
        }

        $store = $request->user()->currentStore();
        $today = today();
        $paidOrders = fn (Builder $query) => $query->where('status', 'paid');
        $todayRevenue = $store->salesOrders()->where($paidOrders)->whereDate('ordered_at', $today)->sum('total_amount');
        $todayOrders = $store->salesOrders()->where($paidOrders)->whereDate('ordered_at', $today)->count();
        $todayCost = $store->salesOrders()->where($paidOrders)->whereDate('ordered_at', $today)->sum('cost_amount');
        $pendingOrders = $store->salesOrders()
            ->where(function (Builder $query): void {
                $query->where('status', 'pending')
                    ->orWhereIn('fulfillment_status', ['pending', 'preparing', 'ready']);
            })->count();
        $todayOrders = $store->salesOrders()->where($paidOrders)->whereDate('ordered_at', $today)->count();
        $averageBasket = $todayOrders > 0 ? (int) round($todayRevenue / $todayOrders) : 0;
        $yesterdayRevenue = $store->salesOrders()->where($paidOrders)
            ->whereBetween('ordered_at', [now()->subDay()->startOfDay(), now()->subDay()->setTimeFrom(now())])
            ->sum('total_amount');
        $revenueChange = $yesterdayRevenue > 0 ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100) : null;
        $todayVisitors = $store->shopVisits()->whereDate('visited_at', $today)->distinct('visitor_key')->count('visitor_key');
        $lowStock = $store->products()->whereColumn('stock_quantity', '<=', 'minimum_stock')->count();
        $lowStockProducts = $store->products()
            ->whereColumn('stock_quantity', '<=', 'minimum_stock')
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();
        $todayExpenses = $store->expenses()->whereDate('spent_at', $today)->sum('amount');
        $todayProfit = $todayRevenue - $todayCost - $todayExpenses;

        $chartStart = today()->subDays(6);
        $salesByDay = $store->salesOrders()
            ->where($paidOrders)
            ->whereDate('ordered_at', '>=', $chartStart)
            ->selectRaw('DATE(ordered_at) as day, SUM(total_amount) as revenue, SUM(CAST(total_amount AS SIGNED) - CAST(cost_amount AS SIGNED)) as margin')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');
        $expenseByDay = $store->expenses()
            ->whereDate('spent_at', '>=', $chartStart)
            ->selectRaw('spent_at as day, SUM(amount) as expenses')
            ->groupBy('spent_at')
            ->orderBy('spent_at')
            ->get()
            ->keyBy('day');
        $chart = collect(range(0, 6))->map(function (int $offset) use ($chartStart, $salesByDay, $expenseByDay): array {
            $day = $chartStart->copy()->addDays($offset)->toDateString();
            $sales = $salesByDay->get($day);
            $expenses = $expenseByDay->get($day);

            return [
                'label' => $chartStart->copy()->addDays($offset)->format('d/m'),
                'revenue' => (int) ($sales?->revenue ?? 0),
                'margin' => (int) ($sales?->margin ?? 0),
                'expenses' => (int) ($expenses?->expenses ?? 0),
            ];
        });
        $recentOrders = $store->salesOrders()->latest('ordered_at')->limit(5)->get();
        $recentExpenses = $store->expenses()->latest('spent_at')->limit(5)->get();
        $deliveryAlerts = $store->salesOrders()
            ->whereIn('fulfillment_status', ['failed', 'returned'])
            ->latest('updated_at')->limit(5)->get();
        $overdueInvoices = B2bDocument::query()
            ->where('store_id', $store->id)
            ->whereIn('type', ['invoice', 'proforma'])
            ->whereIn('status', ['sent', 'partially_paid'])
            ->whereDate('due_at', '<', $today)
            ->with('customer')
            ->latest('due_at')->limit(5)->get();
        $hourlySales = $store->salesOrders()
            ->where($paidOrders)
            ->whereDate('ordered_at', $today)
            ->get(['ordered_at', 'total_amount'])
            ->groupBy(fn (SalesOrder $order): int => $order->ordered_at->hour)
            ->map(fn ($orders): int => (int) $orders->sum('total_amount'));
        $flashChart = collect(range(0, now()->hour))->map(fn (int $hour): array => [
            'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).'h',
            'amount' => $hourlySales->get($hour, 0),
        ]);
        $enabledModules = $store->enabled_modules;

        return view('home.accueil', compact(
            'store',
            'todayRevenue',
            'todayOrders',
            'pendingOrders',
            'averageBasket',
            'revenueChange',
            'todayVisitors',
            'lowStock',
            'lowStockProducts',
            'todayProfit',
            'todayExpenses',
            'chart',
            'recentOrders',
            'recentExpenses',
            'deliveryAlerts',
            'overdueInvoices',
            'flashChart',
            'enabledModules',
        ));
    }
}
