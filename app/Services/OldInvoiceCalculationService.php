<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OldInvoice;
use App\Models\OldInvoiceLine;

class OldInvoiceCalculationService
{
    /**
     * Calculate all totals for an oldinvoice based on its line items.
     *
     * @return array{
     *   total_ht: string,
     *   total_net_before_disc: string,
     *   total_gross: string,
     *   total_discount: string,
     *   total_tva: string,
     *   timbre_fiscal: string,
     *   total_ttc: string,
     *   total_allowances: string,
     *   total_charges: string,
     *   tax_summary: array<int, array{tax_type_code: string, tax_type_name: string, tax_rate: string, taxable_amount: string, tax_amount: string}>,
     *   lines: array<int, array{line_number: int, line_net_amount: string, tva_amount: string, discount_amount: string, line_total: string}>
     * }
     */
    public function calculateTotals(OldInvoice $oldinvoice, string $timbreFiscalAmount = '0.000', bool $timbreEnabled = false): array
    {
        $totalHt = '0.000';
        $totalNetBeforeDisc = '0.000';
        $totalTva = '0.000';
        $totalDiscount = '0.000';
        $totalCharges = '0.000';
        $taxSummary = [];
        $lineResults = [];

        foreach ($oldinvoice->lines as $line) {
            $lineCalc = $this->calculateLine($line);
            $lineResults[] = [
                'line_number' => $line->line_number,
                'line_net_amount' => $lineCalc['line_net_amount'],
                'tva_amount' => $lineCalc['tva_amount'],
                'discount_amount' => $lineCalc['discount_amount'],
                'line_total' => bcadd($lineCalc['line_net_amount'], $lineCalc['tva_amount'], 3),
            ];

            $totalNetBeforeDisc = bcadd($totalNetBeforeDisc, bcmul($line->quantity, $line->unit_price, 3), 3);
            $totalHt = bcadd($totalHt, $lineCalc['line_net_amount'], 3);
            $totalTva = bcadd($totalTva, $lineCalc['tva_amount'], 3);
            $totalDiscount = bcadd($totalDiscount, $lineCalc['discount_amount'], 3);

            $rateKey = $line->tva_rate;
            if (!isset($taxSummary[$rateKey])) {
                $taxSummary[$rateKey] = [
                    'tax_type_code' => 'VAT',
                    'tax_type_name' => 'TVA',
                    'tax_rate' => $rateKey,
                    'taxable_amount' => '0.000',
                    'tax_amount' => '0.000',
                ];
            }
            $taxSummary[$rateKey]['taxable_amount'] = bcadd($taxSummary[$rateKey]['taxable_amount'], $lineCalc['line_net_amount'], 3);
            $taxSummary[$rateKey]['tax_amount'] = bcadd($taxSummary[$rateKey]['tax_amount'], $lineCalc['tva_amount'], 3);
        }

        $timbreFiscal = $timbreEnabled ? $timbreFiscalAmount : '0.000';
        $totalGross = bcadd($totalHt, $totalDiscount, 3);
        $totalTtc = bcadd(bcadd($totalHt, $totalTva, 3), $timbreFiscal, 3);

        return [
            'total_ht' => $totalHt,
            'total_net_before_disc' => $totalNetBeforeDisc,
            'total_gross' => $totalGross,
            'total_discount' => $totalDiscount,
            'total_tva' => $totalTva,
            'timbre_fiscal' => $timbreFiscal,
            'total_ttc' => $totalTtc,
            'total_allowances' => $totalDiscount,
            'total_charges' => $totalCharges,
            'tax_summary' => array_values($taxSummary),
            'lines' => $lineResults,
        ];
    }

    /**
     * Calculate a single line item.
     *
     * @return array{line_net_amount: string, tva_amount: string, discount_amount: string}
     */
    public function calculateLine(OldInvoiceLine $line): array
    {
        $grossAmount = bcmul($line->quantity, $line->unit_price, 3);
        $discountAmount = '0.000';

        if (bccomp($line->discount_rate ?? '0', '0', 2) > 0) {
            $discountAmount = bcdiv(bcmul($grossAmount, $line->discount_rate, 3), '100', 3);
        }

        $lineNetAmount = bcsub($grossAmount, $discountAmount, 3);
        $tvaAmount = bcdiv(bcmul($lineNetAmount, $line->tva_rate, 3), '100', 3);

        return [
            'line_net_amount' => $lineNetAmount,
            'tva_amount' => $tvaAmount,
            'discount_amount' => $discountAmount,
        ];
    }

    /**
     * Format an amount with exactly 3 decimal places for TND.
     */
    public function formatTND(string $amount): string
    {
        return number_format((float) $amount, 3, '.', '');
    }
}
