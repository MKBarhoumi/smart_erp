<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OldInvoiceStatus;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\OldInvoice;
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
        $startOfYear = $now->copy()->startOfYear();

        // Monthly revenue from OldInvoices
        $monthlyRevenueOld = OldInvoice::whereBetween('oldinvoice_date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->sum('total_ttc');

        // Year-to-date revenue from OldInvoices
        $yearlyRevenueOld = OldInvoice::whereBetween('oldinvoice_date', [$startOfYear, $endOfMonth])
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->sum('total_ttc');

        // Also include new Invoice model revenue (based on created_at since dates is JSON)
        $monthlyRevenueNew = Invoice::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->get()
            ->sum(function ($invoice) {
                $amounts = $invoice->invoice_amounts ?? [];
                foreach ($amounts as $amount) {
                    $code = $amount['amount_type_code'] ?? '';
                    if ($code === 'I-180') { // Total TTC
                        return (float) ($amount['amount'] ?? 0);
                    }
                }
                return 0;
            });

        $yearlyRevenueNew = Invoice::whereBetween('created_at', [$startOfYear, $endOfMonth])
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->get()
            ->sum(function ($invoice) {
                $amounts = $invoice->invoice_amounts ?? [];
                foreach ($amounts as $amount) {
                    $code = $amount['amount_type_code'] ?? '';
                    if ($code === 'I-180') { // Total TTC
                        return (float) ($amount['amount'] ?? 0);
                    }
                }
                return 0;
            });

        $monthlyRevenue = $monthlyRevenueOld + $monthlyRevenueNew;
        $yearlyRevenue = $yearlyRevenueOld + $yearlyRevenueNew;

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
        $invoicesThisMonth = Invoice::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $totalInvoicesThisMonth = $oldinvoicesThisMonth + $invoicesThisMonth;
        $pendingOldInvoices = OldInvoice::where('status', OldInvoiceStatus::DRAFT->value)->count();
        $pendingNewInvoices = Invoice::where('status', InvoiceStatus::DRAFT->value)->count()
            + Invoice::where('status', InvoiceStatus::PENDING_VALIDATION->value)->count();
        $pendingInvoices = $pendingOldInvoices + $pendingNewInvoices;

        // Recent invoices (combine both models)
        $recentOldInvoices = OldInvoice::with('customer:id,name')
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'oldinvoice_number', 'customer_id', 'total_ttc', 'status', 'oldinvoice_date', 'created_at'])
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'oldinvoice_number' => $inv->oldinvoice_number,
                'customer' => $inv->customer,
                'total_ttc' => $inv->total_ttc,
                'status' => $inv->status,
                'oldinvoice_date' => $inv->oldinvoice_date,
                'type' => 'old',
            ]);

        $recentNewInvoices = Invoice::with('creator:id,name')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($inv) {
                $totalTTC = '0.000';
                foreach ($inv->invoice_amounts ?? [] as $amount) {
                    if (($amount['amount_type_code'] ?? '') === 'I-180') {
                        $totalTTC = $amount['amount'] ?? '0.000';
                        break;
                    }
                }
                return [
                    'id' => $inv->id,
                    'oldinvoice_number' => $inv->document_identifier,
                    'customer' => $inv->creator ? ['id' => $inv->creator->id, 'name' => $inv->receiver_identifier] : null,
                    'total_ttc' => $totalTTC,
                    'status' => $inv->status,
                    'oldinvoice_date' => $inv->created_at->format('Y-m-d'),
                    'type' => 'new',
                ];
            });

        $recentInvoices = $recentOldInvoices->concat($recentNewInvoices)
            ->sortByDesc(fn ($inv) => $inv['oldinvoice_date'])
            ->take(10)
            ->values();

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
                'yearly_revenue' => number_format((float) $yearlyRevenue, 3, '.', ''),
                'outstanding_balance' => number_format((float) $outstandingBalance, 3, '.', ''),
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
                'oldinvoices_this_month' => $totalInvoicesThisMonth,
                'pending_oldinvoices' => $pendingInvoices,
            ],
            'recentOldInvoices' => $recentInvoices,
            'revenueChart' => $revenueChart,
            'statusDistribution' => $statusDistribution,
            'topCustomers' => $topCustomers,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
