<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;

class InvoiceWithoutTvaXmlService
{
    public function __construct(
        private readonly InvoiceWithoutTvaExportTransformer $transformer,
        private readonly TeifXmlBuilder $xmlBuilder,
    ) {
    }

    /**
     * Generate TEIF XML for the given invoice without TVA.
     */
    public function generate(Invoice $invoice): string
    {
        $exportInvoice = $this->transformer->transform($invoice);

        return $this->xmlBuilder->build($exportInvoice);
    }
}
