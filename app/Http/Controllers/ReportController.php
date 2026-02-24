<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OldInvoiceStatus;
use App\Models\Customer;
use App\Models\OldInvoice;
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

        $monthlyRevenue = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw("EXTRACT(MONTH FROM oldinvoice_date) as month, SUM(total_ht) as total_ht, SUM(total_tva) as total_tva, SUM(total_ttc) as total_ttc, COUNT(*) as oldinvoice_count")
            ->groupByRaw("EXTRACT(MONTH FROM oldinvoice_date)")
            ->orderBy('month')
            ->get();

        $yearlyTotal = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw("SUM(total_ht) as total_ht, SUM(total_tva) as total_tva, SUM(total_ttc) as total_ttc, SUM(timbre_fiscal) as total_timbre, COUNT(*) as oldinvoice_count")
            ->first();

        return Inertia::render('Reports/Revenue', [
            'year' => $year,
            'monthlyRevenue' => $monthlyRevenue,
            'yearlyTotal' => $yearlyTotal,
            'availableYears' => OldInvoice::selectRaw("DISTINCT EXTRACT(YEAR FROM oldinvoice_date) as year")
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

        $taxSummary = DB::table('oldinvoice_tax_lines')
            ->join('oldinvoices', 'oldinvoice_tax_lines.oldinvoice_id', '=', 'oldinvoices.id')
            ->whereYear('oldinvoices.oldinvoice_date', $year)
            ->whereRaw("EXTRACT(MONTH FROM oldinvoices.oldinvoice_date) BETWEEN ? AND ?", [$startMonth, $endMonth])
            ->whereNotIn('oldinvoices.status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->whereNull('oldinvoices.deleted_at')
            ->selectRaw("
                oldinvoice_tax_lines.tax_type_code,
                oldinvoice_tax_lines.tax_type_name,
                oldinvoice_tax_lines.tax_rate,
                SUM(oldinvoice_tax_lines.taxable_amount) as total_taxable,
                SUM(oldinvoice_tax_lines.tax_amount) as total_tax
            ")
            ->groupBy('oldinvoice_tax_lines.tax_type_code', 'oldinvoice_tax_lines.tax_type_name', 'oldinvoice_tax_lines.tax_rate')
            ->orderBy('oldinvoice_tax_lines.tax_rate')
            ->get();

        $totalTimbre = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereRaw("EXTRACT(MONTH FROM oldinvoice_date) BETWEEN ? AND ?", [$startMonth, $endMonth])
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
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
            ['oldinvoices' => fn ($q) => $q->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])],
            'total_ttc'
        )
            ->addSelect([
                'paid_total' => DB::table('payments')
                    ->join('oldinvoices', 'payments.oldinvoice_id', '=', 'oldinvoices.id')
                    ->whereColumn('oldinvoices.customer_id', 'customers.id')
                    ->whereNull('oldinvoices.deleted_at')
                    ->selectRaw('COALESCE(SUM(payments.amount), 0)'),
            ])
            ->orderByDesc('oldinvoices_sum_total_ttc')
            ->paginate(20);

        return Inertia::render('Reports/CustomerAging', [
            'customers' => $customers,
        ]);
    }

    public function customerStatement(Request $request, Customer $customer): Response
    {
        $oldinvoices = OldInvoice::where('customer_id', $customer->id)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->with('payments')
            ->orderByDesc('oldinvoice_date')
            ->paginate(25);

        $totals = OldInvoice::where('customer_id', $customer->id)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw("SUM(total_ttc) as total_oldinvoiced, COUNT(*) as oldinvoice_count")
            ->first();

        $totalPaid = DB::table('payments')
            ->join('oldinvoices', 'payments.oldinvoice_id', '=', 'oldinvoices.id')
            ->where('oldinvoices.customer_id', $customer->id)
            ->whereNull('oldinvoices.deleted_at')
            ->sum('payments.amount');

        return Inertia::render('Reports/CustomerStatement', [
            'customer' => $customer,
            'oldinvoices' => $oldinvoices,
            'totals' => $totals,
            'totalPaid' => number_format((float) $totalPaid, 3, '.', ''),
            'balance' => number_format(((float) ($totals->total_oldinvoiced ?? 0)) - (float) $totalPaid, 3, '.', ''),
        ]);
    }

    public function timbre(Request $request): Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));

        $monthlyTimbre = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->where('timbre_fiscal', '>', 0)
            ->selectRaw("EXTRACT(MONTH FROM oldinvoice_date) as month, SUM(timbre_fiscal) as total_timbre, COUNT(*) as oldinvoice_count")
            ->groupByRaw("EXTRACT(MONTH FROM oldinvoice_date)")
            ->orderBy('month')
            ->get();

        $yearlyTotal = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->sum('timbre_fiscal');

        return Inertia::render('Reports/Timbre', [
            'year' => $year,
            'monthlyTimbre' => $monthlyTimbre,
            'yearlyTotal' => number_format((float) $yearlyTotal, 3, '.', ''),
            'availableYears' => OldInvoice::selectRaw("DISTINCT EXTRACT(YEAR FROM oldinvoice_date) as year")
                ->orderByDesc('year')
                ->pluck('year'),
        ]);
    }
}
