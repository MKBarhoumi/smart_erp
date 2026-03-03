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
            'partners',
            'lines',
            'taxes',
            'creator',
        ]);

        $settings = \App\Models\CompanySetting::first();

        // Get sender and receiver
        $sender = $invoice->partners->where('function_code', 'I-62')->first();
        $receiver = $invoice->partners->where('function_code', 'I-64')->first();

        // Generate QR code if CEV content exists
        $qrCode = null;
        if (!empty($invoice->ref_cev)) {
            $qrCode = base64_encode(
                QrCode::format('svg')
                    ->size(120)
                    ->generate($invoice->ref_cev)
            );
        }

        $amountInWords = $this->amountInWords->convert(
            number_format((float) $invoice->total_ttc, 3, '.', '')
        );

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
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

    /**
     * Generate and save PDF to storage, return path.
     */
    public function generateAndStore(Invoice $invoice): string
    {
        $pdfContent = $this->generate($invoice);
        $filename = "invoices/{$invoice->document_identifier}.pdf";

        \Illuminate\Support\Facades\Storage::disk('local')->put($filename, $pdfContent);

        return $filename;
    }
}
