<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Generates PDF invoices with QR code for CEV.
 */
class InvoicePdfService
{
    public function __construct(
        private readonly AmountInWordsService $amountInWords,
    ) {
    }

    /**
     * Generate a PDF for the given invoice.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing([
            'customer',
            'lines',
            'taxLines',
            'allowances',
            'payments',
        ]);

        $settings = $invoice->companySetting ?? \App\Models\CompanySetting::firstOrFail();

        $qrCode = null;
        if (!empty($invoice->cev_qr_content)) {
            $qrCode = base64_encode(
                QrCode::format('svg')
                    ->size(120)
                    ->generate($invoice->cev_qr_content)
            );
        }

        $amountInWords = $this->amountInWords->convert(
            number_format((float) $invoice->total_ttc, 3, '.', '')
        );

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'settings' => $settings,
            'qrCode' => $qrCode,
            'amountInWords' => $amountInWords,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Generate and save PDF to storage, return path.
     */
    public function generateAndStore(Invoice $invoice): string
    {
        $pdfContent = $this->generate($invoice);
        $filename = "invoices/{$invoice->invoice_number}.pdf";

        \Illuminate\Support\Facades\Storage::disk('local')->put($filename, $pdfContent);

        return $filename;
    }
}
