<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
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
        $monthlyRevenue = Invoice::whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->sum('total_ttc');

        // Outstanding balance
        $outstandingBalance = DB::table('invoices')
            ->leftJoin('payments', 'invoices.id', '=', 'payments.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->whereNotIn('invoices.status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->selectRaw('COALESCE(SUM(invoices.total_ttc), 0) - COALESCE(SUM(payments.amount), 0) as balance')
            ->value('balance') ?? '0.000';

        // Counts
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();
        $invoicesThisMonth = Invoice::whereBetween('invoice_date', [$startOfMonth, $endOfMonth])->count();
        $pendingInvoices = Invoice::where('status', InvoiceStatus::DRAFT->value)->count();

        // Recent invoices
        $recentInvoices = Invoice::with('customer:id,name')
            ->latest('created_at')
            ->take(10)
            ->get(['id', 'invoice_number', 'customer_id', 'total_ttc', 'status', 'invoice_date', 'created_at']);

        // Monthly revenue chart (last 12 months) — use driver-specific month formatting
        $driver = DB::connection()->getDriverName();
        switch ($driver) {
            case 'pgsql':
                $monthExpr = "TO_CHAR(invoice_date, 'YYYY-MM')";
                break;
            case 'sqlite':
                $monthExpr = "strftime('%Y-%m', invoice_date)";
                break;
            case 'sqlsrv':
                $monthExpr = "FORMAT(invoice_date, 'yyyy-MM')";
                break;
            default: // mysql / mariadb
                $monthExpr = "DATE_FORMAT(invoice_date, '%Y-%m')";
        }

        $revenueChart = Invoice::whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->where('invoice_date', '>=', $now->copy()->subMonths(11)->startOfMonth())
            ->selectRaw("{$monthExpr} as month, SUM(total_ttc) as total")
            ->groupByRaw($monthExpr)
            ->orderBy('month')
            ->pluck('total', 'month');

        // Invoice status distribution
        $statusDistribution = Invoice::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Top customers by revenue
        $topCustomers = Customer::withSum(
            ['invoices' => fn ($q) => $q->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])],
            'total_ttc'
        )
            ->orderByDesc('invoices_sum_total_ttc')
            ->take(5)
            ->get(['id', 'name', 'invoices_sum_total_ttc']);

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
                'invoices_this_month' => $invoicesThisMonth,
                'pending_invoices' => $pendingInvoices,
            ],
            'recentInvoices' => $recentInvoices,
            'revenueChart' => $revenueChart,
            'statusDistribution' => $statusDistribution,
            'topCustomers' => $topCustomers,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
