<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoiceWithoutTvaPdfService
{
    public function __construct(
        private readonly InvoiceWithoutTvaExportTransformer $transformer,
        private readonly AmountInWordsService $amountInWords,
    ) {
    }

    /**
     * Generate a PDF for the given invoice without TVA.
     */
    public function generate(Invoice $invoice): string
    {
        $exportInvoice = $this->transformer->transform($invoice);
        $settings = \App\Models\CompanySetting::first();

        $sender = $exportInvoice->partners->where('function_code', 'I-62')->first();
        $receiver = $exportInvoice->partners->where('function_code', 'I-64')->first();

        $qrCode = null;

        $amountInWords = $this->amountInWords->convert(
            number_format((float) $exportInvoice->total_ttc, 3, '.', '')
        );

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $exportInvoice,
            'sender' => $sender,
            'receiver' => $receiver,
            'company' => $settings,
            'settings' => $settings,
            'qrCode' => $qrCode,
            'amountInWords' => $amountInWords,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }
}
