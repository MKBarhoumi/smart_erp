<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TTNSubmissionException;
use App\Models\Invoice;
use App\Models\TTNSubmissionLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTPS client for Tunisia TradeNet (TTN) El Fatoora API.
 * Handles invoice submission, status polling, and response parsing.
 */
class TTNApiClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ttn.base_url', 'https://api.tradenet.com.tn/elfatoora'), '/');
        $this->apiKey = config('services.ttn.api_key', '');
        $this->timeout = (int) config('services.ttn.timeout', 30);
    }

    /**
     * Submit a signed TEIF XML to TTN.
     *
     * @return array{ref_ttn_val: string, cev: string, status: string, response_raw: string}
     *
     * @throws TTNSubmissionException
     */
    public function submit(Invoice $invoice, string $signedXml): array
    {
        $log = TTNSubmissionLog::create([
            'invoice_id' => $invoice->id,
            'direction' => 'outbound',
            'payload' => $signedXml,
            'status' => 'pending',
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/xml',
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/xml',
                ])
                ->withBody($signedXml, 'application/xml')
                ->post("{$this->baseUrl}/invoices");

            $responseBody = $response->body();

            $log->update([
                'response' => $responseBody,
                'http_status' => $response->status(),
            ]);

            if (!$response->successful()) {
                $errorMessage = $this->extractErrorMessage($responseBody);
                $log->update(['status' => 'error']);

                throw new TTNSubmissionException(
                    "TTN submission failed (HTTP {$response->status()}): {$errorMessage}"
                );
            }

            $parsed = $this->parseSuccessResponse($responseBody);

            $log->update([
                'status' => 'success',
                'ref_ttn_val' => $parsed['ref_ttn_val'],
            ]);

            return $parsed;
        } catch (TTNSubmissionException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('TTN submission error', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => 'error',
                'response' => $e->getMessage(),
            ]);

            throw new TTNSubmissionException(
                "TTN communication error: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Check submission status by TTN reference.
     *
     * @return array{status: string, ref_ttn_val: string, cev: string, reason: string|null}
     *
     * @throws TTNSubmissionException
     */
    public function checkStatus(string $refTtnVal): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/xml',
                ])
                ->get("{$this->baseUrl}/invoices/{$refTtnVal}/status");

            if (!$response->successful()) {
                throw new TTNSubmissionException(
                    "TTN status check failed (HTTP {$response->status()})"
                );
            }

            return $this->parseStatusResponse($response->body());
        } catch (TTNSubmissionException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new TTNSubmissionException(
                "TTN status check error: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Download the CEV (Certificat Electronique de Vérification) QR content.
     *
     * @throws TTNSubmissionException
     */
    public function downloadCev(string $refTtnVal): string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}/invoices/{$refTtnVal}/cev");

            if (!$response->successful()) {
                throw new TTNSubmissionException(
                    "CEV download failed (HTTP {$response->status()})"
                );
            }

            return $response->json('cev_qr_content', '');
        } catch (TTNSubmissionException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new TTNSubmissionException(
                "CEV download error: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Parse a successful submission response.
     *
     * @return array{ref_ttn_val: string, cev: string, status: string, response_raw: string}
     */
    private function parseSuccessResponse(string $xml): array
    {
        $dom = new \DOMDocument();
        $loaded = @$dom->loadXML($xml);

        if (!$loaded) {
            // Try JSON fallback
            $json = json_decode($xml, true);
            if (is_array($json)) {
                return [
                    'ref_ttn_val' => $json['refTtnVal'] ?? $json['ref_ttn_val'] ?? '',
                    'cev' => $json['cev'] ?? '',
                    'status' => $json['status'] ?? 'accepted',
                    'response_raw' => $xml,
                ];
            }

            throw new TTNSubmissionException('Unable to parse TTN response.');
        }

        $xpath = new \DOMXPath($dom);

        return [
            'ref_ttn_val' => $this->extractNodeValue($xpath, '//RefTtnVal') ?: '',
            'cev' => $this->extractNodeValue($xpath, '//CEV') ?: '',
            'status' => $this->extractNodeValue($xpath, '//Status') ?: 'accepted',
            'response_raw' => $xml,
        ];
    }

    /**
     * Parse a status check response.
     *
     * @return array{status: string, ref_ttn_val: string, cev: string, reason: string|null}
     */
    private function parseStatusResponse(string $xml): array
    {
        $dom = new \DOMDocument();
        @$dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);

        return [
            'status' => $this->extractNodeValue($xpath, '//Status') ?: 'unknown',
            'ref_ttn_val' => $this->extractNodeValue($xpath, '//RefTtnVal') ?: '',
            'cev' => $this->extractNodeValue($xpath, '//CEV') ?: '',
            'reason' => $this->extractNodeValue($xpath, '//Reason'),
        ];
    }

    private function extractNodeValue(\DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        if ($nodes !== false && $nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }

        return null;
    }

    private function extractErrorMessage(string $responseBody): string
    {
        // Try XML
        $dom = new \DOMDocument();
        if (@$dom->loadXML($responseBody)) {
            $xpath = new \DOMXPath($dom);
            $msg = $this->extractNodeValue($xpath, '//Error') ?? $this->extractNodeValue($xpath, '//Message');
            if ($msg) {
                return $msg;
            }
        }

        // Try JSON
        $json = json_decode($responseBody, true);
        if (is_array($json)) {
            return $json['error'] ?? $json['message'] ?? 'Unknown error';
        }

        return substr($responseBody, 0, 500);
    }
}
