// Paste to: resources/js/Components/Dashboard/KpiCard.tsx
//
// Reusable KPI card. Avoids redefining color tokens per page.

import type { LucideIcon } from 'lucide-react';

interface Props {
  label: string;
  value: string;
  icon: LucideIcon;
  trend?: { value: string; positive?: boolean };
  hint?: string;
  accent?: 'blue' | 'amber' | 'green' | 'violet';
}

const ACCENTS: Record<NonNullable<Props['accent']>, string> = {
  blue: 'bg-blue-50 text-blue-600',
  amber: 'bg-amber-50 text-amber-600',
  green: 'bg-green-50 text-green-600',
  violet: 'bg-violet-50 text-violet-600',
};

export default function KpiCard({ label, value, icon: Icon, trend, hint, accent = 'blue' }: Props) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white p-5">
      <div className="flex items-start justify-between">
        <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${ACCENTS[accent]}`}>
          <Icon className="h-5 w-5" />
        </div>
        {trend && (
          <span
            className={[
              'rounded-full px-2 py-0.5 text-xs font-medium',
              trend.positive ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700',
            ].join(' ')}
          >
            {trend.value}
          </span>
        )}
      </div>
      <div className="mt-4">
        <div className="text-xs font-medium uppercase tracking-wider text-gray-500">{label}</div>
        <div className="mt-1 text-2xl font-semibold text-gray-900">{value}</div>
        {hint && <div className="mt-1 text-xs text-gray-500">{hint}</div>}
      </div>
    </div>
  );
}
