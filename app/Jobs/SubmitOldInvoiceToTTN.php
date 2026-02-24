<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\OldInvoiceStatus;
use App\Exceptions\TTNSubmissionException;
use App\Models\OldInvoice;
use App\Services\TeifXmlBuilder;
use App\Services\TTNApiClient;
use App\Services\XadesSignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitOldInvoiceToTTN implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly OldInvoice $oldinvoice,
    ) {
    }

    public function handle(
        TeifXmlBuilder $xmlBuilder,
        XadesSignatureService $signatureService,
        TTNApiClient $ttnClient,
    ): void {
        try {
            // Build and sign XML if not already signed
            if (empty($this->oldinvoice->signed_xml)) {
                $unsignedXml = $xmlBuilder->build($this->oldinvoice);
                $signedXml = $signatureService->sign($unsignedXml);
                $this->oldinvoice->update(['signed_xml' => $signedXml]);
                $this->oldinvoice->transitionTo(OldInvoiceStatus::SIGNED);
            }

            // Submit to TTN
            $result = $ttnClient->submit($this->oldinvoice, $this->oldinvoice->signed_xml);

            $this->oldinvoice->update([
                'ref_ttn_val' => $result['ref_ttn_val'],
                'cev_qr_content' => $result['cev'],
                'submitted_at' => now(),
            ]);

            $this->oldinvoice->transitionTo(OldInvoiceStatus::SUBMITTED);

            if (strtolower($result['status']) === 'accepted') {
                $this->oldinvoice->update(['accepted_at' => now()]);
                $this->oldinvoice->transitionTo(OldInvoiceStatus::ACCEPTED);
            }

            Log::info('OldInvoice submitted to TTN', [
                'oldinvoice_id' => $this->oldinvoice->id,
                'ref_ttn_val' => $result['ref_ttn_val'],
            ]);
        } catch (TTNSubmissionException $e) {
            Log::error('TTN submission job failed', [
                'oldinvoice_id' => $this->oldinvoice->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e; // Will trigger retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('TTN submission permanently failed', [
            'oldinvoice_id' => $this->oldinvoice->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
