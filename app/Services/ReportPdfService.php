<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Collection;

class ReportPdfService
{
    public function __construct(private readonly PDF $pdf)
    {
    }

    /**
     * Generate Revenue Report PDF
     */
    public function generateRevenue(int $year, Collection $data, string $yearlyTotal): PDF
    {
        return $this->pdf->loadView('pdf.reports.revenue', [
            'year' => $year,
            'data' => $data,
            'yearlyTotal' => $yearlyTotal,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Generate Tax Summary Report PDF
     */
    public function generateTaxSummary(int $year, Collection $data, array $totals): PDF
    {
        return $this->pdf->loadView('pdf.reports.tax-summary', [
            'year' => $year,
            'data' => $data,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Generate Customer Aging Report PDF
     */
    public function generateCustomerAging(Collection $customers, array $totals): PDF
    {
        return $this->pdf->loadView('pdf.reports.customer-aging', [
            'customers' => $customers,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Generate Timbre Report PDF
     */
    public function generateTimbre(int $year, Collection $monthlyData, string $yearlyTotal): PDF
    {
        return $this->pdf->loadView('pdf.reports.timbre', [
            'year' => $year,
            'monthlyData' => $monthlyData,
            'yearlyTotal' => $yearlyTotal,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Generate Customer Statement PDF
     */
    public function generateCustomerStatement($customer, Collection $invoices, array $totals): PDF
    {
        return $this->pdf->loadView('pdf.reports.customer-statement', [
            'customer' => $customer,
            'invoices' => $invoices,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }
}
