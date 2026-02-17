<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DocumentTypeCode;
use App\Enums\InvoiceStatus;
use App\Exceptions\InvoiceStateException;
use App\Exceptions\SignatureException;
use App\Exceptions\TeifValidationException;
use App\Exceptions\TTNSubmissionException;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\InvoiceCalculationService;
use App\Services\InvoiceNumberingService;
use App\Services\InvoicePdfService;
use App\Services\TeifXmlBuilder;
use App\Services\TTNApiClient;
use App\Services\XadesSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceCalculationService $calculator,
        private readonly InvoiceNumberingService $numbering,
        private readonly TeifXmlBuilder $xmlBuilder,
        private readonly XadesSignatureService $signatureService,
        private readonly TTNApiClient $ttnClient,
        private readonly InvoicePdfService $pdfService,
    ) {
    }

    public function index(): Response
    {
        $invoices = Invoice::with('customer:id,name')
            ->when(request('search'), function ($query, $search) {
                $query->where('invoice_number', 'ilike', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));
            })
            ->when(request('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when(request('date_from'), fn ($q, $d) => $q->where('invoice_date', '>=', $d))
            ->when(request('date_to'), fn ($q, $d) => $q->where('invoice_date', '<=', $d))
            ->latest('invoice_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => request()->only('search', 'status', 'date_from', 'date_to'),
            'statuses' => collect(InvoiceStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Invoices/Create', [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'identifier_value']),
            'products' => Product::where('is_active', true)->orderBy('name')->get([
                'id', 'code', 'name', 'unit_price', 'unit_of_measure', 'tva_rate', 'is_subject_to_timbre',
            ]),
            'documentTypes' => collect(DocumentTypeCode::cases())->map(fn ($d) => [
                'value' => $d->value,
                'label' => $d->label(),
            ]),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request) {
            // Generate invoice number
            $invoiceNumber = $this->numbering->generateNextNumber();

            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'created_by' => $request->user()->id,
                'invoice_number' => $invoiceNumber,
                'document_identifier' => $invoiceNumber,
                'document_type_code' => $validated['document_type_code'],
                'status' => InvoiceStatus::DRAFT->value,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'billing_period_start' => $validated['billing_period_start'] ?? null,
                'billing_period_end' => $validated['billing_period_end'] ?? null,
                'parent_invoice_id' => $validated['parent_invoice_id'] ?? null,
                'timbre_fiscal' => $validated['timbre_fiscal'] ?? '0.000',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create lines
            foreach ($validated['lines'] as $index => $lineData) {
                $invoice->lines()->create([
                    'product_id' => $lineData['product_id'] ?? null,
                    'line_number' => $index + 1,
                    'item_code' => $lineData['item_code'],
                    'item_description' => $lineData['item_description'],
                    'item_lang' => $lineData['item_lang'] ?? 'fr',
                    'quantity' => $lineData['quantity'],
                    'unit_of_measure' => $lineData['unit_of_measure'] ?? 'U',
                    'unit_price' => $lineData['unit_price'],
                    'discount_rate' => $lineData['discount_rate'] ?? 0,
                    'tva_rate' => $lineData['tva_rate'],
                ]);
            }

            // Calculate totals
            $totals = $this->calculator->calculateTotals($invoice);

            // Update invoice amounts
            $invoice->update([
                'total_gross' => $totals['total_gross'],
                'total_discount' => $totals['total_discount'],
                'total_net_before_disc' => $totals['total_net_before_disc'],
                'total_ht' => $totals['total_ht'],
                'total_tva' => $totals['total_tva'],
                'total_ttc' => $totals['total_ttc'],
            ]);

            // Update line calculated amounts
            foreach ($totals['lines'] as $lineResult) {
                $invoice->lines()
                    ->where('line_number', $lineResult['line_number'])
                    ->update([
                        'discount_amount' => $lineResult['discount_amount'],
                        'line_net_amount' => $lineResult['line_net_amount'],
                        'tva_amount' => $lineResult['tva_amount'],
                        'line_total' => $lineResult['line_total'],
                    ]);
            }

            // Create tax summary lines
            foreach ($totals['tax_summary'] as $taxSummary) {
                $invoice->taxLines()->create([
                    'tax_type_code' => $taxSummary['tax_type_code'],
                    'tax_type_name' => $taxSummary['tax_type_name'],
                    'tax_rate' => $taxSummary['tax_rate'],
                    'taxable_amount' => $taxSummary['taxable_amount'],
                    'tax_amount' => $taxSummary['tax_amount'],
                ]);
            }

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Facture créée avec succès.');
        });
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load([
            'customer',
            'lines.product:id,code,name',
            'taxLines',
            'allowances',
            'payments.creator:id,name',
            'parentInvoice:id,invoice_number',
            'creator:id,name',
        ]);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        if (!$invoice->isEditable()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Cette facture ne peut plus être modifiée.');
        }

        $invoice->load(['lines', 'customer']);

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'identifier_value']),
            'products' => Product::where('is_active', true)->orderBy('name')->get([
                'id', 'code', 'name', 'unit_price', 'unit_of_measure', 'tva_rate', 'is_subject_to_timbre',
            ]),
            'documentTypes' => collect(DocumentTypeCode::cases())->map(fn ($d) => [
                'value' => $d->value,
                'label' => $d->label(),
            ]),
        ]);
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isEditable()) {
            return back()->with('error', 'Cette facture ne peut plus être modifiée.');
        }

        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $invoice, $request) {
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'document_type_code' => $validated['document_type_code'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'billing_period_start' => $validated['billing_period_start'] ?? null,
                'billing_period_end' => $validated['billing_period_end'] ?? null,
                'parent_invoice_id' => $validated['parent_invoice_id'] ?? null,
                'timbre_fiscal' => $validated['timbre_fiscal'] ?? '0.000',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete old lines and tax lines
            $invoice->lines()->delete();
            $invoice->taxLines()->delete();

            // Recreate lines
            foreach ($validated['lines'] as $index => $lineData) {
                $invoice->lines()->create([
                    'product_id' => $lineData['product_id'] ?? null,
                    'line_number' => $index + 1,
                    'item_code' => $lineData['item_code'],
                    'item_description' => $lineData['item_description'],
                    'item_lang' => $lineData['item_lang'] ?? 'fr',
                    'quantity' => $lineData['quantity'],
                    'unit_of_measure' => $lineData['unit_of_measure'] ?? 'U',
                    'unit_price' => $lineData['unit_price'],
                    'discount_rate' => $lineData['discount_rate'] ?? 0,
                    'tva_rate' => $lineData['tva_rate'],
                ]);
            }

            // Recalculate
            $invoice->refresh();
            $totals = $this->calculator->calculateTotals($invoice);

            $invoice->update([
                'total_gross' => $totals['total_gross'],
                'total_discount' => $totals['total_discount'],
                'total_net_before_disc' => $totals['total_net_before_disc'],
                'total_ht' => $totals['total_ht'],
                'total_tva' => $totals['total_tva'],
                'total_ttc' => $totals['total_ttc'],
            ]);

            foreach ($totals['lines'] as $lineResult) {
                $invoice->lines()
                    ->where('line_number', $lineResult['line_number'])
                    ->update([
                        'discount_amount' => $lineResult['discount_amount'],
                        'line_net_amount' => $lineResult['line_net_amount'],
                        'tva_amount' => $lineResult['tva_amount'],
                        'line_total' => $lineResult['line_total'],
                    ]);
            }

            foreach ($totals['tax_summary'] as $taxSummary) {
                $invoice->taxLines()->create([
                    'tax_type_code' => $taxSummary['tax_type_code'],
                    'tax_type_name' => $taxSummary['tax_type_name'],
                    'tax_rate' => $taxSummary['tax_rate'],
                    'taxable_amount' => $taxSummary['taxable_amount'],
                    'tax_amount' => $taxSummary['tax_amount'],
                ]);
            }

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Facture mise à jour avec succès.');
        });
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isEditable()) {
            return back()->with('error', 'Cette facture ne peut plus être supprimée.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Facture supprimée avec succès.');
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

        return back()->with('success', 'Facture validée avec succès.');
    }

    /**
     * Sign invoice with XAdES-BES (VALIDATED → SIGNED).
     */
    public function sign(Invoice $invoice): RedirectResponse
    {
        try {
            // Build TEIF XML
            $unsignedXml = $this->xmlBuilder->build($invoice);

            // Sign XML
            $signedXml = $this->signatureService->sign($unsignedXml);

            $invoice->update(['signed_xml' => $signedXml]);
            $invoice->transitionTo(InvoiceStatus::SIGNED);
        } catch (TeifValidationException|SignatureException $e) {
            return back()->with('error', 'Erreur de signature: ' . $e->getMessage());
        } catch (InvoiceStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Facture signée avec succès.');
    }

    /**
     * Submit to TTN (SIGNED → SUBMITTED).
     */
    public function submit(Invoice $invoice): RedirectResponse
    {
        if (empty($invoice->signed_xml)) {
            return back()->with('error', 'La facture doit être signée avant soumission.');
        }

        try {
            $result = $this->ttnClient->submit($invoice, $invoice->signed_xml);

            $invoice->update([
                'ref_ttn_val' => $result['ref_ttn_val'],
                'cev_qr_content' => $result['cev'],
                'submitted_at' => now(),
            ]);

            $invoice->transitionTo(InvoiceStatus::SUBMITTED);

            // If we got an immediate acceptance
            if (strtolower($result['status']) === 'accepted') {
                $invoice->update(['accepted_at' => now()]);
                $invoice->transitionTo(InvoiceStatus::ACCEPTED);

                return back()->with('success', 'Facture soumise et acceptée par TTN.');
            }
        } catch (TTNSubmissionException $e) {
            return back()->with('error', 'Erreur TTN: ' . $e->getMessage());
        } catch (InvoiceStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Facture soumise à TTN avec succès.');
    }

    /**
     * Download PDF.
     */
    public function downloadPdf(Invoice $invoice): HttpResponse
    {
        $pdfContent = $this->pdfService->generate($invoice);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$invoice->invoice_number}.pdf\"",
        ]);
    }

    /**
     * Download signed XML.
     */
    public function downloadXml(Invoice $invoice): HttpResponse
    {
        if (empty($invoice->signed_xml)) {
            abort(404, 'Pas de XML signé disponible.');
        }

        return response($invoice->signed_xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$invoice->invoice_number}.xml\"",
        ]);
    }

    /**
     * Duplicate an invoice.
     */
    public function duplicate(Invoice $invoice): RedirectResponse
    {
        return DB::transaction(function () use ($invoice) {
            $newNumber = $this->numbering->generateNextNumber();

            $newInvoice = $invoice->replicate([
                'invoice_number',
                'document_identifier',
                'status',
                'ref_ttn_val',
                'cev_qr_content',
                'signed_xml',
                'submitted_at',
                'accepted_at',
                'rejection_reason',
            ]);

            $newInvoice->invoice_number = $newNumber;
            $newInvoice->document_identifier = $newNumber;
            $newInvoice->status = InvoiceStatus::DRAFT->value;
            $newInvoice->invoice_date = now()->toDateString();
            $newInvoice->created_by = auth()->id();
            $newInvoice->save();

            // Copy lines
            foreach ($invoice->lines as $line) {
                $newLine = $line->replicate(['invoice_id']);
                $newLine->invoice_id = $newInvoice->id;
                $newLine->save();
            }

            return redirect()->route('invoices.edit', $newInvoice)
                ->with('success', 'Facture dupliquée en brouillon.');
        });
    }
}
