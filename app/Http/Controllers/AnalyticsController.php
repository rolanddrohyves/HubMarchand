<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $store = $request->user()->currentStore();
        $period = $request->validate(['period' => ['nullable', Rule::in(['day', 'week', 'month', 'year'])]])['period'] ?? 'month';
        [$from, $to] = $this->period($period);
        $orders = $store->salesOrders()->where('status', 'paid')->whereBetween('ordered_at', [$from, $to]);
        $revenue = (int) (clone $orders)->sum('total_amount');
        $cost = (int) (clone $orders)->sum('cost_amount');
        $expenses = (int) $store->expenses()->whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])->sum('amount');
        $volume = (int) (clone $orders)->count();
        $grossMargin = $revenue - $cost;
        $netMargin = $grossMargin - $expenses;
        $averageBasket = $volume > 0 ? (int) round($revenue / $volume) : 0;
        $marginRate = $revenue > 0 ? $grossMargin / $revenue : 0;
        $breakEven = $marginRate > 0 ? (int) ceil($expenses / $marginRate) : null;

        $salesByDay = (clone $orders)
            ->selectRaw('DATE(ordered_at) as day, SUM(total_amount) as amount')
            ->groupBy('day')->orderBy('day')->get();
        $channels = (clone $orders)->selectRaw('source, COUNT(*) as volume, SUM(total_amount) as amount')
            ->groupBy('source')->orderByDesc('amount')->get();
        $payments = (clone $orders)->selectRaw('payment_method, COUNT(*) as volume, SUM(total_amount) as amount')
            ->groupBy('payment_method')->orderByDesc('amount')->get();
        $expensesList = $store->expenses()->whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])->latest('spent_at')->get();
        $topProducts = $this->productPerformance($store->id, $from, $to, 'desc');
        $flopProducts = $this->productPerformance($store->id, $from, $to, 'asc');
        $lossValue = (int) StockMovement::query()->whereHas('product', fn (Builder $query) => $query->where('store_id', $store->id))
            ->where('stock_movements.type', 'adjustment')->where('stock_movements.quantity', '<', 0)->whereBetween('stock_movements.created_at', [$from, $to])
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->sum(DB::raw('ABS(stock_movements.quantity) * products.purchase_price'));

        return view('analytics.index', compact('store', 'period', 'from', 'to', 'revenue', 'grossMargin', 'netMargin', 'averageBasket', 'volume', 'expenses', 'breakEven', 'salesByDay', 'channels', 'payments', 'expensesList', 'topProducts', 'flopProducts', 'lossValue'));
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $store = $request->user()->currentStore();
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'category' => ['nullable', 'string', 'max:100'],
            'spent_at' => ['required', 'date'],
        ]);
        $store->expenses()->create($validated);

        return back()->with('status', 'Dépense enregistrée.');
    }

    public function destroyExpense(Request $request, Expense $expense): RedirectResponse
    {
        abort_unless($expense->store_id === $request->user()->currentStore()->id, 404);
        $expense->delete();

        return back()->with('status', 'Dépense supprimée.');
    }

    private function period(string $period): array
    {
        $to = now()->endOfDay();
        $from = match ($period) {
            'day' => today(),
            'week' => today()->subDays(6),
            'year' => today()->startOfYear(),
            default => today()->startOfMonth(),
        };

        return [$from instanceof Carbon ? $from : Carbon::parse($from), $to];
    }

    private function productPerformance(int $storeId, Carbon $from, Carbon $to, string $direction)
    {
        return SalesOrderItem::query()
            ->selectRaw('products.name, products.sku, SUM(sales_order_items.quantity) as units, SUM(sales_order_items.quantity * sales_order_items.unit_price) as revenue, SUM(sales_order_items.quantity * (sales_order_items.unit_price - sales_order_items.unit_cost)) as margin')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->where('sales_orders.store_id', $storeId)->where('sales_orders.status', 'paid')
            ->whereBetween('sales_orders.ordered_at', [$from, $to])
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('units', $direction)->limit(5)->get();
    }
}
