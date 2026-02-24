<?php

namespace App\Http\Controllers;

use App\Http\Soap\SoapHandler;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SoapServer;
use SoapFault;

class SoapController extends Controller
{
    private string $wsdlPath;

    public function __construct(private InvoiceService $invoiceService)
    {
        $this->wsdlPath = public_path('wsdl/elfatoora.wsdl');
    }

    /**
     * Serve the WSDL definition.
     */
    public function wsdl(): Response
    {
        return response(file_get_contents($this->wsdlPath), 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }

    /**
     * Handle all SOAP requests.
     */
    public function handle(Request $request): Response
    {
        // PHP's SoapServer requires output buffering
        ob_start();

        $server = new SoapServer($this->wsdlPath, [
            'encoding'   => 'UTF-8',
            'uri'        => 'http://elfatoora.tn/invoicing/v1',
            'soap_version' => SOAP_1_1,
        ]);

        $server->setObject(new SoapHandler($this->invoiceService));

        try {
            $server->handle($request->getContent());
        } catch (\Throwable $e) {
            ob_end_clean();
            return response(
                $this->buildFaultXml('SERVER', $e->getMessage()),
                500,
                ['Content-Type' => 'text/xml; charset=utf-8']
            );
        }

        $responseXml = ob_get_clean();

        return response($responseXml, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }

    private function buildFaultXml(string $code, string $message): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
  <SOAP-ENV:Body>
    <SOAP-ENV:Fault>
      <faultcode>' . htmlspecialchars($code) . '</faultcode>
      <faultstring>' . htmlspecialchars($message) . '</faultstring>
    </SOAP-ENV:Fault>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
    }
}