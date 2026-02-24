<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OldInvoiceStatus;
use App\Models\Customer;
use App\Models\OldInvoice;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Monthly revenue
        $monthlyRevenue = OldInvoice::whereBetween('oldinvoice_date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->sum('total_ttc');

        // Outstanding balance
        $outstandingBalance = DB::table('oldinvoices')
            ->leftJoin('payments', 'oldinvoices.id', '=', 'payments.oldinvoice_id')
            ->whereNull('oldinvoices.deleted_at')
            ->whereNotIn('oldinvoices.status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw('COALESCE(SUM(oldinvoices.total_ttc), 0) - COALESCE(SUM(payments.amount), 0) as balance')
            ->value('balance') ?? '0.000';

        // Counts
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();
        $oldinvoicesThisMonth = OldInvoice::whereBetween('oldinvoice_date', [$startOfMonth, $endOfMonth])->count();
        $pendingOldInvoices = OldInvoice::where('status', OldInvoiceStatus::DRAFT->value)->count();

        // Recent oldinvoices
        $recentOldInvoices = OldInvoice::with('customer:id,name')
            ->latest('created_at')
            ->take(10)
            ->get(['id', 'oldinvoice_number', 'customer_id', 'total_ttc', 'status', 'oldinvoice_date', 'created_at']);

        // Monthly revenue chart (last 12 months) — use driver-specific month formatting
        $driver = DB::connection()->getDriverName();
        switch ($driver) {
            case 'pgsql':
                $monthExpr = "TO_CHAR(oldinvoice_date, 'YYYY-MM')";
                break;
            case 'sqlite':
                $monthExpr = "strftime('%Y-%m', oldinvoice_date)";
                break;
            case 'sqlsrv':
                $monthExpr = "FORMAT(oldinvoice_date, 'yyyy-MM')";
                break;
            default: // mysql / mariadb
                $monthExpr = "DATE_FORMAT(oldinvoice_date, '%Y-%m')";
        }

        $revenueChart = OldInvoice::whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->where('oldinvoice_date', '>=', $now->copy()->subMonths(11)->startOfMonth())
            ->selectRaw("{$monthExpr} as month, SUM(total_ttc) as total")
            ->groupByRaw($monthExpr)
            ->orderBy('month')
            ->pluck('total', 'month');

        // OldInvoice status distribution
        $statusDistribution = OldInvoice::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Top customers by revenue
        $topCustomers = Customer::withSum(
            ['oldinvoices' => fn ($q) => $q->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])],
            'total_ttc'
        )
            ->orderByDesc('oldinvoices_sum_total_ttc')
            ->take(5)
            ->get(['id', 'name', 'oldinvoices_sum_total_ttc']);

        // Low stock alerts
        $lowStockProducts = Product::where('track_inventory', true)
            ->whereColumn('current_stock', '<=', 'min_stock_alert')
            ->where('min_stock_alert', '>', 0)
            ->take(5)
            ->get(['id', 'name', 'code', 'current_stock', 'min_stock_alert']);

        return Inertia::render('Dashboard', [
            'stats' => [
                'monthly_revenue' => number_format((float) $monthlyRevenue, 3, '.', ''),
                'outstanding_balance' => number_format((float) $outstandingBalance, 3, '.', ''),
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
                'oldinvoices_this_month' => $oldinvoicesThisMonth,
                'pending_oldinvoices' => $pendingOldInvoices,
            ],
            'recentOldInvoices' => $recentOldInvoices,
            'revenueChart' => $revenueChart,
            'statusDistribution' => $statusDistribution,
            'topCustomers' => $topCustomers,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
