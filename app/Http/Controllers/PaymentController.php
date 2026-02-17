<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = Payment::with([
            'invoice:id,invoice_number,total_ttc,status,customer_id',
            'invoice.customer:id,name',
            'creator:id,name',
        ])
            ->when($request->input('search'), function ($query, $search) {
                $query->whereHas('invoice', function ($q) use ($search) {
                    $q->where('invoice_number', 'ilike', "%{$search}%")
                        ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'ilike', "%{$search}%"));
                })
                    ->orWhere('reference', 'ilike', "%{$search}%");
            })
            ->when($request->input('method'), fn ($q, $method) => $q->where('method', $method))
            ->when($request->input('date_from'), fn ($q, $d) => $q->where('payment_date', '>=', $d))
            ->when($request->input('date_to'), fn ($q, $d) => $q->where('payment_date', '<=', $d))
            ->latest('payment_date')
            ->paginate(25)
            ->withQueryString();

        $totalCollected = Payment::sum('amount');

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'method', 'date_from', 'date_to']),
            'totalCollected' => number_format((float) $totalCollected, 3, '.', ''),
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'string', 'in:cash,bank_transfer,cheque,effect'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Check remaining balance
        $remaining = bcsub($invoice->total_ttc, $invoice->paid_amount, 3);
        if (bccomp($validated['amount'], $remaining, 3) > 0) {
            return back()->withErrors([
                'amount' => "Le montant ne peut pas dépasser le solde restant ({$remaining} TND).",
            ]);
        }

        Payment::create([
            'invoice_id' => $invoice->id,
            'created_by' => $request->user()->id,
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $invoice = $payment->invoice;

        if (!$invoice->isEditable() && $invoice->status !== 'accepted') {
            return back()->with('error', 'Cannot delete this payment.');
        }

        $payment->delete();

        return back()->with('success', 'Payment deleted successfully.');
    }
}
