<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DocumentTypeCode;
use App\Enums\IdentifierType;
use App\Enums\InvoiceStatus;
use App\Enums\TaxTypeCode;
use App\Exceptions\InvoiceStateException;
use App\Exceptions\SignatureException;
use App\Exceptions\TeifValidationException;
use App\Exceptions\TTNSubmissionException;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePartner;
use App\Models\InvoiceTax;
use App\Models\Product;
use App\Services\TeifXmlBuilder;
use App\Services\XadesSignatureService;
use App\Services\TTNApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly TeifXmlBuilder $xmlBuilder,
        private readonly XadesSignatureService $signatureService,
        private readonly TTNApiClient $ttnClient,
    ) {
    }

    /**
     * Display a listing of invoices.
     */
    public function index(): Response
    {
        $invoices = Invoice::with(['partners' => function ($q) {
                $q->whereIn('function_code', ['I-62', 'I-64']);
            }])
            ->when(request('search'), function ($query, $search) {
                $query->where('document_identifier', 'like', "%{$search}%")
                    ->orWhere('sender_identifier', 'like', "%{$search}%")
                    ->orWhere('receiver_identifier', 'like', "%{$search}%");
            })
            ->when(request('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when(request('date_from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when(request('date_to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function ($invoice) {
                // Add computed attributes for the list
                return [
                    'id' => $invoice->id,
                    'document_identifier' => $invoice->document_identifier,
                    'document_type_code' => $invoice->document_type_code,
                    'document_type_name' => $invoice->document_type_name,
                    'sender_identifier' => $invoice->sender_identifier,
                    'receiver_identifier' => $invoice->receiver_identifier,
                    'sender_name' => $invoice->partners->where('function_code', 'I-62')->first()?->partner_name,
                    'receiver_name' => $invoice->partners->where('function_code', 'I-64')->first()?->partner_name,
                    'invoice_date' => $invoice->invoice_date,
                    'total_ht' => $invoice->total_ht,
                    'total_tva' => $invoice->total_tva,
                    'total_ttc' => $invoice->total_ttc,
                    'status' => $invoice->status,
                    'created_at' => $invoice->created_at,
                ];
            });

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => request()->only('search', 'status', 'date_from', 'date_to'),
            'statuses' => collect(InvoiceStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(): Response
    {
        return Inertia::render('Invoices/Create', [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'identifier_value', 'identifier_type', 'street', 'city', 'postal_code', 'country_code']),
            'products' => Product::where('is_active', true)->orderBy('name')->get([
                'id', 'code', 'name', 'unit_price', 'unit_of_measure', 'tva_rate',
            ]),
            'documentTypes' => collect(DocumentTypeCode::cases())->map(fn ($d) => [
                'value' => $d->value,
                'label' => $d->label(),
            ]),
            'identifierTypes' => collect(IdentifierType::cases())->map(fn ($d) => [
                'value' => $d->value,
                'label' => $d->label(),
            ]),
            'taxTypes' => collect(TaxTypeCode::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'companySettings' => $this->getCompanySettings(),
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request) {
            // Generate document identifier
            $documentIdentifier = $this->generateDocumentIdentifier();

            // Build dates array
            $dates = [];
            if (!empty($validated['invoice_date'])) {
                $dates[] = [
                    'function_code' => 'I-31',
                    'format' => 'ddMMyy',
                    'value' => date('dmy', strtotime($validated['invoice_date'])),
                ];
            }
            if (!empty($validated['due_date'])) {
                $dates[] = [
                    'function_code' => 'I-35',
                    'format' => 'ddMMyy',
                    'value' => date('dmy', strtotime($validated['due_date'])),
                ];
            }

            // Calculate totals
            $totals = $this->calculateTotals($validated['lines']);

            // Build invoice amounts array
            $invoiceAmounts = [
                ['amount_type_code' => 'I-176', 'amount' => number_format($totals['total_ht'], 3, '.', ''), 'currency' => 'TND'],
                ['amount_type_code' => 'I-181', 'amount' => number_format($totals['total_tva'], 3, '.', ''), 'currency' => 'TND'],
                ['amount_type_code' => 'I-180', 'amount' => number_format($totals['total_ttc'], 3, '.', ''), 'currency' => 'TND'],
            ];

            // Create invoice
            $invoice = Invoice::create([
                'created_by' => $request->user()->id,
                'version' => '1.8.8',
                'controlling_agency' => 'TTN',
                'sender_identifier' => $validated['sender_identifier'],
                'sender_type' => $validated['sender_type'] ?? 'I-01',
                'receiver_identifier' => $validated['receiver_identifier'],
                'receiver_type' => $validated['receiver_type'] ?? 'I-01',
                'document_identifier' => $documentIdentifier,
                'document_type_code' => $validated['document_type_code'],
                'document_type_name' => DocumentTypeCode::from($validated['document_type_code'])->label(),
                'dates' => $dates,
                'invoice_amounts' => $invoiceAmounts,
                'notes' => $validated['notes'] ?? null,
                'status' => InvoiceStatus::DRAFT->value,
            ]);

            // Create sender partner
            InvoicePartner::create([
                'invoice_id' => $invoice->id,
                'function_code' => 'I-62',
                'partner_identifier' => $validated['sender_identifier'],
                'partner_identifier_type' => $validated['sender_type'] ?? 'I-01',
                'partner_name' => $validated['sender_name'],
                'partner_name_type' => 'Qualification',
                'address_description' => $validated['sender_address_description'] ?? null,
                'street' => $validated['sender_street'] ?? null,
                'city' => $validated['sender_city'] ?? null,
                'postal_code' => $validated['sender_postal_code'] ?? null,
                'country' => $validated['sender_country'] ?? 'TN',
                'country_code_list' => 'ISO_3166-1',
                'address_lang' => 'fr',
            ]);

            // Create receiver partner
            InvoicePartner::create([
                'invoice_id' => $invoice->id,
                'function_code' => 'I-64',
                'partner_identifier' => $validated['receiver_identifier'],
                'partner_identifier_type' => $validated['receiver_type'] ?? 'I-01',
                'partner_name' => $validated['receiver_name'],
                'partner_name_type' => 'Qualification',
                'address_description' => $validated['receiver_address_description'] ?? null,
                'street' => $validated['receiver_street'] ?? null,
                'city' => $validated['receiver_city'] ?? null,
                'postal_code' => $validated['receiver_postal_code'] ?? null,
                'country' => $validated['receiver_country'] ?? 'TN',
                'country_code_list' => 'ISO_3166-1',
                'address_lang' => 'fr',
            ]);

            // Create lines
            foreach ($validated['lines'] as $index => $lineData) {
                $lineAmounts = $this->calculateLineAmounts($lineData);

                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'item_identifier' => (string) ($index + 1),
                    'item_code' => $lineData['item_code'],
                    'item_description' => $lineData['item_description'],
                    'item_lang' => 'fr',
                    'quantity' => $lineData['quantity'],
                    'measurement_unit' => $lineData['unit_of_measure'] ?? 'UNIT',
                    'tax_type_code' => 'I-1602',
                    'tax_type_name' => 'TVA',
                    'tax_rate' => $lineData['tva_rate'],
                    'amounts' => $lineAmounts,
                    'sort_order' => $index,
                ]);
            }

            // Create tax summary
            foreach ($totals['tax_summary'] as $taxSummary) {
                InvoiceTax::create([
                    'invoice_id' => $invoice->id,
                    'tax_type_code' => $taxSummary['tax_type_code'],
                    'tax_type_name' => $taxSummary['tax_type_name'],
                    'tax_rate' => $taxSummary['tax_rate'],
                    'amounts' => [
                        ['amount_type_code' => 'I-177', 'amount' => number_format($taxSummary['taxable_amount'], 3, '.', ''), 'currency' => 'TND'],
                        ['amount_type_code' => 'I-178', 'amount' => number_format($taxSummary['tax_amount'], 3, '.', ''), 'currency' => 'TND'],
                    ],
                ]);
            }

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice created successfully.');
        });
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $invoice->load([
            'partners',
            'lines',
            'taxes',
            'creator:id,name',
        ]);

        $senderPartner = $invoice->partners->where('function_code', 'I-62')->first();
        $receiverPartner = $invoice->partners->where('function_code', 'I-64')->first();

        return Inertia::render('Invoices/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'document_identifier' => $invoice->document_identifier,
                'document_type_code' => $invoice->document_type_code,
                'document_type_name' => $invoice->document_type_name,
                'invoice_date' => $invoice->invoice_date,
                'status' => $invoice->status,
                'total_ht' => $invoice->total_ht,
                'total_tva' => $invoice->total_tva,
                'total_ttc' => $invoice->total_ttc,
                'notes' => $invoice->notes,
                'ref_ttn_value' => $invoice->ref_ttn_value,
                'ref_cev' => $invoice->ref_cev,
                'signed_xml' => $invoice->signed_xml,
                'submitted_at' => $invoice->submitted_at,
                'accepted_at' => $invoice->accepted_at,
                'rejection_reason' => $invoice->rejection_reason,
                'created_at' => $invoice->created_at,
                'creator' => $invoice->creator,
                'sender' => $senderPartner ? [
                    'identifier' => $senderPartner->partner_identifier,
                    'identifier_type' => $senderPartner->partner_identifier_type,
                    'name' => $senderPartner->partner_name,
                    'street' => $senderPartner->street,
                    'city' => $senderPartner->city,
                    'postal_code' => $senderPartner->postal_code,
                    'country' => $senderPartner->country,
                ] : null,
                'receiver' => $receiverPartner ? [
                    'identifier' => $receiverPartner->partner_identifier,
                    'identifier_type' => $receiverPartner->partner_identifier_type,
                    'name' => $receiverPartner->partner_name,
                    'street' => $receiverPartner->street,
                    'city' => $receiverPartner->city,
                    'postal_code' => $receiverPartner->postal_code,
                    'country' => $receiverPartner->country,
                ] : null,
                'lines' => $invoice->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'item_identifier' => $line->item_identifier,
                    'item_code' => $line->item_code,
                    'item_description' => $line->item_description,
                    'quantity' => $line->quantity,
                    'measurement_unit' => $line->measurement_unit,
                    'tax_rate' => $line->tax_rate,
                    'unit_price' => $this->getAmountFromJson($line->amounts, 'I-183'),
                    'line_net' => $this->getAmountFromJson($line->amounts, 'I-171'),
                ]),
                'taxes' => $invoice->taxes->map(fn ($tax) => [
                    'tax_type_code' => $tax->tax_type_code,
                    'tax_type_name' => $tax->tax_type_name,
                    'tax_rate' => $tax->tax_rate,
                    'taxable_amount' => $this->getAmountFromJson($tax->amounts, 'I-177'),
                    'tax_amount' => $this->getAmountFromJson($tax->amounts, 'I-178'),
                ]),
            ],
            'canEdit' => $invoice->isEditable(),
            'canDelete' => $invoice->isDeletable(),
            'canValidate' => $invoice->status === InvoiceStatus::DRAFT->value,
            'canSign' => $invoice->status === InvoiceStatus::VALIDATED->value,
            'canSubmit' => $invoice->status === InvoiceStatus::SIGNED->value,
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice): Response|RedirectResponse
    {
        if (!$invoice->isEditable()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'This invoice can no longer be edited.');
        }

        $invoice->load(['partners', 'lines']);

        $senderPartner = $invoice->partners->where('function_code', 'I-62')->first();
        $receiverPartner = $invoice->partners->where('function_code', 'I-64')->first();

        // Format dates from array
        $invoiceDate = null;
        $dueDate = null;
        foreach ($invoice->dates ?? [] as $date) {
            if ($date['function_code'] === 'I-31') {
                $value = $date['value'];
                if (strlen($value) === 6) {
                    $invoiceDate = '20' . substr($value, 4, 2) . '-' . substr($value, 2, 2) . '-' . substr($value, 0, 2);
                }
            }
            if ($date['function_code'] === 'I-35') {
                $value = $date['value'];
                if (strlen($value) === 6) {
                    $dueDate = '20' . substr($value, 4, 2) . '-' . substr($value, 2, 2) . '-' . substr($value, 0, 2);
                }
            }
        }

        return Inertia::render('Invoices/Edit', [
            'invoice' => [
                'id' => $invoice->id,
                'document_identifier' => $invoice->document_identifier,
                'document_type_code' => $invoice->document_type_code,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'notes' => $invoice->notes,
                'sender_identifier' => $invoice->sender_identifier,
                'sender_type' => $invoice->sender_type,
                'sender_name' => $senderPartner?->partner_name,
                'sender_street' => $senderPartner?->street,
                'sender_city' => $senderPartner?->city,
                'sender_postal_code' => $senderPartner?->postal_code,
                'sender_country' => $senderPartner?->country,
                'receiver_identifier' => $invoice->receiver_identifier,
                'receiver_type' => $invoice->receiver_type,
                'receiver_name' => $receiverPartner?->partner_name,
                'receiver_street' => $receiverPartner?->street,
                'receiver_city' => $receiverPartner?->city,
                'receiver_postal_code' => $receiverPartner?->postal_code,
                'receiver_country' => $receiverPartner?->country,
                'lines' => $invoice->lines->map(fn ($line) => [
                    'item_code' => $line->item_code,
                    'item_description' => $line->item_description,
                    'quantity' => $line->quantity,
                    'unit_of_measure' => $line->measurement_unit,
                    'unit_price' => $this->getAmountFromJson($line->amounts, 'I-183'),
                    'tva_rate' => $line->tax_rate,
                ]),
            ],
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'identifier_value', 'identifier_type', 'street', 'city', 'postal_code', 'country_code']),
            'products' => Product::where('is_active', true)->orderBy('name')->get([
                'id', 'code', 'name', 'unit_price', 'unit_of_measure', 'tva_rate',
            ]),
            'documentTypes' => collect(DocumentTypeCode::cases())->map(fn ($d) => [
                'value' => $d->value,
                'label' => $d->label(),
            ]),
            'identifierTypes' => collect(IdentifierType::cases())->map(fn ($d) => [
                'value' => $d->value,
                'label' => $d->label(),
            ]),
            'companySettings' => $this->getCompanySettings(),
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(StoreInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isEditable()) {
            return back()->with('error', 'This invoice can no longer be edited.');
        }

        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $invoice) {
            // Build dates array
            $dates = [];
            if (!empty($validated['invoice_date'])) {
                $dates[] = [
                    'function_code' => 'I-31',
                    'format' => 'ddMMyy',
                    'value' => date('dmy', strtotime($validated['invoice_date'])),
                ];
            }
            if (!empty($validated['due_date'])) {
                $dates[] = [
                    'function_code' => 'I-35',
                    'format' => 'ddMMyy',
                    'value' => date('dmy', strtotime($validated['due_date'])),
                ];
            }

            // Calculate totals
            $totals = $this->calculateTotals($validated['lines']);

            // Build invoice amounts array
            $invoiceAmounts = [
                ['amount_type_code' => 'I-176', 'amount' => number_format($totals['total_ht'], 3, '.', ''), 'currency' => 'TND'],
                ['amount_type_code' => 'I-181', 'amount' => number_format($totals['total_tva'], 3, '.', ''), 'currency' => 'TND'],
                ['amount_type_code' => 'I-180', 'amount' => number_format($totals['total_ttc'], 3, '.', ''), 'currency' => 'TND'],
            ];

            // Update invoice
            $invoice->update([
                'sender_identifier' => $validated['sender_identifier'],
                'sender_type' => $validated['sender_type'] ?? 'I-01',
                'receiver_identifier' => $validated['receiver_identifier'],
                'receiver_type' => $validated['receiver_type'] ?? 'I-01',
                'document_type_code' => $validated['document_type_code'],
                'document_type_name' => DocumentTypeCode::from($validated['document_type_code'])->label(),
                'dates' => $dates,
                'invoice_amounts' => $invoiceAmounts,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete old related data
            $invoice->partners()->delete();
            $invoice->allLines()->delete();
            $invoice->taxes()->delete();

            // Create sender partner
            InvoicePartner::create([
                'invoice_id' => $invoice->id,
                'function_code' => 'I-62',
                'partner_identifier' => $validated['sender_identifier'],
                'partner_identifier_type' => $validated['sender_type'] ?? 'I-01',
                'partner_name' => $validated['sender_name'],
                'partner_name_type' => 'Qualification',
                'street' => $validated['sender_street'] ?? null,
                'city' => $validated['sender_city'] ?? null,
                'postal_code' => $validated['sender_postal_code'] ?? null,
                'country' => $validated['sender_country'] ?? 'TN',
                'country_code_list' => 'ISO_3166-1',
                'address_lang' => 'fr',
            ]);

            // Create receiver partner
            InvoicePartner::create([
                'invoice_id' => $invoice->id,
                'function_code' => 'I-64',
                'partner_identifier' => $validated['receiver_identifier'],
                'partner_identifier_type' => $validated['receiver_type'] ?? 'I-01',
                'partner_name' => $validated['receiver_name'],
                'partner_name_type' => 'Qualification',
                'street' => $validated['receiver_street'] ?? null,
                'city' => $validated['receiver_city'] ?? null,
                'postal_code' => $validated['receiver_postal_code'] ?? null,
                'country' => $validated['receiver_country'] ?? 'TN',
                'country_code_list' => 'ISO_3166-1',
                'address_lang' => 'fr',
            ]);

            // Create lines
            foreach ($validated['lines'] as $index => $lineData) {
                $lineAmounts = $this->calculateLineAmounts($lineData);

                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'item_identifier' => (string) ($index + 1),
                    'item_code' => $lineData['item_code'],
                    'item_description' => $lineData['item_description'],
                    'item_lang' => 'fr',
                    'quantity' => $lineData['quantity'],
                    'measurement_unit' => $lineData['unit_of_measure'] ?? 'UNIT',
                    'tax_type_code' => 'I-1602',
                    'tax_type_name' => 'TVA',
                    'tax_rate' => $lineData['tva_rate'],
                    'amounts' => $lineAmounts,
                    'sort_order' => $index,
                ]);
            }

            // Create tax summary
            foreach ($totals['tax_summary'] as $taxSummary) {
                InvoiceTax::create([
                    'invoice_id' => $invoice->id,
                    'tax_type_code' => $taxSummary['tax_type_code'],
                    'tax_type_name' => $taxSummary['tax_type_name'],
                    'tax_rate' => $taxSummary['tax_rate'],
                    'amounts' => [
                        ['amount_type_code' => 'I-177', 'amount' => number_format($taxSummary['taxable_amount'], 3, '.', ''), 'currency' => 'TND'],
                        ['amount_type_code' => 'I-178', 'amount' => number_format($taxSummary['tax_amount'], 3, '.', ''), 'currency' => 'TND'],
                    ],
                ]);
            }

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice updated successfully.');
        });
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isDeletable()) {
            return back()->with('error', 'This invoice cannot be deleted.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Validate invoice (DRAFT → VALIDATED).
     */
    public function validateInvoice(Invoice $invoice): RedirectResponse
    {
        try {
            $invoice->transitionTo(InvoiceStatus::VALIDATED);
        } catch (InvoiceStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invoice validated successfully.');
    }

    /**
     * Sign invoice with XAdES-BES (VALIDATED → SIGNED).
     */
    public function sign(Invoice $invoice): RedirectResponse
    {
        try {
            // Build TEIF XML from invoice
            $unsignedXml = $this->xmlBuilder->build($invoice);

            // Sign XML
            $signedXml = $this->signatureService->sign($unsignedXml);

            $invoice->update(['signed_xml' => $signedXml]);
            $invoice->transitionTo(InvoiceStatus::SIGNED);
        } catch (TeifValidationException|SignatureException $e) {
            return back()->with('error', 'Signature error: ' . $e->getMessage());
        } catch (InvoiceStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invoice signed successfully.');
    }

    /**
     * Submit to TTN (SIGNED → SUBMITTED).
     */
    public function submit(Invoice $invoice): RedirectResponse
    {
        if (empty($invoice->signed_xml)) {
            return back()->with('error', 'The invoice must be signed before submission.');
        }

        try {
            // Note: TTNApiClient currently expects OldInvoice; adapter pattern or extension needed for Invoice
            $result = $this->ttnClient->submitToTTN($invoice, $invoice->signed_xml);

            $invoice->update([
                'ref_ttn_value' => $result['ref_ttn_val'] ?? null,
                'ref_cev' => $result['cev'] ?? null,
                'submitted_at' => now(),
            ]);

            $invoice->transitionTo(InvoiceStatus::SUBMITTED);

            // If immediate acceptance
            if (isset($result['status']) && strtolower($result['status']) === 'accepted') {
                $invoice->update(['accepted_at' => now()]);
                $invoice->transitionTo(InvoiceStatus::ACCEPTED);

                return back()->with('success', 'Invoice submitted and accepted by TTN.');
            }
        } catch (TTNSubmissionException $e) {
            return back()->with('error', 'TTN Error: ' . $e->getMessage());
        } catch (InvoiceStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invoice submitted to TTN successfully.');
    }

    /**
     * Download signed XML.
     */
    public function downloadXml(Invoice $invoice): RedirectResponse|HttpResponse
    {
        // Build XML from invoice data
        $xml = $invoice->signed_xml;

        if (empty($xml)) {
            // Generate unsigned XML
            try {
                $xml = $this->xmlBuilder->build($invoice);
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to generate XML: ' . $e->getMessage());
            }
        }

        $filename = str_replace(['/', '\\', ' '], '-', $invoice->document_identifier) . '.xml';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Duplicate an invoice.
     */
    public function duplicate(Invoice $invoice): RedirectResponse
    {
        return DB::transaction(function () use ($invoice) {
            $newDocId = $this->generateDocumentIdentifier();

            $newInvoice = $invoice->replicate([
                'document_identifier',
                'status',
                'ref_ttn_value',
                'ref_cev',
                'signed_xml',
                'submitted_at',
                'accepted_at',
                'rejection_reason',
            ]);

            $newInvoice->document_identifier = $newDocId;
            $newInvoice->status = InvoiceStatus::DRAFT->value;
            $newInvoice->created_by = auth()->id();
            $newInvoice->save();

            // Copy partners
            foreach ($invoice->partners as $partner) {
                $newPartner = $partner->replicate(['invoice_id']);
                $newPartner->invoice_id = $newInvoice->id;
                $newPartner->save();
            }

            // Copy lines
            foreach ($invoice->lines as $line) {
                $newLine = $line->replicate(['invoice_id']);
                $newLine->invoice_id = $newInvoice->id;
                $newLine->save();
            }

            // Copy taxes
            foreach ($invoice->taxes as $tax) {
                $newTax = $tax->replicate(['invoice_id']);
                $newTax->invoice_id = $newInvoice->id;
                $newTax->save();
            }

            return redirect()->route('invoices.edit', $newInvoice)
                ->with('success', 'Invoice duplicated as draft.');
        });
    }

    /**
     * Helper to generate document identifier
     */
    private function generateDocumentIdentifier(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? ((int) preg_replace('/[^0-9]/', '', $lastInvoice->document_identifier) % 100000) + 1 : 1;

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }

    /**
     * Calculate totals from lines
     */
    private function calculateTotals(array $lines): array
    {
        $totalHt = 0;
        $totalTva = 0;
        $taxSummary = [];

        foreach ($lines as $line) {
            $qty = floatval($line['quantity'] ?? 0);
            $price = floatval($line['unit_price'] ?? 0);
            $tvaRate = floatval($line['tva_rate'] ?? 0);

            $lineNet = $qty * $price;
            $lineTva = $lineNet * ($tvaRate / 100);

            $totalHt += $lineNet;
            $totalTva += $lineTva;

            // Aggregate by tax rate
            $rateKey = (string) $tvaRate;
            if (!isset($taxSummary[$rateKey])) {
                $taxSummary[$rateKey] = [
                    'tax_type_code' => 'I-1602',
                    'tax_type_name' => 'TVA',
                    'tax_rate' => $tvaRate,
                    'taxable_amount' => 0,
                    'tax_amount' => 0,
                ];
            }
            $taxSummary[$rateKey]['taxable_amount'] += $lineNet;
            $taxSummary[$rateKey]['tax_amount'] += $lineTva;
        }

        return [
            'total_ht' => $totalHt,
            'total_tva' => $totalTva,
            'total_ttc' => $totalHt + $totalTva,
            'tax_summary' => array_values($taxSummary),
        ];
    }

    /**
     * Calculate line amounts
     */
    private function calculateLineAmounts(array $lineData): array
    {
        $qty = floatval($lineData['quantity'] ?? 0);
        $price = floatval($lineData['unit_price'] ?? 0);
        $lineNet = $qty * $price;

        return [
            ['amount_type_code' => 'I-183', 'amount' => number_format($price, 3, '.', ''), 'currency' => 'TND'],
            ['amount_type_code' => 'I-171', 'amount' => number_format($lineNet, 3, '.', ''), 'currency' => 'TND'],
        ];
    }

    /**
     * Get amount from JSON array by type code
     */
    private function getAmountFromJson(?array $amounts, string $typeCode): string
    {
        if (!$amounts) return '0.000';

        foreach ($amounts as $amount) {
            if (($amount['amount_type_code'] ?? '') === $typeCode) {
                return $amount['amount'] ?? '0.000';
            }
        }

        return '0.000';
    }

    /**
     * Get company settings for pre-filling sender info
     */
    private function getCompanySettings(): array
    {
        $settings = \App\Models\CompanySetting::first();

        if (!$settings) {
            return [
                'identifier' => '',
                'name' => '',
                'street' => '',
                'city' => '',
                'postal_code' => '',
                'country' => 'TN',
            ];
        }

        return [
            'identifier' => $settings->matricule_fiscal ?? '',
            'name' => $settings->company_name ?? '',
            'street' => $settings->street ?? '',
            'city' => $settings->city ?? '',
            'postal_code' => $settings->postal_code ?? '',
            'country' => $settings->country_code ?? 'TN',
        ];
    }
}
