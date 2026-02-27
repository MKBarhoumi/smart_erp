interface BadgeProps {
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info';
  children: React.ReactNode;
  dot?: boolean;
  icon?: React.ReactNode;
}

const variants = {
  default: 'bg-gray-100 text-gray-700 ring-gray-500/10',
  success: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  warning: 'bg-amber-50 text-amber-700 ring-amber-600/20',
  danger: 'bg-red-50 text-red-700 ring-red-600/20',
  info: 'bg-sky-50 text-sky-700 ring-sky-600/20',
};

const dotColors = {
  default: 'bg-gray-500',
  success: 'bg-emerald-500',
  warning: 'bg-amber-500',
  danger: 'bg-red-500',
  info: 'bg-sky-500',
};

export function Badge({ variant = 'default', children, dot, icon }: BadgeProps) {
  return (
    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset ${variants[variant]}`}>
      {dot && <span className={`w-1.5 h-1.5 rounded-full ${dotColors[variant]}`} />}
      {icon && <span className="[&>svg]:w-3 [&>svg]:h-3">{icon}</span>}
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

  return <Badge variant={config.variant} dot>{config.label}</Badge>;
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

  return <Badge variant={config.variant} dot>{config.label}</Badge>;
}
