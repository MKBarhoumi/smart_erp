<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoicePartner;
use App\Models\InvoiceLine;
use App\Models\InvoiceTax;
use Illuminate\Support\Facades\DB;
use Exception;

class InvoiceService
{
    public function __construct(
        private TeifXmlParser   $parser,
        private TeifXmlBuilder  $builder,
        private TeifXsdValidator $validator
    ) {}

    /**
     * Submit a new invoice from TEIF XML.
     */
    public function submitInvoice(string $teifXml, bool $withSignature): array
    {
        // 1. Validate against XSD
        $validation = $this->validator->validate($teifXml, $withSignature);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException(
                'XSD validation failed: ' . implode('; ', $validation['errors'])
            );
        }

        // 2. Parse XML
        $data = $this->parser->parse($teifXml);

        // 3. Persist
        return DB::transaction(function () use ($data, $withSignature) {
            $invoice = Invoice::create([
                'version'             => $data['version'],
                'controlling_agency'  => $data['controlling_agency'],
                'sender_identifier'   => $data['header']['sender_identifier'],
                'sender_type'         => $data['header']['sender_type'],
                'receiver_identifier' => $data['header']['receiver_identifier'],
                'receiver_type'       => $data['header']['receiver_type'],
                'document_identifier' => $data['body']['bgm']['document_identifier'],
                'document_type_code'  => $data['body']['bgm']['document_type_code'],
                'document_type_name'  => $data['body']['bgm']['document_type_name'],
                'dates'               => $data['body']['dtm'],
                'payment_section'     => $data['body']['payment_section'],
                'free_texts'          => $data['body']['free_texts'],
                'special_conditions'  => $data['body']['special_conditions'],
                'loc_section'         => $data['body']['loc_section'],
                'invoice_amounts'     => $data['body']['invoice_amounts'],
                'invoice_allowances'  => $data['body']['invoice_allowances'],
                'ref_ttn_id'          => $data['ref_ttn_val']['ref_id'] ?? null,
                'ref_ttn_value'       => $data['ref_ttn_val']['value'] ?? null,
                'ref_cev'             => $data['ref_ttn_val']['ref_cev'] ?? null,
                'ref_ttn_dates'       => $data['ref_ttn_val']['dates'] ?? null,
                'signatures'          => $data['signatures'],
                'status'              => empty($data['signatures']) ? 'draft' : 'signed',
            ]);

            // Partners
            foreach ($data['body']['partners'] as $partnerData) {
                $addr = $partnerData['addresses'][0] ?? [];
                InvoicePartner::create([
                    'invoice_id'              => $invoice->id,
                    'function_code'           => $partnerData['function_code'],
                    'partner_identifier'      => $partnerData['partner_identifier'],
                    'partner_identifier_type' => $partnerData['partner_identifier_type'],
                    'partner_name'            => $partnerData['partner_name'],
                    'partner_name_type'       => $partnerData['partner_name_type'],
                    'address_description'     => $addr['description'] ?? null,
                    'street'                  => $addr['street'] ?? null,
                    'city'                    => $addr['city'] ?? null,
                    'postal_code'             => $addr['postal_code'] ?? null,
                    'country'                 => $addr['country'] ?? null,
                    'country_code_list'       => $addr['country_code_list'] ?? null,
                    'address_lang'            => $addr['lang'] ?? null,
                    'locations'               => $partnerData['locations'],
                    'references'              => $partnerData['references'],
                    'contacts'                => $partnerData['contacts'],
                ]);
            }

            // Lines
            foreach ($data['body']['lines'] as $i => $lineData) {
                $this->createLine($invoice->id, $lineData, null, $i);
            }

            // Taxes
            foreach ($data['body']['invoice_taxes'] as $taxData) {
                InvoiceTax::create([
                    'invoice_id'    => $invoice->id,
                    'tax_type_code' => $taxData['tax_type_code'],
                    'tax_type_name' => $taxData['tax_type_name'],
                    'tax_category'  => $taxData['tax_category'],
                    'tax_rate'      => $taxData['tax_rate'],
                    'amounts'       => $taxData['amounts'],
                ]);
            }

            return [
                'invoice_id' => $invoice->id,
                'status'     => $invoice->status,
                'message'    => 'Invoice submitted successfully.',
            ];
        });
    }

    private function createLine(
        int $invoiceId,
        array $lineData,
        ?int $parentId,
        int $order
    ): InvoiceLine {
        $line = InvoiceLine::create([
            'invoice_id'      => $invoiceId,
            'parent_line_id'  => $parentId,
            'item_identifier' => $lineData['item_identifier'],
            'item_code'       => $lineData['item_code'],
            'item_description'=> $lineData['item_description'],
            'item_lang'       => $lineData['item_lang'],
            'api_details'     => $lineData['api_details'],
            'quantity'        => $lineData['quantity'],
            'measurement_unit'=> $lineData['measurement_unit'],
            'dates'           => $lineData['dates'],
            'tax_type_code'   => $lineData['tax_type_code'],
            'tax_type_name'   => $lineData['tax_type_name'],
            'tax_category'    => $lineData['tax_category'],
            'tax_rate'        => $lineData['tax_rate'],
            'tax_rate_basis'  => $lineData['tax_rate_basis'],
            'allowances'      => $lineData['allowances'],
            'amounts'         => $lineData['amounts'],
            'free_texts'      => $lineData['free_texts'],
            'sort_order'      => $order,
        ]);

        foreach ($lineData['sub_lines'] ?? [] as $j => $subLine) {
            $this->createLine($invoiceId, $subLine, $line->id, $j);
        }

        return $line;
    }

    /**
     * Get invoice and build TEIF XML.
     */
    public function getInvoiceXml(int $id): array
    {
        $invoice = Invoice::with(['partners', 'lines.subLines', 'taxes'])
                          ->findOrFail($id);

        $xml = $this->builder->build($invoice);

        return [
            'teif_xml' => $xml,
            'status'   => $invoice->status,
        ];
    }

    /**
     * Validate an existing invoice against XSD.
     */
    public function validateInvoice(int $id): array
    {
        $invoice = Invoice::with(['partners', 'lines', 'taxes'])->findOrFail($id);
        $xml     = $this->builder->build($invoice);
        $withSig = !empty($invoice->signatures);

        return $this->validator->validate($xml, $withSig);
    }

    /**
     * List invoices with optional filters.
     */
    public function listInvoices(array $filters): array
    {
        $query = Invoice::query();

        if (!empty($filters['sender_identifier'])) {
            $query->where('sender_identifier', $filters['sender_identifier']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $page    = max(1, (int)($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int)($filters['per_page'] ?? 20)));

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'invoices' => $paginated->items(),
            'total'    => $paginated->total(),
        ];
    }

    /**
     * Update invoice from new TEIF XML.
     */
    public function updateInvoice(int $id, string $teifXml): array
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'validated') {
            throw new \LogicException('Cannot update a validated invoice.');
        }

        $validation = $this->validator->validate(
            $teifXml,
            str_contains($teifXml, 'ds:Signature')
        );

        if (!$validation['valid']) {
            throw new \InvalidArgumentException(
                'XSD validation failed: ' . implode('; ', $validation['errors'])
            );
        }

        $data = $this->parser->parse($teifXml);

        DB::transaction(function () use ($invoice, $data) {
            $invoice->partners()->delete();
            $invoice->lines()->delete();
            $invoice->taxes()->delete();

            $invoice->update([
                'document_identifier' => $data['body']['bgm']['document_identifier'],
                'document_type_code'  => $data['body']['bgm']['document_type_code'],
                'document_type_name'  => $data['body']['bgm']['document_type_name'],
                'dates'               => $data['body']['dtm'],
                'invoice_amounts'     => $data['body']['invoice_amounts'],
                'signatures'          => $data['signatures'],
                'status'              => empty($data['signatures']) ? 'draft' : 'signed',
            ]);

            // Re-create partners, lines, taxes
            foreach ($data['body']['partners'] as $p) {
                $addr = $p['addresses'][0] ?? [];
                InvoicePartner::create([
                    'invoice_id'              => $invoice->id,
                    'function_code'           => $p['function_code'],
                    'partner_identifier'      => $p['partner_identifier'],
                    'partner_identifier_type' => $p['partner_identifier_type'],
                    'partner_name'            => $p['partner_name'],
                    'partner_name_type'       => $p['partner_name_type'],
                    'address_description'     => $addr['description'] ?? null,
                    'street'                  => $addr['street'] ?? null,
                    'city'                    => $addr['city'] ?? null,
                    'postal_code'             => $addr['postal_code'] ?? null,
                    'country'                 => $addr['country'] ?? null,
                    'country_code_list'       => $addr['country_code_list'] ?? null,
                    'address_lang'            => $addr['lang'] ?? null,
                    'locations'               => $p['locations'],
                    'references'              => $p['references'],
                    'contacts'                => $p['contacts'],
                ]);
            }

            foreach ($data['body']['lines'] as $i => $lineData) {
                $this->createLine($invoice->id, $lineData, null, $i);
            }

            foreach ($data['body']['invoice_taxes'] as $taxData) {
                InvoiceTax::create([
                    'invoice_id'    => $invoice->id,
                    'tax_type_code' => $taxData['tax_type_code'],
                    'tax_type_name' => $taxData['tax_type_name'],
                    'tax_category'  => $taxData['tax_category'],
                    'tax_rate'      => $taxData['tax_rate'],
                    'amounts'       => $taxData['amounts'],
                ]);
            }
        });

        return ['success' => true, 'message' => 'Invoice updated successfully.'];
    }

    /**
     * Soft delete an invoice.
     */
    public function deleteInvoice(int $id): array
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'validated') {
            throw new \LogicException('Cannot delete a validated invoice.');
        }

        $invoice->delete();
        return ['success' => true, 'message' => 'Invoice deleted.'];
    }
}