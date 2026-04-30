// Paste to: resources/js/utils/invoiceStatus.ts
//
// UI-side mirror of the server status machine. This is NOT a security boundary —
// the backend remains the source of truth and continues to compute can* flags.
// We use these helpers only for visual gating (stepper, tabs, role-based CTA).

export type InvoiceStatus =
  | 'draft'
  | 'pending_validation'
  | 'validated'
  | 'signed'
  | 'submitted'
  | 'accepted'
  | 'rejected'
  | 'archived';

export type UserRole =
  | 'super_admin'
  | 'admin'
  | 'accountant'
  | 'sales'
  | 'inventory_manager'
  | 'viewer';

/** Ordered lifecycle for the stepper. Terminal/branch states (rejected, archived) are not shown. */
export const LIFECYCLE: InvoiceStatus[] = [
  'draft',
  'pending_validation',
  'validated',
  'signed',
  'submitted',
  'accepted',
];

export const STATUS_LABEL: Record<InvoiceStatus, string> = {
  draft: 'Draft',
  pending_validation: 'Pending Validation',
  validated: 'Validated',
  signed: 'Signed',
  submitted: 'Submitted',
  accepted: 'Accepted',
  rejected: 'Rejected',
  archived: 'Archived',
};

/** Index of a status in the lifecycle, or -1 if branch state. */
export function lifecycleIndex(status: InvoiceStatus): number {
  return LIFECYCLE.indexOf(status);
}

/**
 * Role → can create invoices. Used ONLY to gate the "New Invoice" CTA on the list.
 * Server still enforces creation permission on POST /invoices.
 *
 * Per spec: Admin, Sales, Super Admin can create. Accountant validates only.
 * Viewer is read-only.
 */
export function canCreateInvoice(role: UserRole | undefined | null): boolean {
  if (!role) return false;
  return role === 'super_admin' || role === 'admin' || role === 'sales';
}

/** Status tabs to show on the list page. 'all' is a virtual tab. */
export const STATUS_TABS: Array<{ value: 'all' | InvoiceStatus; label: string }> = [
  { value: 'all', label: 'All' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending_validation', label: 'Pending' },
  { value: 'validated', label: 'Validated' },
  { value: 'signed', label: 'Signed' },
  { value: 'submitted', label: 'Submitted' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' },
];
