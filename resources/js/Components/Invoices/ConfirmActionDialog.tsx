// Paste to: resources/js/Components/Invoices/ConfirmActionDialog.tsx
//
// Reusable confirmation dialog for irreversible compliance actions (Sign / Submit TTN / Delete).
// Built on top of your existing <Modal /> primitive — no new deps.
//
// Usage:
//   <ConfirmActionDialog
//     open={confirm === 'sign'}
//     title="Sign this invoice?"
//     description="Signing will lock the invoice and apply the digital signature. This cannot be undone."
//     confirmLabel="Sign Invoice"
//     variant="primary"
//     loading={signing}
//     onCancel={() => setConfirm(null)}
//     onConfirm={() => action(`/invoices/${invoice.id}/sign`)}
//   />

import { AlertTriangle, ShieldCheck, Send, Trash2 } from 'lucide-react';
import { Modal } from '@/Components/ui/Modal';
import { Button } from '@/Components/ui/Button';

export type ConfirmVariant = 'primary' | 'danger' | 'warning';

interface Props {
  open: boolean;
  title: string;
  description: string;
  confirmLabel: string;
  cancelLabel?: string;
  variant?: ConfirmVariant;
  /** Optional icon override; falls back to one based on variant. */
  icon?: 'sign' | 'submit' | 'delete' | 'warning';
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
  children?: React.ReactNode;
}

const ICONS = {
  sign: ShieldCheck,
  submit: Send,
  delete: Trash2,
  warning: AlertTriangle,
} as const;

const ICON_COLORS: Record<ConfirmVariant, string> = {
  primary: 'bg-blue-100 text-blue-600',
  danger: 'bg-red-100 text-red-600',
  warning: 'bg-amber-100 text-amber-600',
};

export default function ConfirmActionDialog({
  open,
  title,
  description,
  confirmLabel,
  cancelLabel = 'Cancel',
  variant = 'primary',
  icon = 'warning',
  loading,
  onConfirm,
  onCancel,
  children,
}: Props) {
  const Icon = ICONS[icon];

  return (
    // ADAPT: your Modal probably uses `show`/`onClose` — adjust prop names if needed.
    <Modal show={open} onClose={loading ? () => {} : onCancel}>
      <div className="p-6">
        <div className="flex items-start gap-4">
          <div
            className={[
              'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full',
              ICON_COLORS[variant],
            ].join(' ')}
          >
            <Icon className="h-5 w-5" />
          </div>
          <div className="flex-1">
            <h3 className="text-base font-semibold text-gray-900">{title}</h3>
            <p className="mt-1 text-sm text-gray-600">{description}</p>
            {children && <div className="mt-3">{children}</div>}
          </div>
        </div>

        <div className="mt-6 flex justify-end gap-2">
          <Button variant="ghost" onClick={onCancel} disabled={loading}>
            {cancelLabel}
          </Button>
          <Button
            // ADAPT: your Button variants might be named differently
            variant={variant === 'danger' ? 'destructive' : 'primary'}
            onClick={onConfirm}
            disabled={loading}
          >
            {loading ? 'Working…' : confirmLabel}
          </Button>
        </div>
      </div>
    </Modal>
  );
}
