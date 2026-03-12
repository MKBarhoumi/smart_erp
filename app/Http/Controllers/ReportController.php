<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\OldInvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\OldInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $month = $request->input('month');
        $quarter = $request->input('quarter');
        $customerId = $request->input('customer_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

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

        // OldInvoice query
        $query = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value]);

        // Apply filters
        if ($month) {
            $query->whereMonth('oldinvoice_date', $month);
        }
        if ($quarter) {
            $quarterMonths = match ((int) $quarter) {
                1 => [1, 2, 3],
                2 => [4, 5, 6],
                3 => [7, 8, 9],
                4 => [10, 11, 12],
                default => [],
            };
            if ($quarterMonths) {
                $query->whereIn(DB::raw('MONTH(oldinvoice_date)'), $quarterMonths);
            }
        }
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        if ($startDate) {
            $query->where('oldinvoice_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('oldinvoice_date', '<=', $endDate);
        }

        // Get OldInvoice monthly revenue
        $oldMonthlyRevenue = (clone $query)
            ->selectRaw("{$monthExpr} as month, SUM(total_ttc) as total, COUNT(*) as count")
            ->groupByRaw("{$monthExpr}, {$monthOrderExpr}")
            ->orderByRaw($monthOrderExpr)
            ->get()
            ->keyBy('month')
            ->toArray();

        $oldYearlyTotal = (float) (clone $query)->sum('total_ttc');

        // Invoice query (new TEIF invoices) - uses created_at as date reference
        // Only include non-draft and non-rejected invoices
        $invoiceQuery = Invoice::whereYear('created_at', $year)
            ->whereIn('status', [InvoiceStatus::ACCEPTED->value, InvoiceStatus::VALIDATED->value, InvoiceStatus::SUBMITTED->value]);

        if ($month) {
            $invoiceQuery->whereMonth('created_at', $month);
        }
        if ($quarter) {
            $quarterMonths = match ((int) $quarter) {
                1 => [1, 2, 3],
                2 => [4, 5, 6],
                3 => [7, 8, 9],
                4 => [10, 11, 12],
                default => [],
            };
            if ($quarterMonths) {
                $invoiceQuery->whereIn(DB::raw('MONTH(created_at)'), $quarterMonths);
            }
        }
        if ($startDate) {
            $invoiceQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $invoiceQuery->where('created_at', '<=', $endDate);
        }

        // Get Invoice monthly revenue - process in PHP due to JSON amount fields
        $invoices = $invoiceQuery->get();
        $invoiceMonthlyTotals = [];
        $invoiceYearlyTotal = 0.0;
        
        foreach ($invoices as $invoice) {
            $monthKey = $invoice->created_at->format('M');
            $total = (float) $invoice->total_ttc; // Uses accessor
            
            if (!isset($invoiceMonthlyTotals[$monthKey])) {
                $invoiceMonthlyTotals[$monthKey] = ['total' => 0.0, 'count' => 0];
            }
            $invoiceMonthlyTotals[$monthKey]['total'] += $total;
            $invoiceMonthlyTotals[$monthKey]['count']++;
            $invoiceYearlyTotal += $total;
        }

        // Combine OldInvoice and Invoice data
        $allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $combinedRevenue = [];
        
        foreach ($allMonths as $monthKey) {
            $oldData = $oldMonthlyRevenue[$monthKey] ?? null;
            $newData = $invoiceMonthlyTotals[$monthKey] ?? null;
            
            if ($oldData || $newData) {
                $total = ((float) ($oldData['total'] ?? 0)) + (($newData['total'] ?? 0));
                $count = ((int) ($oldData['count'] ?? 0)) + (($newData['count'] ?? 0));
                
                $combinedRevenue[] = [
                    'month' => $monthKey,
                    'total' => number_format($total, 3, '.', ''),
                    'count' => $count,
                ];
            }
        }

        $yearlyTotal = $oldYearlyTotal + $invoiceYearlyTotal;

        // Get customers for filter dropdown
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        // Get available years from both sources
        $oldYears = OldInvoice::selectRaw("DISTINCT YEAR(oldinvoice_date) as year")
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
        $newYears = Invoice::selectRaw("DISTINCT YEAR(created_at) as year")
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
        $availableYears = array_values(array_unique(array_merge($oldYears, $newYears)));
        rsort($availableYears);

        return Inertia::render('Reports/Revenue', [
            'year' => $year,
            'data' => $combinedRevenue,
            'yearlyTotal' => number_format($yearlyTotal, 3, '.', ''),
            'availableYears' => $availableYears,
            'customers' => $customers,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'quarter' => $quarter,
                'customer_id' => $customerId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function taxSummary(Request $request): Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));

        // Get driver-specific quarter extraction
        $driver = DB::connection()->getDriverName();
        switch ($driver) {
            case 'pgsql':
                $quarterExpr = "CEIL(EXTRACT(MONTH FROM oldinvoice_date) / 3)::integer";
                $yearExpr = "EXTRACT(YEAR FROM oldinvoice_date)::integer";
                break;
            case 'sqlite':
                $quarterExpr = "((CAST(strftime('%m', oldinvoice_date) AS INTEGER) + 2) / 3)";
                $yearExpr = "CAST(strftime('%Y', oldinvoice_date) AS INTEGER)";
                break;
            case 'sqlsrv':
                $quarterExpr = "DATEPART(QUARTER, oldinvoice_date)";
                $yearExpr = "YEAR(oldinvoice_date)";
                break;
            default: // mysql / mariadb
                $quarterExpr = "QUARTER(oldinvoice_date)";
                $yearExpr = "YEAR(oldinvoice_date)";
        }

        // Get quarterly data
        $quarterlyData = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw("{$quarterExpr} as quarter, SUM(total_tva) as tva_collected, SUM(timbre_fiscal) as timbre_fiscal, COUNT(*) as oldinvoice_count, SUM(total_ht) as taxable_base")
            ->groupByRaw($quarterExpr)
            ->orderByRaw($quarterExpr)
            ->get()
            ->map(fn ($row) => [
                'quarter' => (int) $row->quarter,
                'tva_collected' => number_format((float) $row->tva_collected, 3, '.', ''),
                'timbre_fiscal' => number_format((float) $row->timbre_fiscal, 3, '.', ''),
                'total_tax' => number_format((float) $row->tva_collected + (float) $row->timbre_fiscal, 3, '.', ''),
                'taxable_base' => number_format((float) $row->taxable_base, 3, '.', ''),
                'oldinvoice_count' => (int) $row->oldinvoice_count,
            ])
            ->keyBy('quarter');

        // Ensure all 4 quarters are present
        $data = collect([1, 2, 3, 4])->map(function ($q) use ($quarterlyData) {
            return $quarterlyData->get($q, [
                'quarter' => $q,
                'tva_collected' => '0.000',
                'timbre_fiscal' => '0.000',
                'total_tax' => '0.000',
                'taxable_base' => '0.000',
                'oldinvoice_count' => 0,
            ]);
        })->values();

        // Calculate yearly totals
        $yearlyTotals = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw('SUM(total_tva) as tva, SUM(timbre_fiscal) as timbre, SUM(total_ht) as base')
            ->first();

        $totals = [
            'tva' => number_format((float) ($yearlyTotals->tva ?? 0), 3, '.', ''),
            'timbre' => number_format((float) ($yearlyTotals->timbre ?? 0), 3, '.', ''),
            'total' => number_format((float) ($yearlyTotals->tva ?? 0) + (float) ($yearlyTotals->timbre ?? 0), 3, '.', ''),
            'base' => number_format((float) ($yearlyTotals->base ?? 0), 3, '.', ''),
        ];

        // Get available years for filtering
        $availableYears = OldInvoice::selectRaw("DISTINCT {$yearExpr} as year")
            ->whereNotNull('oldinvoice_date')
            ->orderByDesc('year')
            ->pluck('year')
            ->values()
            ->toArray();

        // If no years available, use current year
        if (empty($availableYears)) {
            $availableYears = [(int) now()->format('Y')];
        }

        return Inertia::render('Reports/TaxSummary', [
            'year' => $year,
            'data' => $data,
            'totals' => $totals,
            'availableYears' => $availableYears,
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

    public function customerStatementSelect(Request $request): Response
    {
        $search = $request->input('search', '');
        
        $customers = Customer::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('identifier_value', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'identifier_value']);
        
        return Inertia::render('Reports/CustomerStatementSelect', [
            'customers' => $customers,
            'search' => $search,
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

        // Get driver-specific month extraction
        $driver = DB::connection()->getDriverName();
        switch ($driver) {
            case 'pgsql':
                $monthExpr = "EXTRACT(MONTH FROM oldinvoice_date)::integer";
                $yearExpr = "EXTRACT(YEAR FROM oldinvoice_date)::integer";
                break;
            case 'sqlite':
                $monthExpr = "CAST(strftime('%m', oldinvoice_date) AS INTEGER)";
                $yearExpr = "CAST(strftime('%Y', oldinvoice_date) AS INTEGER)";
                break;
            case 'sqlsrv':
                $monthExpr = "MONTH(oldinvoice_date)";
                $yearExpr = "YEAR(oldinvoice_date)";
                break;
            default: // mysql / mariadb
                $monthExpr = "MONTH(oldinvoice_date)";
                $yearExpr = "YEAR(oldinvoice_date)";
        }

        $monthlyTimbre = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->where('timbre_fiscal', '>', 0)
            ->selectRaw("{$monthExpr} as month, SUM(timbre_fiscal) as total_timbre, COUNT(*) as oldinvoice_count")
            ->groupByRaw($monthExpr)
            ->orderBy('month')
            ->get();

        $yearlyTotal = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->sum('timbre_fiscal');

        $availableYears = OldInvoice::selectRaw("DISTINCT {$yearExpr} as year")
            ->whereNotNull('oldinvoice_date')
            ->orderByDesc('year')
            ->pluck('year')
            ->values()
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [(int) now()->format('Y')];
        }

        return Inertia::render('Reports/Timbre', [
            'year' => $year,
            'monthlyTimbre' => $monthlyTimbre,
            'yearlyTotal' => number_format((float) $yearlyTotal, 3, '.', ''),
            'availableYears' => $availableYears,
        ]);
    }

    // ============ PDF DOWNLOAD METHODS ============

    public function revenuePdf(Request $request): \Illuminate\Http\Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));

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
            default:
                $monthExpr = "DATE_FORMAT(oldinvoice_date, '%b')";
                $monthOrderExpr = "MONTH(oldinvoice_date)";
        }

        $data = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw("{$monthExpr} as month, SUM(total_ttc) as total, COUNT(*) as count")
            ->groupByRaw("{$monthExpr}, {$monthOrderExpr}")
            ->orderByRaw($monthOrderExpr)
            ->get();

        $yearlyTotal = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->sum('total_ttc');

        $pdf = Pdf::loadView('pdf.reports.revenue', [
            'year' => $year,
            'data' => $data,
            'yearlyTotal' => number_format((float) $yearlyTotal, 3, '.', ''),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"revenue_report_{$year}.pdf\"",
        ]);
    }

    public function taxSummaryPdf(Request $request): \Illuminate\Http\Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));

        $driver = DB::connection()->getDriverName();
        switch ($driver) {
            case 'pgsql':
                $quarterExpr = "CEIL(EXTRACT(MONTH FROM oldinvoice_date) / 3)::integer";
                break;
            case 'sqlite':
                $quarterExpr = "((CAST(strftime('%m', oldinvoice_date) AS INTEGER) + 2) / 3)";
                break;
            case 'sqlsrv':
                $quarterExpr = "DATEPART(QUARTER, oldinvoice_date)";
                break;
            default:
                $quarterExpr = "QUARTER(oldinvoice_date)";
        }

        $quarterlyData = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw("{$quarterExpr} as quarter, SUM(total_tva) as tva_collected, SUM(timbre_fiscal) as timbre_fiscal, COUNT(*) as oldinvoice_count, SUM(total_ht) as taxable_base")
            ->groupByRaw($quarterExpr)
            ->orderByRaw($quarterExpr)
            ->get()
            ->keyBy('quarter');

        $data = collect([1, 2, 3, 4])->map(function ($q) use ($quarterlyData) {
            $row = $quarterlyData->get($q);
            return [
                'quarter' => $q,
                'tva_collected' => number_format((float) ($row->tva_collected ?? 0), 3, '.', ''),
                'timbre_fiscal' => number_format((float) ($row->timbre_fiscal ?? 0), 3, '.', ''),
                'total_tax' => number_format((float) ($row->tva_collected ?? 0) + (float) ($row->timbre_fiscal ?? 0), 3, '.', ''),
                'taxable_base' => number_format((float) ($row->taxable_base ?? 0), 3, '.', ''),
                'oldinvoice_count' => (int) ($row->oldinvoice_count ?? 0),
            ];
        });

        $yearlyTotals = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->selectRaw('SUM(total_tva) as tva, SUM(timbre_fiscal) as timbre, SUM(total_ht) as base')
            ->first();

        $totals = [
            'tva' => number_format((float) ($yearlyTotals->tva ?? 0), 3, '.', ''),
            'timbre' => number_format((float) ($yearlyTotals->timbre ?? 0), 3, '.', ''),
            'total' => number_format((float) ($yearlyTotals->tva ?? 0) + (float) ($yearlyTotals->timbre ?? 0), 3, '.', ''),
            'base' => number_format((float) ($yearlyTotals->base ?? 0), 3, '.', ''),
        ];

        $pdf = Pdf::loadView('pdf.reports.tax-summary', [
            'year' => $year,
            'data' => $data,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"tax_summary_{$year}.pdf\"",
        ]);
    }

    public function customerAgingPdf(): \Illuminate\Http\Response
    {
        $today = now();
        $days30 = $today->copy()->subDays(30);
        $days60 = $today->copy()->subDays(60);
        $days90 = $today->copy()->subDays(90);

        $customers = Customer::select('customers.*')
            ->whereHas('oldinvoices', function ($q) {
                $q->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value]);
            })
            ->get()
            ->map(function ($customer) use ($days30, $days60, $days90) {
                $invoices = OldInvoice::where('customer_id', $customer->id)
                    ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
                    ->with('payments')
                    ->get();

                $current = $days_30_60 = $days_60_90 = $over_90 = 0;

                foreach ($invoices as $inv) {
                    $paid = $inv->payments->sum('amount');
                    $outstanding = (float) $inv->total_ttc - (float) $paid;
                    if ($outstanding <= 0) continue;

                    $invDateCarbon = \Carbon\Carbon::parse($inv->oldinvoice_date);

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
                    'name' => $customer->name,
                    'identifier_value' => $customer->identifier_value,
                    'total_outstanding' => number_format($totalOutstanding, 3, '.', ''),
                    'current' => number_format($current, 3, '.', ''),
                    'days_30_60' => number_format($days_30_60, 3, '.', ''),
                    'days_60_90' => number_format($days_60_90, 3, '.', ''),
                    'over_90' => number_format($over_90, 3, '.', ''),
                ];
            })
            ->filter()
            ->values();

        $totals = [
            'total_outstanding' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['total_outstanding'])), 3, '.', ''),
            'current' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['current'])), 3, '.', ''),
            'days_30_60' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['days_30_60'])), 3, '.', ''),
            'days_60_90' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['days_60_90'])), 3, '.', ''),
            'over_90' => number_format($customers->sum(fn ($c) => (float) str_replace(',', '', $c['over_90'])), 3, '.', ''),
        ];

        $pdf = Pdf::loadView('pdf.reports.customer-aging', [
            'customers' => $customers,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="customer_aging_report.pdf"',
        ]);
    }

    public function timbrePdf(Request $request): \Illuminate\Http\Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));

        $driver = DB::connection()->getDriverName();
        switch ($driver) {
            case 'pgsql':
                $monthExpr = "EXTRACT(MONTH FROM oldinvoice_date)::integer";
                break;
            case 'sqlite':
                $monthExpr = "CAST(strftime('%m', oldinvoice_date) AS INTEGER)";
                break;
            case 'sqlsrv':
                $monthExpr = "MONTH(oldinvoice_date)";
                break;
            default:
                $monthExpr = "MONTH(oldinvoice_date)";
        }

        $monthlyData = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->where('timbre_fiscal', '>', 0)
            ->selectRaw("{$monthExpr} as month, SUM(timbre_fiscal) as total_timbre, COUNT(*) as oldinvoice_count")
            ->groupByRaw($monthExpr)
            ->orderBy('month')
            ->get();

        $yearlyTotal = OldInvoice::whereYear('oldinvoice_date', $year)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->sum('timbre_fiscal');

        $pdf = Pdf::loadView('pdf.reports.timbre', [
            'year' => $year,
            'monthlyData' => $monthlyData,
            'yearlyTotal' => number_format((float) $yearlyTotal, 3, '.', ''),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"timbre_report_{$year}.pdf\"",
        ]);
    }

    public function customerStatementPdf(Customer $customer): \Illuminate\Http\Response
    {
        $invoices = OldInvoice::where('customer_id', $customer->id)
            ->whereNotIn('status', [OldInvoiceStatus::DRAFT->value, OldInvoiceStatus::REJECTED->value])
            ->with('payments')
            ->orderByDesc('oldinvoice_date')
            ->get();

        $totalInvoiced = $invoices->sum(fn ($i) => (float) $i->total_ttc);
        $totalPaid = $invoices->sum(fn ($i) => $i->payments->sum('amount'));
        $balance = $totalInvoiced - $totalPaid;

        $totals = [
            'total_invoiced' => number_format($totalInvoiced, 3, '.', ''),
            'total_paid' => number_format($totalPaid, 3, '.', ''),
            'balance' => number_format($balance, 3, '.', ''),
        ];

        $pdf = Pdf::loadView('pdf.reports.customer-statement', [
            'customer' => $customer,
            'invoices' => $invoices,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"statement_{$customer->id}.pdf\"",
        ]);
    }
}
