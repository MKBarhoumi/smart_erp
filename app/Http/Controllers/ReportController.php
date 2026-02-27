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

        // Get the database driver to use appropriate date functions
        $driver = DB::connection()->getDriverName();
        switch ($driver) {
            case 'pgsql':
                $monthExpr = "TO_CHAR(oldinvoice_date, 'Mon')";
                $monthOrderExpr = "EXTRACT(MONTH FROM oldinvoice_date)";
                break;
            case 'sqlite':
                $monthExpr = "strftime('%m', oldinvoice_date)";
                $monthOrderExpr = "strftime('%m', oldinvoice_date)";
                break;
            case 'sqlsrv':
                $monthExpr = "FORMAT(oldinvoice_date, 'MMM')";
                $monthOrderExpr = "MONTH(oldinvoice_date)";
                break;
            default: // mysql / mariadb
                $monthExpr = "DATE_FORMAT(oldinvoice_date, '%b')";
                $monthOrderExpr = "MONTH(oldinvoice_date)";
        }

        $monthlyRevenue = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw("{$monthExpr} as month, SUM(total_ttc) as total, COUNT(*) as count")
            ->groupByRaw("{$monthExpr}, {$monthOrderExpr}")
            ->orderByRaw($monthOrderExpr)
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total' => number_format((float) $row->total, 3, '.', ''),
                'count' => (int) $row->count,
            ]);

        $yearlyTotal = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->sum('total_ttc');

        return Inertia::render('Reports/Revenue', [
            'year' => $year,
            'data' => $monthlyRevenue,
            'yearlyTotal' => number_format((float) $yearlyTotal, 3, '.', ''),
            'availableYears' => OldInvoice::selectRaw("DISTINCT YEAR(oldinvoice_date) as year")
                ->orderByDesc('year')
                ->pluck('year')
                ->values()
                ->toArray(),
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
        $today = now();
        $days30 = $today->copy()->subDays(30);
        $days60 = $today->copy()->subDays(60);
        $days90 = $today->copy()->subDays(90);

        // Get all customers with outstanding invoices and calculate aging buckets
        $customers = Customer::select('customers.*')
            ->whereHas('oldinvoices', function ($q) {
                $q->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value]);
            })
            ->get()
            ->map(function ($customer) use ($today, $days30, $days60, $days90) {
                // Get all invoices for this customer
                $invoices = OldInvoice::where('customer_id', $customer->id)
                    ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
                    ->with('payments')
                    ->get();

                $current = 0;
                $days_30_60 = 0;
                $days_60_90 = 0;
                $over_90 = 0;
                $oldestDate = null;

                foreach ($invoices as $inv) {
                    $paid = $inv->payments->sum('amount');
                    $outstanding = (float) $inv->total_ttc - (float) $paid;

                    if ($outstanding <= 0) continue;

                    $invDate = $inv->oldinvoice_date;
                    if (!$oldestDate || $invDate < $oldestDate) {
                        $oldestDate = $invDate;
                    }

                    $invDateCarbon = \Carbon\Carbon::parse($invDate);

                    if ($invDateCarbon >= $days30) {
                        $current += $outstanding;
                    } elseif ($invDateCarbon >= $days60) {
                        $days_30_60 += $outstanding;
                    } elseif ($invDateCarbon >= $days90) {
                        $days_60_90 += $outstanding;
                    } else {
                        $over_90 += $outstanding;
                    }
                }

                $totalOutstanding = $current + $days_30_60 + $days_60_90 + $over_90;

                if ($totalOutstanding <= 0) return null;

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'identifier_value' => $customer->identifier_value,
                    'total_outstanding' => number_format($totalOutstanding, 3, '.', ''),
                    'current' => number_format($current, 3, '.', ''),
                    'days_30_60' => number_format($days_30_60, 3, '.', ''),
                    'days_60_90' => number_format($days_60_90, 3, '.', ''),
                    'over_90' => number_format($over_90, 3, '.', ''),
                    'oldest_oldinvoice_date' => $oldestDate,
                ];
            })
            ->filter()
            ->values();

        // Calculate totals
        $totals = [
            'total_outstanding' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['total_outstanding'])), 3, '.', ''),
            'current' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['current'])), 3, '.', ''),
            'days_30_60' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['days_30_60'])), 3, '.', ''),
            'days_60_90' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['days_60_90'])), 3, '.', ''),
            'over_90' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['over_90'])), 3, '.', ''),
        ];

        return Inertia::render('Reports/CustomerAging', [
            'customers' => $customers->toArray(),
            'totals' => $totals,
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
