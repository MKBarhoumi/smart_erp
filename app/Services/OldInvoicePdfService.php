<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OldInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Generates PDF oldinvoices with QR code for CEV.
 */
class OldInvoicePdfService
{
    public function __construct(
        private readonly AmountInWordsService $amountInWords,
    ) {
    }

    /**
     * Generate a PDF for the given oldinvoice.
     */
    public function generate(OldInvoice $oldinvoice): string
    {
        $oldinvoice->loadMissing([
            'customer',
            'lines',
            'taxLines',
            'allowances',
            'payments',
        ]);

        $settings = $oldinvoice->companySetting ?? \App\Models\CompanySetting::firstOrFail();

        $qrCode = null;
        if (!empty($oldinvoice->cev_qr_content)) {
            $qrCode = base64_encode(
                QrCode::format('svg')
                    ->size(120)
                    ->generate($oldinvoice->cev_qr_content)
            );
        }

        $amountInWords = $this->amountInWords->convert(
            number_format((float) $oldinvoice->total_ttc, 3, '.', '')
        );

        $pdf = Pdf::loadView('pdf.oldinvoice', [
            'oldinvoice' => $oldinvoice,
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
    public function generateAndStore(OldInvoice $oldinvoice): string
    {
        $pdfContent = $this->generate($oldinvoice);
        $filename = "oldinvoices/{$oldinvoice->oldinvoice_number}.pdf";

        \Illuminate\Support\Facades\Storage::disk('local')->put($filename, $pdfContent);

        return $filename;
    }
}
