<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLine;

class InvoiceWithoutTvaCalculationService
{
    /**
     * Calculate invoice totals for export without TVA.
     *
     * @return array{
     *   total_ht: string,
     *   total_tva: string,
     *   total_ttc: string,
     *   taxable_amount: string,
     * }
     */
    public function calculate(Invoice $invoice): array
    {
        $totalHt = '0.000';

        foreach ($invoice->lines as $line) {
            $lineNet = $this->resolveLineNetAmount($line);
            $totalHt = bcadd($totalHt, $lineNet, 3);
        }

        return [
            'total_ht' => $totalHt,
            'total_tva' => '0.000',
            'total_ttc' => $totalHt,
            'taxable_amount' => $totalHt,
        ];
    }

    private function resolveLineNetAmount(InvoiceLine $line): string
    {
        $lineNet = $this->amountFromJson($line->amounts, 'I-171');
        if ($lineNet !== null) {
            return $this->formatAmount($lineNet);
        }

        $unitPrice = $this->amountFromJson($line->amounts, 'I-183') ?? '0.000';
        $quantity = (string) ($line->quantity ?? '0');
        $discountRate = (string) ($line->discount_rate ?? '0');

        $grossAmount = bcmul($quantity, $unitPrice, 3);
        $discountAmount = '0.000';

        if (bccomp($discountRate, '0', 2) > 0) {
            $discountAmount = bcdiv(bcmul($grossAmount, $discountRate, 3), '100', 3);
        }

        return bcsub($grossAmount, $discountAmount, 3);
    }

    private function amountFromJson(?array $amounts, string $code): ?string
    {
        if (empty($amounts)) {
            return null;
        }

        foreach ($amounts as $amount) {
            if (($amount['amount_type_code'] ?? null) === $code) {
                return (string) ($amount['amount'] ?? '0.000');
            }
        }

        return null;
    }

    private function formatAmount(string $amount): string
    {
        return number_format((float) $amount, 3, '.', '');
    }
}
