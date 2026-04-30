<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLine;

class InvoiceCalculationService
{
    /**
     * Calculate all totals for an invoice based on its line items.
     */
    public function calculateTotals(Invoice $invoice, string $timbreFiscalAmount = '0.000', bool $timbreEnabled = false): array
    {
        $totalHt = '0.000';
        $totalTva = '0.000';
        $totalDiscount = '0.000';
        $taxSummary = [];
        $lineResults = [];

        foreach ($invoice->lines as $line) {
            $lineCalc = $this->calculateLine($line);
            $lineResults[] = [
                'id' => $line->id,
                'line_net_amount' => $lineCalc['line_net_amount'],
                'tva_amount' => $lineCalc['tva_amount'],
                'discount_amount' => $lineCalc['discount_amount'],
                'line_total' => bcadd($lineCalc['line_net_amount'], $lineCalc['tva_amount'], 3),
            ];

            $totalHt = bcadd($totalHt, $lineCalc['line_net_amount'], 3);
            $totalTva = bcadd($totalTva, $lineCalc['tva_amount'], 3);
            $totalDiscount = bcadd($totalDiscount, $lineCalc['discount_amount'], 3);

            $rateKey = $line->tax_rate;
            if (!isset($taxSummary[$rateKey])) {
                $taxSummary[$rateKey] = [
                    'tax_type_code' => $line->tax_type_code,
                    'tax_type_name' => $line->tax_type_name,
                    'tax_rate' => $rateKey,
                    'taxable_amount' => '0.000',
                    'tax_amount' => '0.000',
                ];
            }
            $taxSummary[$rateKey]['taxable_amount'] = bcadd($taxSummary[$rateKey]['taxable_amount'], $lineCalc['line_net_amount'], 3);
            $taxSummary[$rateKey]['tax_amount'] = bcadd($taxSummary[$rateKey]['tax_amount'], $lineCalc['tva_amount'], 3);
        }

        $timbreFiscal = $timbreEnabled ? $timbreFiscalAmount : '0.000';
        $totalTtc = bcadd(bcadd($totalHt, $totalTva, 3), $timbreFiscal, 3);

        return [
            'total_ht' => $totalHt,
            'total_discount' => $totalDiscount,
            'total_tva' => $totalTva,
            'timbre_fiscal' => $timbreFiscal,
            'total_ttc' => $totalTtc,
            'tax_summary' => array_values($taxSummary),
            'lines' => $lineResults,
        ];
    }

    /**
     * Calculate a single line item.
     */
    public function calculateLine(InvoiceLine $line): array
    {
        // For InvoiceLine, unit price is usually stored in 'amounts' JSON with amount_type_code '212' (Item price)
        // or we can try to find it.
        $unitPrice = '0.000';
        foreach ($line->amounts as $amt) {
            if ($amt['amount_type_code'] === '212' || $amt['amount_type_code'] === '146') {
                $unitPrice = $amt['amount'];
                break;
            }
        }

        $quantity = $line->quantity ?: '0';
        $grossAmount = bcmul($quantity, $unitPrice, 3);
        $discountAmount = '0.000';

        if (bccomp((string)($line->discount_rate ?? '0'), '0', 2) > 0) {
            $discountAmount = bcdiv(bcmul($grossAmount, (string)$line->discount_rate, 3), '100', 3);
        }

        $lineNetAmount = bcsub($grossAmount, $discountAmount, 3);
        $tvaAmount = bcdiv(bcmul($lineNetAmount, (string)($line->tax_rate ?: '0'), 3), '100', 3);

        return [
            'line_net_amount' => $lineNetAmount,
            'tva_amount' => $tvaAmount,
            'discount_amount' => $discountAmount,
        ];
    }

    /**
     * Format an amount for TND.
     */
    public function formatTND(string $amount): string
    {
        return number_format((float) $amount, 3, '.', '');
    }
}
