<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Exceptions\TTNSubmissionException;
use App\Models\Invoice;
use App\Services\TeifXmlBuilder;
use App\Services\TTNApiClient;
use App\Services\XadesSignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitInvoiceToTTN implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly Invoice $invoice,
    ) {
    }

    public function handle(
        TeifXmlBuilder $xmlBuilder,
        XadesSignatureService $signatureService,
        TTNApiClient $ttnClient,
    ): void {
        try {
            // Build and sign XML if not already signed
            if (empty($this->invoice->signed_xml)) {
                $unsignedXml = $xmlBuilder->build($this->invoice);
                $signedXml = $signatureService->sign($unsignedXml);
                $this->invoice->update(['signed_xml' => $signedXml]);
                $this->invoice->transitionTo(InvoiceStatus::SIGNED);
            }

            // Submit to TTN
            $result = $ttnClient->submit($this->invoice, $this->invoice->signed_xml);

            $this->invoice->update([
                'ref_ttn_val' => $result['ref_ttn_val'],
                'cev_qr_content' => $result['cev'],
                'submitted_at' => now(),
            ]);

            $this->invoice->transitionTo(InvoiceStatus::SUBMITTED);

            if (strtolower($result['status']) === 'accepted') {
                $this->invoice->update(['accepted_at' => now()]);
                $this->invoice->transitionTo(InvoiceStatus::ACCEPTED);
            }

            Log::info('Invoice submitted to TTN', [
                'invoice_id' => $this->invoice->id,
                'ref_ttn_val' => $result['ref_ttn_val'],
            ]);
        } catch (TTNSubmissionException $e) {
            Log::error('TTN submission job failed', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e; // Will trigger retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('TTN submission permanently failed', [
            'invoice_id' => $this->invoice->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
