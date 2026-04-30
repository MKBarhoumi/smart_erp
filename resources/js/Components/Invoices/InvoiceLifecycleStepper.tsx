// Paste to: resources/js/Components/Invoices/InvoiceLifecycleStepper.tsx
//
// Visual stepper for the invoice lifecycle. Read-only — does not trigger transitions.
// Shows where the invoice currently sits and which steps remain.

import { Check, Circle, AlertCircle } from 'lucide-react';
import { LIFECYCLE, STATUS_LABEL, lifecycleIndex, type InvoiceStatus } from '@/utils/invoiceStatus';

interface Props {
  status: InvoiceStatus;
}

export default function InvoiceLifecycleStepper({ status }: Props) {
  const isRejected = status === 'rejected';
  const isArchived = status === 'archived';
  const currentIndex = lifecycleIndex(status);

  if (isRejected) {
    return (
      <div className="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
        <AlertCircle className="h-5 w-5 text-red-600" />
        <div>
          <div className="text-sm font-semibold text-red-900">Invoice rejected</div>
          <div className="text-xs text-red-700">
            TTN rejected this invoice. Review the TTN log below for details.
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="rounded-lg border border-gray-200 bg-white p-4">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-sm font-semibold text-gray-900">Lifecycle</h3>
        {isArchived && (
          <span className="text-xs text-gray-500">Archived</span>
        )}
      </div>

      {/* Horizontal stepper (desktop) */}
      <ol className="hidden items-center md:flex">
        {LIFECYCLE.map((step, idx) => {
          const isDone = idx < currentIndex;
          const isCurrent = idx === currentIndex;

          return (
            <li key={step} className="flex flex-1 items-center last:flex-none">
              <div className="flex flex-col items-center text-center">
                <div
                  className={[
                    'flex h-8 w-8 items-center justify-center rounded-full border-2 transition-colors',
                    isDone
                      ? 'border-blue-600 bg-blue-600 text-white'
                      : isCurrent
                        ? 'border-blue-600 bg-white text-blue-600'
                        : 'border-gray-300 bg-white text-gray-400',
                  ].join(' ')}
                  aria-current={isCurrent ? 'step' : undefined}
                >
                  {isDone ? (
                    <Check className="h-4 w-4" />
                  ) : (
                    <span className="text-xs font-semibold">{idx + 1}</span>
                  )}
                </div>
                <div
                  className={[
                    'mt-2 max-w-[8rem] text-xs',
                    isDone || isCurrent ? 'font-medium text-gray-900' : 'text-gray-500',
                  ].join(' ')}
                >
                  {STATUS_LABEL[step]}
                </div>
              </div>

              {idx < LIFECYCLE.length - 1 && (
                <div
                  className={[
                    'mx-2 h-0.5 flex-1 transition-colors',
                    idx < currentIndex ? 'bg-blue-600' : 'bg-gray-200',
                  ].join(' ')}
                />
              )}
            </li>
          );
        })}
      </ol>

      {/* Vertical stepper (mobile) */}
      <ol className="space-y-3 md:hidden">
        {LIFECYCLE.map((step, idx) => {
          const isDone = idx < currentIndex;
          const isCurrent = idx === currentIndex;
          return (
            <li key={step} className="flex items-center gap-3">
              <div
                className={[
                  'flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border-2',
                  isDone
                    ? 'border-blue-600 bg-blue-600 text-white'
                    : isCurrent
                      ? 'border-blue-600 bg-white text-blue-600'
                      : 'border-gray-300 bg-white text-gray-400',
                ].join(' ')}
              >
                {isDone ? <Check className="h-3.5 w-3.5" /> : <Circle className="h-2 w-2 fill-current" />}
              </div>
              <span
                className={[
                  'text-sm',
                  isDone || isCurrent ? 'font-medium text-gray-900' : 'text-gray-500',
                ].join(' ')}
              >
                {STATUS_LABEL[step]}
              </span>
            </li>
          );
        })}
      </ol>
    </div>
  );
}
