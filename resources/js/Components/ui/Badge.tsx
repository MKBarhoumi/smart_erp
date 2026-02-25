interface BadgeProps {
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info';
  children: React.ReactNode;
}

const variants = {
  default: 'bg-gray-100 text-gray-700',
  success: 'bg-green-100 text-green-700',
  warning: 'bg-yellow-100 text-yellow-700',
  danger: 'bg-red-100 text-red-700',
  info: 'bg-blue-100 text-blue-700',
};

export function Badge({ variant = 'default', children }: BadgeProps) {
  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${variants[variant]}`}>
      {children}
    </span>
  );
}

export function OldInvoiceStatusBadge({ status }: { status: string }) {
  const statusMap: Record<string, { variant: BadgeProps['variant']; label: string }> = {
    draft: { variant: 'default', label: 'Draft' },
    validated: { variant: 'info', label: 'Validated' },
    signed: { variant: 'info', label: 'Signed' },
    submitted: { variant: 'warning', label: 'Submitted' },
    accepted: { variant: 'success', label: 'Accepted' },
    rejected: { variant: 'danger', label: 'Rejected' },
    archived: { variant: 'default', label: 'Archived' },
  };

  const config = statusMap[status] || { variant: 'default' as const, label: status };

  return <Badge variant={config.variant}>{config.label}</Badge>;
}

export function InvoiceStatusBadge({ status }: { status: string }) {
  const statusMap: Record<string, { variant: BadgeProps['variant']; label: string }> = {
    draft: { variant: 'default', label: 'Draft' },
    validated: { variant: 'info', label: 'Validated' },
    signed: { variant: 'info', label: 'Signed' },
    submitted: { variant: 'warning', label: 'Submitted' },
    accepted: { variant: 'success', label: 'Accepted' },
    rejected: { variant: 'danger', label: 'Rejected' },
  };

  const config = statusMap[status] || { variant: 'default' as const, label: status };

  return <Badge variant={config.variant}>{config.label}</Badge>;
}
