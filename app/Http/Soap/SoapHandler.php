<?php

namespace App\Http\Soap;

use App\Services\InvoiceService;
use SoapFault;

/**
 * SOAP operation handler - methods must match WSDL operation names exactly.
 */
class SoapHandler
{
    public function __construct(private InvoiceService $service) {}

    public function SubmitInvoice(object $params): object
    {
        try {
            $result = $this->service->submitInvoice(
                (string) $params->teifXml,
                (bool)   $params->withSignature
            );
            return (object) [
                'invoiceId' => $result['invoice_id'],
                'status'    => $result['status'],
                'message'   => $result['message'],
            ];
        } catch (\InvalidArgumentException $e) {
            throw new SoapFault('CLIENT', $e->getMessage());
        } catch (\Throwable $e) {
            throw new SoapFault('SERVER', 'Internal error: ' . $e->getMessage());
        }
    }

    public function GetInvoice(object $params): object
    {
        try {
            $result = $this->service->getInvoiceXml((int) $params->invoiceId);
            return (object) [
                'teifXml' => $result['teif_xml'],
                'status'  => $result['status'],
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new SoapFault('CLIENT', 'Invoice not found.');
        } catch (\Throwable $e) {
            throw new SoapFault('SERVER', $e->getMessage());
        }
    }

    public function ValidateInvoice(object $params): object
    {
        try {
            $result = $this->service->validateInvoice((int) $params->invoiceId);
            return (object) [
                'valid'  => $result['valid'],
                'errors' => implode("\n", $result['errors']),
            ];
        } catch (\Throwable $e) {
            throw new SoapFault('SERVER', $e->getMessage());
        }
    }

    public function ListInvoices(object $params): object
    {
        try {
            $result = $this->service->listInvoices([
                'sender_identifier' => $params->senderIdentifier ?? null,
                'status'            => $params->status ?? null,
                'page'              => $params->page ?? 1,
                'per_page'          => $params->perPage ?? 20,
            ]);
            return (object) [
                'invoicesJson' => json_encode($result['invoices']),
                'total'        => $result['total'],
            ];
        } catch (\Throwable $e) {
            throw new SoapFault('SERVER', $e->getMessage());
        }
    }

    public function UpdateInvoice(object $params): object
    {
        try {
            $result = $this->service->updateInvoice(
                (int)    $params->invoiceId,
                (string) $params->teifXml
            );
            return (object) $result;
        } catch (\InvalidArgumentException $e) {
            throw new SoapFault('CLIENT', $e->getMessage());
        } catch (\LogicException $e) {
            throw new SoapFault('CLIENT', $e->getMessage());
        } catch (\Throwable $e) {
            throw new SoapFault('SERVER', $e->getMessage());
        }
    }

    public function DeleteInvoice(object $params): object
    {
        try {
            $result = $this->service->deleteInvoice((int) $params->invoiceId);
            return (object) $result;
        } catch (\LogicException $e) {
            throw new SoapFault('CLIENT', $e->getMessage());
        } catch (\Throwable $e) {
            throw new SoapFault('SERVER', $e->getMessage());
        }
    }

    public function SignInvoice(object $params): object
    {
        // Signature logic is advanced - return stub
        return (object) [
            'signedXml' => '',
            'success'   => false,
            'message'   => 'Signing not yet implemented in this demo.',
        ];
    }

    public function VerifySignature(object $params): object
    {
        return (object) [
            'valid'   => false,
            'message' => 'Signature verification not yet implemented.',
        ];
    }
}