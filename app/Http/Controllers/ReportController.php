<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reports/Index');
    }

    public function revenue(Request $request): Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));

        $monthlyRevenue = Invoice::whereYear('invoice_date', $year)
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->selectRaw("EXTRACT(MONTH FROM invoice_date) as month, SUM(total_ht) as total_ht, SUM(total_tva) as total_tva, SUM(total_ttc) as total_ttc, COUNT(*) as invoice_count")
            ->groupByRaw("EXTRACT(MONTH FROM invoice_date)")
            ->orderBy('month')
            ->get();

        $yearlyTotal = Invoice::whereYear('invoice_date', $year)
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->selectRaw("SUM(total_ht) as total_ht, SUM(total_tva) as total_tva, SUM(total_ttc) as total_ttc, SUM(timbre_fiscal) as total_timbre, COUNT(*) as invoice_count")
            ->first();

        return Inertia::render('Reports/Revenue', [
            'year' => $year,
            'monthlyRevenue' => $monthlyRevenue,
            'yearlyTotal' => $yearlyTotal,
            'availableYears' => Invoice::selectRaw("DISTINCT EXTRACT(YEAR FROM invoice_date) as year")
                ->orderByDesc('year')
                ->pluck('year'),
        ]);
    }

    public function taxSummary(Request $request): Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));
        $quarter = $request->integer('quarter', (int) ceil(now()->month / 3));

        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        $taxSummary = DB::table('invoice_tax_lines')
            ->join('invoices', 'invoice_tax_lines.invoice_id', '=', 'invoices.id')
            ->whereYear('invoices.invoice_date', $year)
            ->whereRaw("EXTRACT(MONTH FROM invoices.invoice_date) BETWEEN ? AND ?", [$startMonth, $endMonth])
            ->whereNotIn('invoices.status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->whereNull('invoices.deleted_at')
            ->selectRaw("
                invoice_tax_lines.tax_type_code,
                invoice_tax_lines.tax_type_name,
                invoice_tax_lines.tax_rate,
                SUM(invoice_tax_lines.taxable_amount) as total_taxable,
                SUM(invoice_tax_lines.tax_amount) as total_tax
            ")
            ->groupBy('invoice_tax_lines.tax_type_code', 'invoice_tax_lines.tax_type_name', 'invoice_tax_lines.tax_rate')
            ->orderBy('invoice_tax_lines.tax_rate')
            ->get();

        $totalTimbre = Invoice::whereYear('invoice_date', $year)
            ->whereRaw("EXTRACT(MONTH FROM invoice_date) BETWEEN ? AND ?", [$startMonth, $endMonth])
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->sum('timbre_fiscal');

        return Inertia::render('Reports/TaxSummary', [
            'year' => $year,
            'quarter' => $quarter,
            'taxSummary' => $taxSummary,
            'totalTimbre' => number_format((float) $totalTimbre, 3, '.', ''),
        ]);
    }

    public function customerAging(): Response
    {
        $customers = Customer::withSum(
            ['invoices' => fn ($q) => $q->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])],
            'total_ttc'
        )
            ->addSelect([
                'paid_total' => DB::table('payments')
                    ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                    ->whereColumn('invoices.customer_id', 'customers.id')
                    ->whereNull('invoices.deleted_at')
                    ->selectRaw('COALESCE(SUM(payments.amount), 0)'),
            ])
            ->orderByDesc('invoices_sum_total_ttc')
            ->paginate(20);

        return Inertia::render('Reports/CustomerAging', [
            'customers' => $customers,
        ]);
    }

    public function customerStatement(Request $request, Customer $customer): Response
    {
        $invoices = Invoice::where('customer_id', $customer->id)
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->with('payments')
            ->orderByDesc('invoice_date')
            ->paginate(25);

        $totals = Invoice::where('customer_id', $customer->id)
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->selectRaw("SUM(total_ttc) as total_invoiced, COUNT(*) as invoice_count")
            ->first();

        $totalPaid = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.customer_id', $customer->id)
            ->whereNull('invoices.deleted_at')
            ->sum('payments.amount');

        return Inertia::render('Reports/CustomerStatement', [
            'customer' => $customer,
            'invoices' => $invoices,
            'totals' => $totals,
            'totalPaid' => number_format((float) $totalPaid, 3, '.', ''),
            'balance' => number_format(((float) ($totals->total_invoiced ?? 0)) - (float) $totalPaid, 3, '.', ''),
        ]);
    }

    public function timbre(Request $request): Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));

        $monthlyTimbre = Invoice::whereYear('invoice_date', $year)
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->where('timbre_fiscal', '>', 0)
            ->selectRaw("EXTRACT(MONTH FROM invoice_date) as month, SUM(timbre_fiscal) as total_timbre, COUNT(*) as invoice_count")
            ->groupByRaw("EXTRACT(MONTH FROM invoice_date)")
            ->orderBy('month')
            ->get();

        $yearlyTotal = Invoice::whereYear('invoice_date', $year)
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value])
            ->sum('timbre_fiscal');

        return Inertia::render('Reports/Timbre', [
            'year' => $year,
            'monthlyTimbre' => $monthlyTimbre,
            'yearlyTotal' => number_format((float) $yearlyTotal, 3, '.', ''),
            'availableYears' => Invoice::selectRaw("DISTINCT EXTRACT(YEAR FROM invoice_date) as year")
                ->orderByDesc('year')
                ->pluck('year'),
        ]);
    }
}
