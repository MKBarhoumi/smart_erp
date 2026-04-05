<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\OldInvoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = Payment::with([
            'oldinvoice:id,oldinvoice_number,total_ttc,status,customer_id',
            'oldinvoice.customer:id,name',
            'invoice:id,document_identifier,status,created_by',
            'creator:id,name',
        ])
            ->where(function ($q) {
                // Include payments that have either oldinvoice OR invoice
                $q->whereNotNull('oldinvoice_id')
                    ->orWhereNotNull('invoice_id');
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('oldinvoice', function ($qq) use ($search) {
                        $qq->where('oldinvoice_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                    })
                    ->orWhereHas('invoice', function ($qq) use ($search) {
                        $qq->where('document_identifier', 'like', "%{$search}%");
                    })
                    ->orWhere('reference', 'like', "%{$search}%");
                });
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

    public function store(Request $request, OldInvoice $oldinvoice): RedirectResponse
    {
        $this->authorize('create', \App\Models\Payment::class);
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'string', 'in:cash,bank_transfer,cheque,effect'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Check remaining balance
        $remaining = bcsub($oldinvoice->total_ttc, $oldinvoice->paid_amount, 3);
        if (bccomp($validated['amount'], $remaining, 3) > 0) {
            return back()->withErrors([
                'amount' => "Le montant ne peut pas dépasser le solde restant ({$remaining} TND).",
            ]);
        }

        $payment = Payment::create([
            'oldinvoice_id' => $oldinvoice->id,
            'created_by' => $request->user()->id,
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Notify accountants and admins about the payment
        $recipients = User::whereIn('role', ['admin', 'super_admin', 'accountant'])
            ->where('is_active', true)
            ->where('id', '!=', $request->user()->id)
            ->get();
        
        foreach ($recipients as $user) {
            Notification::createPaymentReceived(
                $user,
                $oldinvoice,
                (float) $validated['amount'],
                $oldinvoice->customer->name ?? 'Unknown Customer'
            );
        }

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);
        $oldinvoice = $payment->oldinvoice;

        if (!$oldinvoice->isEditable() && $oldinvoice->status !== 'accepted') {
            return back()->with('error', 'Cannot delete this payment.');
        }

        $payment->delete();

        return back()->with('success', 'Payment deleted successfully.');
    }
}
