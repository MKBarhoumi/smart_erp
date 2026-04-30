<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoiceTax;

class InvoiceWithoutTvaExportTransformer
{
    public function __construct(
        private readonly InvoiceWithoutTvaCalculationService $calculationService,
    ) {
    }

    /**
     * Build a detached in-memory invoice snapshot for no-TVA exports.
     */
    public function transform(Invoice $invoice): Invoice
    {
        $invoice->loadMissing([
            'partners',
            'lines',
            'taxes',
            'creator',
        ]);

        $totals = $this->calculationService->calculate($invoice);

        $exportInvoice = clone $invoice;
        $exportInvoice->setAttribute('invoice_amounts', [
            ['amount_type_code' => 'I-176', 'amount' => $totals['total_ht'], 'currency' => 'TND'],
            ['amount_type_code' => 'I-181', 'amount' => $totals['total_tva'], 'currency' => 'TND'],
            ['amount_type_code' => 'I-180', 'amount' => $totals['total_ttc'], 'currency' => 'TND'],
        ]);

        $exportLines = $invoice->lines->map(function (InvoiceLine $line) {
            $lineClone = clone $line;
            $lineClone->tax_rate = '0';

            return $lineClone;
        });

        $exportInvoice->setRelation('lines', $exportLines);

        $taxLine = new InvoiceTax();
        $taxLine->tax_type_code = 'I-1602';
        $taxLine->tax_type_name = 'TVA';
        $taxLine->tax_rate = '0';
        $taxLine->amounts = [
            ['amount_type_code' => 'I-177', 'amount' => $totals['taxable_amount'], 'currency' => 'TND'],
            ['amount_type_code' => 'I-178', 'amount' => '0.000', 'currency' => 'TND'],
        ];

        $exportInvoice->setRelation('taxes', collect([$taxLine]));

        return $exportInvoice;
    }
}
