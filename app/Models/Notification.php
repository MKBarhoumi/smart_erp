<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'link',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if the notification is unread.
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Create a notification for validation request.
     */
    public static function createValidationRequest(User $admin, OldInvoice $invoice, User $requester): self
    {
        return self::create([
            'user_id' => $admin->id,
            'type' => 'validation_request',
            'title' => 'Validation Request',
            'message' => "{$requester->name} requested validation for invoice {$invoice->oldinvoice_number}",
            'icon' => 'clipboard-check',
            'link' => "/oldinvoices/{$invoice->id}",
            'notifiable_type' => OldInvoice::class,
            'notifiable_id' => $invoice->id,
            'data' => [
                'requester_id' => $requester->id,
                'requester_name' => $requester->name,
                'invoice_number' => $invoice->oldinvoice_number,
            ],
        ]);
    }

    /**
     * Create a notification for validation response (approved/rejected).
     */
    public static function createValidationResponse(User $accountant, OldInvoice $invoice, bool $approved, ?string $reason = null): self
    {
        return self::create([
            'user_id' => $accountant->id,
            'type' => 'validation_response',
            'title' => $approved ? 'Invoice Approved' : 'Invoice Rejected',
            'message' => $approved 
                ? "Invoice {$invoice->oldinvoice_number} has been approved"
                : "Invoice {$invoice->oldinvoice_number} was rejected" . ($reason ? ": {$reason}" : ''),
            'icon' => $approved ? 'check-circle' : 'x-circle',
            'link' => "/oldinvoices/{$invoice->id}",
            'notifiable_type' => OldInvoice::class,
            'notifiable_id' => $invoice->id,
            'data' => [
                'approved' => $approved,
                'reason' => $reason,
                'invoice_number' => $invoice->oldinvoice_number,
            ],
        ]);
    }

    /**
     * Create a notification for low stock alert.
     */
    public static function createLowStockAlert(User $user, Product $product): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => "Product '{$product->name}' is running low on stock ({$product->current_stock} remaining)",
            'icon' => 'exclamation-triangle',
            'link' => "/products/{$product->id}/edit",
            'notifiable_type' => Product::class,
            'notifiable_id' => $product->id,
            'data' => [
                'product_name' => $product->name,
                'current_stock' => $product->current_stock,
                'min_stock' => $product->min_stock_alert,
            ],
        ]);
    }

    /**
     * Create a notification for payment received.
     */
    public static function createPaymentReceived(User $user, Payment $payment): self
    {
        $invoice = $payment->oldinvoice ?? $payment->invoice;
        $invoiceNumber = $invoice?->oldinvoice_number ?? $invoice?->document_identifier ?? 'Unknown';
        
        return self::create([
            'user_id' => $user->id,
            'type' => 'payment_received',
            'title' => 'Payment Received',
            'message' => "Payment of {$payment->amount} TND received for invoice {$invoiceNumber}",
            'icon' => 'currency-dollar',
            'link' => $invoice ? ($payment->oldinvoice_id ? "/oldinvoices/{$invoice->id}" : "/invoices/{$invoice->id}") : null,
            'notifiable_type' => Payment::class,
            'notifiable_id' => $payment->id,
            'data' => [
                'amount' => $payment->amount,
                'method' => $payment->method,
                'invoice_number' => $invoiceNumber,
            ],
        ]);
    }

    /**
     * Create a notification for new invoice.
     */
    public static function createNewInvoice(User $user, $invoice): self
    {
        $invoiceNumber = $invoice->oldinvoice_number ?? $invoice->document_identifier;
        $isOldInvoice = $invoice instanceof OldInvoice;
        
        return self::create([
            'user_id' => $user->id,
            'type' => 'new_invoice',
            'title' => 'New Invoice Created',
            'message' => "Invoice {$invoiceNumber} has been created",
            'icon' => 'document-text',
            'link' => $isOldInvoice ? "/oldinvoices/{$invoice->id}" : "/invoices/{$invoice->id}",
            'notifiable_type' => get_class($invoice),
            'notifiable_id' => $invoice->id,
            'data' => [
                'invoice_number' => $invoiceNumber,
                'total' => $invoice->total_ttc ?? ($invoice->invoice_amounts['total_ttc'] ?? 0),
            ],
        ]);
    }

    /**
     * Create a notification for invoice status change.
     */
    public static function createInvoiceStatusChange(User $user, $invoice, string $oldStatus, string $newStatus): self
    {
        $invoiceNumber = $invoice->oldinvoice_number ?? $invoice->document_identifier;
        $isOldInvoice = $invoice instanceof OldInvoice;
        
        return self::create([
            'user_id' => $user->id,
            'type' => 'invoice_status_change',
            'title' => 'Invoice Status Updated',
            'message' => "Invoice {$invoiceNumber} status changed from {$oldStatus} to {$newStatus}",
            'icon' => 'refresh',
            'link' => $isOldInvoice ? "/oldinvoices/{$invoice->id}" : "/invoices/{$invoice->id}",
            'notifiable_type' => get_class($invoice),
            'notifiable_id' => $invoice->id,
            'data' => [
                'invoice_number' => $invoiceNumber,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
        ]);
    }
}
