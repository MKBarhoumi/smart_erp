<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DocumentTypeCode;
use App\Enums\OldInvoiceStatus;
use App\Exceptions\OldInvoiceStateException;
use App\Exceptions\SignatureException;
use App\Exceptions\TeifValidationException;
use App\Exceptions\TTNSubmissionException;
use App\Http\Requests\StoreOldInvoiceRequest;
use App\Models\Customer;
use App\Models\OldInvoice;
use App\Models\Product;
use App\Services\OldInvoiceCalculationService;
use App\Services\OldInvoiceNumberingService;
use App\Services\OldInvoicePdfService;
use App\Services\TeifXmlBuilder;
use App\Services\TTNApiClient;
use App\Services\XadesSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OldInvoiceController extends Controller
{
    public function __construct(
        private readonly OldInvoiceCalculationService $calculator,
        private readonly OldInvoiceNumberingService $numbering,
        private readonly TeifXmlBuilder $xmlBuilder,
        private readonly XadesSignatureService $signatureService,
        private readonly TTNApiClient $ttnClient,
        private readonly OldInvoicePdfService $pdfService,
    ) {
    }

    public function index(): Response
    {
        $oldinvoices = OldInvoice::with('customer:id,name')
            ->when(request('search'), function ($query, $search) {
                $query->where('oldinvoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when(request('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when(request('date_from'), fn ($q, $d) => $q->where('oldinvoice_date', '>=', $d))
            ->when(request('date_to'), fn ($q, $d) => $q->where('oldinvoice_date', '<=', $d))
            ->latest('oldinvoice_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('OldInvoices/Index', [
            'oldinvoices' => $oldinvoices,
            'filters' => request()->only('search', 'status', 'date_from', 'date_to'),
            'statuses' => collect(OldInvoiceStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('OldInvoices/Create', [
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

    public function store(StoreOldInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request) {
            // Generate oldinvoice number
            $oldinvoiceNumber = $this->numbering->generateNextNumber();

            // Create oldinvoice
            $oldinvoice = OldInvoice::create([
                'customer_id' => $validated['customer_id'],
                'created_by' => $request->user()->id,
                'oldinvoice_number' => $oldinvoiceNumber,
                'document_identifier' => $oldinvoiceNumber,
                'document_type_code' => $validated['document_type_code'],
                'status' => OldInvoiceStatus::DRAFT->value,
                'oldinvoice_date' => $validated['oldinvoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'billing_period_start' => $validated['billing_period_start'] ?? null,
                'billing_period_end' => $validated['billing_period_end'] ?? null,
                'parent_oldinvoice_id' => $validated['parent_oldinvoice_id'] ?? null,
                'timbre_fiscal' => $validated['timbre_fiscal'] ?? '0.000',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create lines
            foreach ($validated['lines'] as $index => $lineData) {
                $oldinvoice->lines()->create([
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
            $totals = $this->calculator->calculateTotals($oldinvoice);

            // Update oldinvoice amounts
            $oldinvoice->update([
                'total_gross' => $totals['total_gross'],
                'total_discount' => $totals['total_discount'],
                'total_net_before_disc' => $totals['total_net_before_disc'],
                'total_ht' => $totals['total_ht'],
                'total_tva' => $totals['total_tva'],
                'total_ttc' => $totals['total_ttc'],
            ]);

            // Update line calculated amounts
            foreach ($totals['lines'] as $lineResult) {
                $oldinvoice->lines()
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
                $oldinvoice->taxLines()->create([
                    'tax_type_code' => $taxSummary['tax_type_code'],
                    'tax_type_name' => $taxSummary['tax_type_name'],
                    'tax_rate' => $taxSummary['tax_rate'],
                    'taxable_amount' => $taxSummary['taxable_amount'],
                    'tax_amount' => $taxSummary['tax_amount'],
                ]);
            }

            return redirect()->route('oldinvoices.show', $oldinvoice)
                ->with('success', 'OldInvoice created successfully.');
        });
    }

    public function show(OldInvoice $oldinvoice): Response
    {
        $oldinvoice->load([
            'customer',
            'lines.product:id,code,name',
            'taxLines',
            'allowances',
            'payments.creator:id,name',
            'parentOldInvoice:id,oldinvoice_number',
            'creator:id,name',
        ]);

        return Inertia::render('OldInvoices/Show', [
            'oldinvoice' => $oldinvoice,
        ]);
    }

    public function edit(OldInvoice $oldinvoice)
    {
        if (!$oldinvoice->isEditable()) {
            return redirect()->route('oldinvoices.show', $oldinvoice)
                ->with('error', 'This oldinvoice can no longer be edited.');
        }

        $oldinvoice->load(['lines', 'customer']);

        return Inertia::render('OldInvoices/Edit', [
            'oldinvoice' => $oldinvoice,
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

    public function update(StoreOldInvoiceRequest $request, OldInvoice $oldinvoice): RedirectResponse
    {
        if (!$oldinvoice->isEditable()) {
            return back()->with('error', 'This oldinvoice can no longer be edited.');
        }

        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $oldinvoice, $request) {
            $oldinvoice->update([
                'customer_id' => $validated['customer_id'],
                'document_type_code' => $validated['document_type_code'],
                'oldinvoice_date' => $validated['oldinvoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'billing_period_start' => $validated['billing_period_start'] ?? null,
                'billing_period_end' => $validated['billing_period_end'] ?? null,
                'parent_oldinvoice_id' => $validated['parent_oldinvoice_id'] ?? null,
                'timbre_fiscal' => $validated['timbre_fiscal'] ?? '0.000',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete old lines and tax lines
            $oldinvoice->lines()->delete();
            $oldinvoice->taxLines()->delete();

            // Recreate lines
            foreach ($validated['lines'] as $index => $lineData) {
                $oldinvoice->lines()->create([
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
            $oldinvoice->refresh();
            $totals = $this->calculator->calculateTotals($oldinvoice);

            $oldinvoice->update([
                'total_gross' => $totals['total_gross'],
                'total_discount' => $totals['total_discount'],
                'total_net_before_disc' => $totals['total_net_before_disc'],
                'total_ht' => $totals['total_ht'],
                'total_tva' => $totals['total_tva'],
                'total_ttc' => $totals['total_ttc'],
            ]);

            foreach ($totals['lines'] as $lineResult) {
                $oldinvoice->lines()
                    ->where('line_number', $lineResult['line_number'])
                    ->update([
                        'discount_amount' => $lineResult['discount_amount'],
                        'line_net_amount' => $lineResult['line_net_amount'],
                        'tva_amount' => $lineResult['tva_amount'],
                        'line_total' => $lineResult['line_total'],
                    ]);
            }

            foreach ($totals['tax_summary'] as $taxSummary) {
                $oldinvoice->taxLines()->create([
                    'tax_type_code' => $taxSummary['tax_type_code'],
                    'tax_type_name' => $taxSummary['tax_type_name'],
                    'tax_rate' => $taxSummary['tax_rate'],
                    'taxable_amount' => $taxSummary['taxable_amount'],
                    'tax_amount' => $taxSummary['tax_amount'],
                ]);
            }

            return redirect()->route('oldinvoices.show', $oldinvoice)
                ->with('success', 'OldInvoice updated successfully.');
        });
    }

    public function destroy(OldInvoice $oldinvoice): RedirectResponse
    {
        if (!$oldinvoice->isEditable()) {
            return back()->with('error', 'This oldinvoice can no longer be deleted.');
        }

        $oldinvoice->delete();

        return redirect()->route('oldinvoices.index')
            ->with('success', 'OldInvoice deleted successfully.');
    }

    /**
     * Validate oldinvoice (DRAFT → VALIDATED).
     */
    public function validateOldInvoice(OldInvoice $oldinvoice): RedirectResponse
    {
        try {
            $oldinvoice->transitionTo(OldInvoiceStatus::VALIDATED);
        } catch (OldInvoiceStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'OldInvoice validated successfully.');
    }

    /**
     * Sign oldinvoice with XAdES-BES (VALIDATED → SIGNED).
     */
    public function sign(OldInvoice $oldinvoice): RedirectResponse
    {
        try {
            // Build TEIF XML
            $unsignedXml = $this->xmlBuilder->build($oldinvoice);

            // Sign XML
            $signedXml = $this->signatureService->sign($unsignedXml);

            $oldinvoice->update(['signed_xml' => $signedXml]);
            $oldinvoice->transitionTo(OldInvoiceStatus::SIGNED);
        } catch (TeifValidationException|SignatureException $e) {
            return back()->with('error', 'Signature error: ' . $e->getMessage());
        } catch (OldInvoiceStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'OldInvoice signed successfully.');
    }

    /**
     * Submit to TTN (SIGNED → SUBMITTED).
     */
    public function submit(OldInvoice $oldinvoice): RedirectResponse
    {
        if (empty($oldinvoice->signed_xml)) {
            return back()->with('error', 'The oldinvoice must be signed before submission.');
        }

        try {
            $result = $this->ttnClient->submit($oldinvoice, $oldinvoice->signed_xml);

            $oldinvoice->update([
                'ref_ttn_val' => $result['ref_ttn_val'],
                'cev_qr_content' => $result['cev'],
                'submitted_at' => now(),
            ]);

            $oldinvoice->transitionTo(OldInvoiceStatus::SUBMITTED);

            // If we got an immediate acceptance
            if (strtolower($result['status']) === 'accepted') {
                $oldinvoice->update(['accepted_at' => now()]);
                $oldinvoice->transitionTo(OldInvoiceStatus::ACCEPTED);

                return back()->with('success', 'OldInvoice submitted and accepted by TTN.');
            }
        } catch (TTNSubmissionException $e) {
            return back()->with('error', 'TTN Error: ' . $e->getMessage());
        } catch (OldInvoiceStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'OldInvoice submitted to TTN successfully.');
    }

    /**
     * Download PDF.
     */
    public function downloadPdf(OldInvoice $oldinvoice): HttpResponse
    {
        $pdfContent = $this->pdfService->generate($oldinvoice);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$oldinvoice->oldinvoice_number}.pdf\"",
        ]);
    }

    /**
     * Download signed XML.
     */
    public function downloadXml(OldInvoice $oldinvoice): RedirectResponse|HttpResponse
    {
        if (empty($oldinvoice->signed_xml)) {
            return redirect()
                ->back()
                ->with('error', 'No signed XML available. Please sign the oldinvoice first.');
        }

        return response($oldinvoice->signed_xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$oldinvoice->oldinvoice_number}.xml\"",
        ]);
    }

    /**
     * Duplicate an oldinvoice.
     */
    public function duplicate(OldInvoice $oldinvoice): RedirectResponse
    {
        return DB::transaction(function () use ($oldinvoice) {
            $newNumber = $this->numbering->generateNextNumber();

            $newOldInvoice = $oldinvoice->replicate([
                'oldinvoice_number',
                'document_identifier',
                'status',
                'ref_ttn_val',
                'cev_qr_content',
                'signed_xml',
                'submitted_at',
                'accepted_at',
                'rejection_reason',
            ]);

            $newOldInvoice->oldinvoice_number = $newNumber;
            $newOldInvoice->document_identifier = $newNumber;
            $newOldInvoice->status = OldInvoiceStatus::DRAFT->value;
            $newOldInvoice->oldinvoice_date = now()->toDateString();
            $newOldInvoice->created_by = auth()->id();
            $newOldInvoice->save();

            // Copy lines
            foreach ($oldinvoice->lines as $line) {
                $newLine = $line->replicate(['oldinvoice_id']);
                $newLine->oldinvoice_id = $newOldInvoice->id;
                $newLine->save();
            }

            return redirect()->route('oldinvoices.edit', $newOldInvoice)
                ->with('success', 'OldInvoice duplicated as draft.');
        });
    }
}
