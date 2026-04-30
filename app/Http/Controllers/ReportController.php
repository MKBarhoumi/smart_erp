<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $month = $this->normalizeNullableInput($request->input('month'));
        $quarter = $this->normalizeNullableInput($request->input('quarter'));
        $customerId = $this->normalizeNullableInput($request->input('customer_id'));
        $startDate = $this->normalizeNullableInput($request->input('start_date'));
        $endDate = $this->normalizeNullableInput($request->input('end_date'));

        $quarterMonths = $this->quarterMonths($quarter);
        $customerIdentifier = $this->customerIdentifier($customerId);

        $invoices = $this->reportableInvoiceQuery()
            ->get()
            ->filter(fn (Invoice $invoice) => $this->invoiceMatchesFilters(
                $invoice,
                $year,
                $month,
                $quarterMonths,
                $startDate,
                $endDate,
                $customerId,
                $customerIdentifier,
            ));

        $monthlyTotals = $invoices
            ->groupBy(fn (Invoice $invoice) => Carbon::parse($invoice->invoice_date)->format('M'))
            ->map(fn (Collection $rows) => [
                'total' => (float) $rows->sum(fn (Invoice $invoice) => (float) $invoice->total_ttc),
                'count' => $rows->count(),
            ])
            ->toArray();

        $allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = [];

        foreach ($allMonths as $monthKey) {
            $row = $monthlyTotals[$monthKey] ?? null;
            if (!$row) {
                continue;
            }

            $data[] = [
                'month' => $monthKey,
                'total' => number_format((float) $row['total'], 3, '.', ''),
                'count' => (int) $row['count'],
            ];
        }

        $yearlyTotal = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->total_ttc);
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Reports/Revenue', [
            'year' => $year,
            'data' => $data,
            'yearlyTotal' => number_format($yearlyTotal, 3, '.', ''),
            'availableYears' => $this->availableInvoiceYears(),
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
        $invoices = $this->invoicesForYear($year);

        $quarterlyData = $invoices
            ->groupBy(function (Invoice $invoice): int {
                $invoiceDate = Carbon::parse($invoice->invoice_date);

                return (int) ceil($invoiceDate->month / 3);
            })
            ->map(function (Collection $rows, int $quarter): array {
                $tvaCollected = (float) $rows->sum(fn (Invoice $invoice) => (float) $invoice->total_tva);
                $timbreFiscal = (float) $rows->sum(fn (Invoice $invoice) => $this->invoiceTimbre($invoice));
                $taxableBase = (float) $rows->sum(fn (Invoice $invoice) => (float) $invoice->total_ht);

                return [
                    'quarter' => $quarter,
                    'tva_collected' => number_format($tvaCollected, 3, '.', ''),
                    'timbre_fiscal' => number_format($timbreFiscal, 3, '.', ''),
                    'total_tax' => number_format($tvaCollected + $timbreFiscal, 3, '.', ''),
                    'taxable_base' => number_format($taxableBase, 3, '.', ''),
                    'invoice_count' => $rows->count(),
                ];
            })
            ->keyBy('quarter');

        $data = collect([1, 2, 3, 4])->map(function (int $quarter) use ($quarterlyData): array {
            return $quarterlyData->get($quarter, [
                'quarter' => $quarter,
                'tva_collected' => '0.000',
                'timbre_fiscal' => '0.000',
                'total_tax' => '0.000',
                'taxable_base' => '0.000',
                'invoice_count' => 0,
            ]);
        })->values();

        $totalTva = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->total_tva);
        $totalTimbre = (float) $invoices->sum(fn (Invoice $invoice) => $this->invoiceTimbre($invoice));
        $totalBase = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->total_ht);

        return Inertia::render('Reports/TaxSummary', [
            'year' => $year,
            'data' => $data,
            'totals' => [
                'tva' => number_format($totalTva, 3, '.', ''),
                'timbre' => number_format($totalTimbre, 3, '.', ''),
                'total' => number_format($totalTva + $totalTimbre, 3, '.', ''),
                'base' => number_format($totalBase, 3, '.', ''),
            ],
            'availableYears' => $this->availableInvoiceYears(),
        ]);
    }

    public function customerAging(): Response
    {
        $today = now();
        $days30 = $today->copy()->subDays(30);
        $days60 = $today->copy()->subDays(60);
        $days90 = $today->copy()->subDays(90);

        $invoices = $this->reportableInvoiceQuery()
            ->with('payments')
            ->whereNotNull('receiver_identifier')
            ->get()
            ->filter(fn (Invoice $invoice) => !empty($invoice->invoice_date));

        $invoicesByIdentifier = $invoices->groupBy('receiver_identifier');

        $customers = Customer::whereIn('identifier_value', $invoicesByIdentifier->keys()->all())
            ->get(['id', 'name', 'identifier_value'])
            ->map(function (Customer $customer) use ($invoicesByIdentifier, $days30, $days60, $days90) {
                $customerInvoices = $invoicesByIdentifier->get($customer->identifier_value, collect());

                $current = 0.0;
                $days_30_60 = 0.0;
                $days_60_90 = 0.0;
                $over_90 = 0.0;
                $oldestDate = null;

                foreach ($customerInvoices as $invoice) {
                    $paid = (float) $invoice->payments->sum('amount');
                    $outstanding = (float) $invoice->total_ttc - $paid;
                    if ($outstanding <= 0) {
                        continue;
                    }

                    $invoiceDate = Carbon::parse($invoice->invoice_date);
                    if (!$oldestDate || $invoiceDate->lt($oldestDate)) {
                        $oldestDate = $invoiceDate;
                    }

                    if ($invoiceDate->greaterThanOrEqualTo($days30)) {
                        $current += $outstanding;
                    } elseif ($invoiceDate->greaterThanOrEqualTo($days60)) {
                        $days_30_60 += $outstanding;
                    } elseif ($invoiceDate->greaterThanOrEqualTo($days90)) {
                        $days_60_90 += $outstanding;
                    } else {
                        $over_90 += $outstanding;
                    }
                }

                $totalOutstanding = $current + $days_30_60 + $days_60_90 + $over_90;
                if ($totalOutstanding <= 0) {
                    return null;
                }

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'identifier_value' => $customer->identifier_value,
                    'total_outstanding' => number_format($totalOutstanding, 3, '.', ''),
                    'current' => number_format($current, 3, '.', ''),
                    'days_30_60' => number_format($days_30_60, 3, '.', ''),
                    'days_60_90' => number_format($days_60_90, 3, '.', ''),
                    'over_90' => number_format($over_90, 3, '.', ''),
                    'oldest_invoice_date' => $oldestDate?->format('Y-m-d'),
                ];
            })
            ->filter()
            ->values();

        $totals = [
            'total_outstanding' => number_format($customers->sum(fn ($c) => (float) ($c['total_outstanding'] ?? 0)), 3, '.', ''),
            'current' => number_format($customers->sum(fn ($c) => (float) ($c['current'] ?? 0)), 3, '.', ''),
            'days_30_60' => number_format($customers->sum(fn ($c) => (float) ($c['days_30_60'] ?? 0)), 3, '.', ''),
            'days_60_90' => number_format($customers->sum(fn ($c) => (float) ($c['days_60_90'] ?? 0)), 3, '.', ''),
            'over_90' => number_format($customers->sum(fn ($c) => (float) ($c['over_90'] ?? 0)), 3, '.', ''),
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
            ->when($search, fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
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
        $customerIdentifiers = collect([
            $customer->identifier_value,
            $customer->matricule_fiscal,
        ])->filter()->unique()->values();

        $invoices = $this->reportableInvoiceQuery()
            ->with('payments')
            ->whereIn('receiver_identifier', $customerIdentifiers)
            ->get()
            ->sortByDesc(function (Invoice $invoice): int {
                $invoiceDate = $invoice->invoice_date ?: $invoice->created_at?->format('Y-m-d');

                return $invoiceDate ? Carbon::parse($invoiceDate)->timestamp : 0;
            })
            ->values();

        $totalInvoiced = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->total_ttc);
        $totalPaid = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->payments->sum('amount'));

        $invoiceRows = $invoices->map(function (Invoice $invoice): array {
            $paidAmount = (float) $invoice->payments->sum('amount');
            $totalTtc = (float) $invoice->total_ttc;
            $invoiceDate = $invoice->invoice_date ?: $invoice->created_at?->format('Y-m-d');

            return [
                'id' => $invoice->id,
                'document_identifier' => $invoice->document_identifier,
                'invoice_date' => $invoiceDate,
                'status' => $invoice->status,
                'total_ttc' => number_format($totalTtc, 3, '.', ''),
                'paid_amount' => number_format($paidAmount, 3, '.', ''),
                'remaining_balance' => number_format($totalTtc - $paidAmount, 3, '.', ''),
            ];
        })->values();

        return Inertia::render('Reports/CustomerStatement', [
            'customer' => $customer,
            'invoices' => $invoiceRows,
            'totals' => [
                'total_invoiced' => number_format($totalInvoiced, 3, '.', ''),
                'invoice_count' => $invoices->count(),
            ],
            'totalPaid' => number_format($totalPaid, 3, '.', ''),
            'balance' => number_format($totalInvoiced - $totalPaid, 3, '.', ''),
        ]);
    }

    public function timbre(Request $request): Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));
        $invoices = $this->invoicesForYear($year);

        $monthlyTimbre = $invoices
            ->map(function (Invoice $invoice): array {
                $invoiceDate = Carbon::parse($invoice->invoice_date);

                return [
                    'month' => (int) $invoiceDate->format('n'),
                    'total_timbre' => $this->invoiceTimbre($invoice),
                ];
            })
            ->filter(fn (array $row) => (float) $row['total_timbre'] > 0)
            ->groupBy('month')
            ->map(function (Collection $rows, int $month): array {
                return [
                    'month' => $month,
                    'total_timbre' => number_format((float) $rows->sum('total_timbre'), 3, '.', ''),
                    'invoice_count' => $rows->count(),
                ];
            })
            ->sortBy('month')
            ->values();

        $yearlyTotal = (float) $monthlyTimbre->sum(fn (array $row) => (float) $row['total_timbre']);

        return Inertia::render('Reports/Timbre', [
            'year' => $year,
            'monthlyTimbre' => $monthlyTimbre,
            'yearlyTotal' => number_format($yearlyTotal, 3, '.', ''),
            'availableYears' => $this->availableInvoiceYears(),
        ]);
    }

    public function revenuePdf(Request $request): \Illuminate\Http\Response
    {
        $year = $request->integer('year', (int) now()->format('Y'));
        $month = $this->normalizeNullableInput($request->input('month'));
        $quarter = $this->normalizeNullableInput($request->input('quarter'));
        $customerId = $this->normalizeNullableInput($request->input('customer_id'));
        $startDate = $this->normalizeNullableInput($request->input('start_date'));
        $endDate = $this->normalizeNullableInput($request->input('end_date'));

        $quarterMonths = $this->quarterMonths($quarter);
        $customerIdentifier = $this->customerIdentifier($customerId);

        $data = $this->reportableInvoiceQuery()
            ->get()
            ->filter(fn (Invoice $invoice) => $this->invoiceMatchesFilters(
                $invoice,
                $year,
                $month,
                $quarterMonths,
                $startDate,
                $endDate,
                $customerId,
                $customerIdentifier,
            ))
            ->groupBy(fn (Invoice $invoice) => Carbon::parse($invoice->invoice_date)->format('M'))
            ->map(fn (Collection $rows, string $monthName) => [
                'month' => $monthName,
                'total' => (float) $rows->sum(fn (Invoice $invoice) => (float) $invoice->total_ttc),
                'count' => $rows->count(),
            ])
            ->sortBy(fn (array $row) => $this->monthSortOrder($row['month']))
            ->values();

        $yearlyTotal = (float) $data->sum(fn (array $row) => (float) $row['total']);

        $pdf = Pdf::loadView('pdf.reports.revenue', [
            'year' => $year,
            'data' => $data,
            'yearlyTotal' => number_format($yearlyTotal, 3, '.', ''),
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
        $invoices = $this->invoicesForYear($year);

        $quarterlyData = $invoices
            ->groupBy(function (Invoice $invoice): int {
                $invoiceDate = Carbon::parse($invoice->invoice_date);

                return (int) ceil($invoiceDate->month / 3);
            })
            ->map(function (Collection $rows, int $quarter): array {
                $tvaCollected = (float) $rows->sum(fn (Invoice $invoice) => (float) $invoice->total_tva);
                $timbreFiscal = (float) $rows->sum(fn (Invoice $invoice) => $this->invoiceTimbre($invoice));
                $taxableBase = (float) $rows->sum(fn (Invoice $invoice) => (float) $invoice->total_ht);

                return [
                    'quarter' => $quarter,
                    'tva_collected' => number_format($tvaCollected, 3, '.', ''),
                    'timbre_fiscal' => number_format($timbreFiscal, 3, '.', ''),
                    'total_tax' => number_format($tvaCollected + $timbreFiscal, 3, '.', ''),
                    'taxable_base' => number_format($taxableBase, 3, '.', ''),
                    'invoice_count' => $rows->count(),
                ];
            })
            ->keyBy('quarter');

        $data = collect([1, 2, 3, 4])->map(function (int $quarter) use ($quarterlyData): array {
            return $quarterlyData->get($quarter, [
                'quarter' => $quarter,
                'tva_collected' => '0.000',
                'timbre_fiscal' => '0.000',
                'total_tax' => '0.000',
                'taxable_base' => '0.000',
                'invoice_count' => 0,
            ]);
        });

        $totalTva = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->total_tva);
        $totalTimbre = (float) $invoices->sum(fn (Invoice $invoice) => $this->invoiceTimbre($invoice));
        $totalBase = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->total_ht);

        $pdf = Pdf::loadView('pdf.reports.tax-summary', [
            'year' => $year,
            'data' => $data,
            'totals' => [
                'tva' => number_format($totalTva, 3, '.', ''),
                'timbre' => number_format($totalTimbre, 3, '.', ''),
                'total' => number_format($totalTva + $totalTimbre, 3, '.', ''),
                'base' => number_format($totalBase, 3, '.', ''),
            ],
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

        $invoices = $this->reportableInvoiceQuery()
            ->with('payments')
            ->whereNotNull('receiver_identifier')
            ->get()
            ->filter(fn (Invoice $invoice) => !empty($invoice->invoice_date));

        $invoicesByIdentifier = $invoices->groupBy('receiver_identifier');

        $customers = Customer::whereIn('identifier_value', $invoicesByIdentifier->keys()->all())
            ->get(['id', 'name', 'identifier_value'])
            ->map(function (Customer $customer) use ($invoicesByIdentifier, $days30, $days60, $days90) {
                $customerInvoices = $invoicesByIdentifier->get($customer->identifier_value, collect());

                $current = 0.0;
                $days_30_60 = 0.0;
                $days_60_90 = 0.0;
                $over_90 = 0.0;

                foreach ($customerInvoices as $invoice) {
                    $paid = (float) $invoice->payments->sum('amount');
                    $outstanding = (float) $invoice->total_ttc - $paid;
                    if ($outstanding <= 0) {
                        continue;
                    }

                    $invoiceDate = Carbon::parse($invoice->invoice_date);

                    if ($invoiceDate->greaterThanOrEqualTo($days30)) {
                        $current += $outstanding;
                    } elseif ($invoiceDate->greaterThanOrEqualTo($days60)) {
                        $days_30_60 += $outstanding;
                    } elseif ($invoiceDate->greaterThanOrEqualTo($days90)) {
                        $days_60_90 += $outstanding;
                    } else {
                        $over_90 += $outstanding;
                    }
                }

                $totalOutstanding = $current + $days_30_60 + $days_60_90 + $over_90;
                if ($totalOutstanding <= 0) {
                    return null;
                }

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
            'total_outstanding' => number_format($customers->sum(fn ($c) => (float) ($c['total_outstanding'] ?? 0)), 3, '.', ''),
            'current' => number_format($customers->sum(fn ($c) => (float) ($c['current'] ?? 0)), 3, '.', ''),
            'days_30_60' => number_format($customers->sum(fn ($c) => (float) ($c['days_30_60'] ?? 0)), 3, '.', ''),
            'days_60_90' => number_format($customers->sum(fn ($c) => (float) ($c['days_60_90'] ?? 0)), 3, '.', ''),
            'over_90' => number_format($customers->sum(fn ($c) => (float) ($c['over_90'] ?? 0)), 3, '.', ''),
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
        $invoices = $this->invoicesForYear($year);

        $monthlyData = $invoices
            ->map(function (Invoice $invoice): array {
                $invoiceDate = Carbon::parse($invoice->invoice_date);

                return [
                    'month' => (int) $invoiceDate->format('n'),
                    'total_timbre' => $this->invoiceTimbre($invoice),
                ];
            })
            ->filter(fn (array $row) => (float) $row['total_timbre'] > 0)
            ->groupBy('month')
            ->map(function (Collection $rows, int $month): array {
                return [
                    'month' => $month,
                    'total_timbre' => number_format((float) $rows->sum('total_timbre'), 3, '.', ''),
                    'invoice_count' => $rows->count(),
                ];
            })
            ->sortBy('month')
            ->values();

        $yearlyTotal = (float) $monthlyData->sum(fn (array $row) => (float) $row['total_timbre']);

        $pdf = Pdf::loadView('pdf.reports.timbre', [
            'year' => $year,
            'monthlyData' => $monthlyData,
            'yearlyTotal' => number_format($yearlyTotal, 3, '.', ''),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"timbre_report_{$year}.pdf\"",
        ]);
    }

    public function customerStatementPdf(Customer $customer): \Illuminate\Http\Response
    {
        $customerIdentifiers = collect([
            $customer->identifier_value,
            $customer->matricule_fiscal,
        ])->filter()->unique()->values();

        $invoices = $this->reportableInvoiceQuery()
            ->with('payments')
            ->whereIn('receiver_identifier', $customerIdentifiers)
            ->get()
            ->sortByDesc(function (Invoice $invoice): int {
                $invoiceDate = $invoice->invoice_date ?: $invoice->created_at?->format('Y-m-d');

                return $invoiceDate ? Carbon::parse($invoiceDate)->timestamp : 0;
            })
            ->values();

        $totalInvoiced = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->total_ttc);
        $totalPaid = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->payments->sum('amount'));
        $balance = $totalInvoiced - $totalPaid;

        $pdf = Pdf::loadView('pdf.reports.customer-statement', [
            'customer' => $customer,
            'invoices' => $invoices,
            'totals' => [
                'total_invoiced' => number_format($totalInvoiced, 3, '.', ''),
                'total_paid' => number_format($totalPaid, 3, '.', ''),
                'balance' => number_format($balance, 3, '.', ''),
            ],
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"statement_{$customer->id}.pdf\"",
        ]);
    }

    private function reportableInvoiceQuery(): Builder
    {
        return Invoice::query()->whereNotIn('status', [
            InvoiceStatus::DRAFT->value,
            InvoiceStatus::REJECTED->value,
        ]);
    }

    private function invoicesForYear(int $year): Collection
    {
        return $this->reportableInvoiceQuery()
            ->get()
            ->filter(function (Invoice $invoice) use ($year): bool {
                if (empty($invoice->invoice_date)) {
                    return false;
                }

                return (int) Carbon::parse($invoice->invoice_date)->format('Y') === $year;
            })
            ->values();
    }

    private function availableInvoiceYears(): array
    {
        $years = $this->reportableInvoiceQuery()
            ->get()
            ->pluck('invoice_date')
            ->filter()
            ->map(fn (string $invoiceDate) => (int) Carbon::parse($invoiceDate)->format('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        if (empty($years)) {
            return [(int) now()->format('Y')];
        }

        return $years;
    }

    private function invoiceMatchesFilters(
        Invoice $invoice,
        int $year,
        mixed $month,
        array $quarterMonths,
        mixed $startDate,
        mixed $endDate,
        mixed $customerId,
        ?string $customerIdentifier,
    ): bool {
        $month = $this->normalizeNullableInput($month);
        $startDate = $this->normalizeNullableInput($startDate);
        $endDate = $this->normalizeNullableInput($endDate);
        $customerId = $this->normalizeNullableInput($customerId);

        if (empty($invoice->invoice_date)) {
            return false;
        }

        $invoiceDate = Carbon::parse($invoice->invoice_date);

        if ((int) $invoiceDate->format('Y') !== $year) {
            return false;
        }

        if ($month && (int) $invoiceDate->format('n') !== (int) $month) {
            return false;
        }

        if (!empty($quarterMonths) && !in_array((int) $invoiceDate->format('n'), $quarterMonths, true)) {
            return false;
        }

        if ($startDate && $invoiceDate->lt(Carbon::parse((string) $startDate)->startOfDay())) {
            return false;
        }

        if ($endDate && $invoiceDate->gt(Carbon::parse((string) $endDate)->endOfDay())) {
            return false;
        }

        if ($customerId && !$customerIdentifier) {
            return false;
        }

        if ($customerIdentifier && $invoice->receiver_identifier !== $customerIdentifier) {
            return false;
        }

        return true;
    }

    private function quarterMonths(mixed $quarter): array
    {
        return match ((int) $quarter) {
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12],
            default => [],
        };
    }

    private function customerIdentifier(mixed $customerId): ?string
    {
        $customerId = $this->normalizeNullableInput($customerId);

        if (!$customerId) {
            return null;
        }

        return Customer::where('id', $customerId)->value('identifier_value');
    }

    private function monthSortOrder(string $month): int
    {
        $monthOrder = [
            'Jan' => 1,
            'Feb' => 2,
            'Mar' => 3,
            'Apr' => 4,
            'May' => 5,
            'Jun' => 6,
            'Jul' => 7,
            'Aug' => 8,
            'Sep' => 9,
            'Oct' => 10,
            'Nov' => 11,
            'Dec' => 12,
        ];

        return $monthOrder[$month] ?? 99;
    }

    private function invoiceTimbre(Invoice $invoice): float
    {
        $rawValue = $invoice->getAttributes()['timbre_fiscal'] ?? 0;

        return (float) $rawValue;
    }

    private function normalizeNullableInput(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (in_array(strtolower($trimmed), ['null', 'undefined'], true)) {
            return null;
        }

        return $trimmed;
    }
}
