// Paste to: resources/js/Pages/Invoices/Show.tsx
//
// Drop-in replacement for the Invoice Detail / Signing / Submission page.
// - Same Inertia props (invoice + can* server-driven flags)
// - Same routes for actions (/validate, /sign, /submit, /pdf, /xml, /payments, etc.)
// - Adds: lifecycle stepper, confirmation dialogs for Sign/Submit/Delete,
//         improved TTN log inspection, error-prominent layout
// - Permission gating remains 100% server-driven via canValidate / canSign / canSubmit / canEdit / canDelete.
//   Roles are NEVER re-checked client-side for mutations.

import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
  Pencil,
  ShieldCheck,
  Send,
  FileDown,
  FileCode,
  Copy,
  Trash2,
  CheckCircle2,
  Plus,
  ArrowLeft,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { InvoiceStatusBadge } from '@/Components/ui/Badge';
import { Modal } from '@/Components/ui/Modal';
import type { PageProps, Invoice } from '@/types';
import type { InvoiceStatus } from '@/utils/invoiceStatus';

import InvoiceLifecycleStepper from '@/Components/Invoices/InvoiceLifecycleStepper';
import ConfirmActionDialog from '@/Components/Invoices/ConfirmActionDialog';
import TtnLogInspector from '@/Components/Invoices/TtnLogInspector';

type ConfirmKind = 'validate' | 'sign' | 'submit' | 'delete' | null;

interface Props extends PageProps {
  invoice: Invoice & {
    customer: any;
    lines: any[];
    tax_lines?: any[];
    payments?: any[];
    ttn_logs?: any[];
    creator?: { name: string };
    ref_ttn_val?: string | null;
    cev_qr_content?: string | null;
    notes?: string | null;
  };
  canValidate: boolean;
  canSign: boolean;
  canSubmit: boolean;
  canEdit: boolean;
  canDelete: boolean;
}

function formatTND(n: number | string | null | undefined): string {
  const v = typeof n === 'string' ? parseFloat(n) : n ?? 0;
  return `${v.toFixed(3)} TND`;
}

export default function InvoiceShowPage({
  invoice,
  canValidate,
  canSign,
  canSubmit,
  canEdit,
  canDelete,
}: Props) {
  const [confirm, setConfirm] = useState<ConfirmKind>(null);
  const [pending, setPending] = useState<ConfirmKind>(null);
  const [showPayment, setShowPayment] = useState(false);

  const paymentForm = useForm({
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    method: 'transfer',
    reference: '',
  });

  function dispatchAction(url: string, method: 'post' | 'delete' = 'post', kind?: ConfirmKind) {
    setPending(kind ?? null);
    router[method](url, undefined, {
      preserveScroll: true,
      onFinish: () => {
        setPending(null);
        setConfirm(null);
      },
    });
  }

  const status = invoice.status as InvoiceStatus;
  const showPaymentsSection = ['validated', 'signed', 'submitted', 'accepted'].includes(status);
  const lastTtnError = (invoice.ttn_logs ?? []).find(
    (l) => l.status_code && (l.status_code < 200 || l.status_code >= 300),
  );

  return (
    <AuthenticatedLayout>
      <Head title={`Invoice ${invoice.invoice_number}`} />

      <div className="space-y-6 p-4 sm:p-6">
        {/* Back + breadcrumb */}
        <Link
          href="/invoices"
          className="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4" /> Invoices
        </Link>

        {/* Header */}
        <div className="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-5 lg:flex-row lg:items-start lg:justify-between">
          <div className="space-y-2">
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-2xl font-semibold text-gray-900">
                {invoice.invoice_number}
              </h1>
              <InvoiceStatusBadge status={invoice.status} />
            </div>
            <div className="text-sm text-gray-500">
              Created {new Date((invoice as any).created_at).toLocaleString()}
              {invoice.creator?.name && <> • by {invoice.creator.name}</>}
            </div>
          </div>

          {/* Action bar — every button gated by server-supplied can* flag */}
          <div className="flex flex-wrap items-center gap-2">
            {canEdit && (
              <Link href={`/invoices/${invoice.id}/edit`}>
                <Button variant="outline">
                  <Pencil className="mr-2 h-4 w-4" /> Edit
                </Button>
              </Link>
            )}
            {canValidate && (
              <Button
                variant="outline"
                onClick={() => setConfirm('validate')}
                disabled={pending === 'validate'}
              >
                <CheckCircle2 className="mr-2 h-4 w-4" /> Validate
              </Button>
            )}
            {canSign && (
              <Button onClick={() => setConfirm('sign')} disabled={pending === 'sign'}>
                <ShieldCheck className="mr-2 h-4 w-4" /> Sign
              </Button>
            )}
            {canSubmit && (
              <Button onClick={() => setConfirm('submit')} disabled={pending === 'submit'}>
                <Send className="mr-2 h-4 w-4" /> Submit to TTN
              </Button>
            )}
            <a href={`/invoices/${invoice.id}/pdf`}>
              <Button variant="ghost">
                <FileDown className="mr-2 h-4 w-4" /> PDF
              </Button>
            </a>
            <a href={`/invoices/${invoice.id}/xml`}>
              <Button variant="ghost">
                <FileCode className="mr-2 h-4 w-4" /> XML
              </Button>
            </a>
            {canEdit && (
              <Button
                variant="ghost"
                onClick={() => dispatchAction(`/invoices/${invoice.id}/duplicate`)}
              >
                <Copy className="mr-2 h-4 w-4" /> Duplicate
              </Button>
            )}
            {canDelete && (
              <Button variant="ghost" onClick={() => setConfirm('delete')}>
                <Trash2 className="mr-2 h-4 w-4 text-red-600" />
                <span className="text-red-600">Delete</span>
              </Button>
            )}
          </div>
        </div>

        {/* Lifecycle stepper */}
        <InvoiceLifecycleStepper status={status} />

        {/* Last TTN error banner — surfaces submission failures inline */}
        {lastTtnError && (
          <div className="rounded-lg border border-red-200 bg-red-50 p-4">
            <div className="text-sm font-semibold text-red-900">
              Last TTN exchange failed (HTTP {lastTtnError.status_code})
            </div>
            {lastTtnError.error_message && (
              <div className="mt-1 text-sm text-red-800">{lastTtnError.error_message}</div>
            )}
            <div className="mt-2 text-xs text-red-700">
              See the TTN Log section below for full request/response details.
            </div>
          </div>
        )}

        {/* Summary cards */}
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <div className="rounded-lg border border-gray-200 bg-white p-4">
            <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">
              Customer
            </div>
            <div className="text-base font-medium text-gray-900">
              {invoice.customer?.name ?? '—'}
            </div>
            {invoice.customer?.identifier_value && (
              <div className="mt-1 text-sm text-gray-500">
                ID: {invoice.customer.identifier_value}
              </div>
            )}
          </div>

          <div className="rounded-lg border border-gray-200 bg-white p-4">
            <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">
              Dates
            </div>
            <dl className="space-y-1 text-sm">
              <div className="flex justify-between">
                <dt className="text-gray-500">Issue</dt>
                <dd className="text-gray-900">
                  {invoice.invoice_date
                    ? new Date(invoice.invoice_date).toLocaleDateString()
                    : '—'}
                </dd>
              </div>
              {(invoice as any).due_date && (
                <div className="flex justify-between">
                  <dt className="text-gray-500">Due</dt>
                  <dd className="text-gray-900">
                    {new Date((invoice as any).due_date).toLocaleDateString()}
                  </dd>
                </div>
              )}
            </dl>
          </div>

          <div className="rounded-lg border border-gray-200 bg-white p-4">
            <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">
              Amounts
            </div>
            <dl className="space-y-1 text-sm">
              <div className="flex justify-between">
                <dt className="text-gray-500">Subtotal HT</dt>
                <dd className="font-mono text-gray-900">{formatTND((invoice as any).total_ht)}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-gray-500">TVA</dt>
                <dd className="font-mono text-gray-900">{formatTND((invoice as any).total_tva)}</dd>
              </div>
              {Number((invoice as any).timbre_fiscal) > 0 && (
                <div className="flex justify-between">
                  <dt className="text-gray-500">Timbre</dt>
                  <dd className="font-mono text-gray-900">
                    {formatTND((invoice as any).timbre_fiscal)}
                  </dd>
                </div>
              )}
              <div className="mt-2 flex justify-between border-t border-gray-100 pt-2">
                <dt className="font-semibold text-gray-900">Total TTC</dt>
                <dd className="font-mono text-base font-semibold text-gray-900">
                  {formatTND((invoice as any).total_ttc)}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        {/* Lines */}
        <section className="overflow-hidden rounded-lg border border-gray-200 bg-white">
          <div className="border-b border-gray-200 bg-gray-50 px-4 py-3">
            <h2 className="text-sm font-semibold text-gray-900">Invoice Lines</h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 text-xs uppercase tracking-wider text-gray-600">
                <tr>
                  <th className="px-4 py-2 text-left">Code</th>
                  <th className="px-4 py-2 text-left">Description</th>
                  <th className="px-4 py-2 text-right">Qty</th>
                  <th className="px-4 py-2 text-right">Unit price</th>
                  <th className="px-4 py-2 text-right">Disc%</th>
                  <th className="px-4 py-2 text-right">TVA%</th>
                  <th className="px-4 py-2 text-right">Total HT</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {invoice.lines.map((line, i) => (
                  <tr key={i}>
                    <td className="px-4 py-2 font-mono text-xs text-gray-700">{line.item_code}</td>
                    <td className="px-4 py-2 text-gray-900">{line.item_description}</td>
                    <td className="px-4 py-2 text-right font-mono">{line.quantity}</td>
                    <td className="px-4 py-2 text-right font-mono">{formatTND(line.unit_price)}</td>
                    <td className="px-4 py-2 text-right font-mono">{line.discount_rate ?? 0}</td>
                    <td className="px-4 py-2 text-right font-mono">{line.tva_rate}</td>
                    <td className="px-4 py-2 text-right font-mono">
                      {formatTND(line.total_ht ?? line.quantity * line.unit_price)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>

        {/* TTN information */}
        {invoice.ref_ttn_val && (
          <section className="rounded-lg border border-gray-200 bg-white p-4">
            <h2 className="mb-3 text-sm font-semibold text-gray-900">TTN Information</h2>
            <dl className="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
              <div>
                <dt className="text-xs uppercase text-gray-500">Reference TTN</dt>
                <dd className="font-mono text-gray-900">{invoice.ref_ttn_val}</dd>
              </div>
              {invoice.cev_qr_content && (
                <div>
                  <dt className="text-xs uppercase text-gray-500">CEV QR content</dt>
                  <dd className="break-all font-mono text-xs text-gray-700">
                    {invoice.cev_qr_content}
                  </dd>
                </div>
              )}
            </dl>
          </section>
        )}

        {/* Payments */}
        {showPaymentsSection && (
          <section className="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div className="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3">
              <h2 className="text-sm font-semibold text-gray-900">Payments</h2>
              <Button variant="outline" onClick={() => setShowPayment(true)}>
                <Plus className="mr-1 h-4 w-4" /> Payment
              </Button>
            </div>
            {(invoice.payments ?? []).length === 0 ? (
              <div className="p-6 text-center text-sm text-gray-500">No payments recorded.</div>
            ) : (
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-xs uppercase tracking-wider text-gray-600">
                  <tr>
                    <th className="px-4 py-2 text-left">Date</th>
                    <th className="px-4 py-2 text-left">Method</th>
                    <th className="px-4 py-2 text-left">Reference</th>
                    <th className="px-4 py-2 text-right">Amount</th>
                    <th className="px-4 py-2" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {invoice.payments!.map((p) => (
                    <tr key={p.id}>
                      <td className="px-4 py-2">
                        {new Date(p.payment_date).toLocaleDateString()}
                      </td>
                      <td className="px-4 py-2 capitalize">{p.method}</td>
                      <td className="px-4 py-2 text-gray-500">{p.reference ?? '—'}</td>
                      <td className="px-4 py-2 text-right font-mono">{formatTND(p.amount)}</td>
                      <td className="px-4 py-2 text-right">
                        <button
                          type="button"
                          className="text-xs text-red-600 hover:underline"
                          onClick={() =>
                            dispatchAction(`/invoices/${invoice.id}/payments/${p.id}`, 'delete')
                          }
                        >
                          Remove
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </section>
        )}

        {/* TTN log */}
        <section className="space-y-2">
          <h2 className="text-sm font-semibold text-gray-900">TTN Log</h2>
          <TtnLogInspector logs={invoice.ttn_logs ?? []} />
        </section>

        {/* Notes */}
        {invoice.notes && (
          <section className="rounded-lg border border-gray-200 bg-white p-4">
            <h2 className="mb-2 text-sm font-semibold text-gray-900">Notes</h2>
            <p className="whitespace-pre-wrap text-sm text-gray-700">{invoice.notes}</p>
          </section>
        )}
      </div>

      {/* Confirmation dialogs */}
      <ConfirmActionDialog
        open={confirm === 'validate'}
        title="Validate this invoice?"
        description="Validation freezes the invoice contents and moves it forward in the lifecycle."
        confirmLabel="Validate"
        icon="warning"
        variant="primary"
        loading={pending === 'validate'}
        onCancel={() => setConfirm(null)}
        onConfirm={() => dispatchAction(`/invoices/${invoice.id}/validate`, 'post', 'validate')}
      />

      <ConfirmActionDialog
        open={confirm === 'sign'}
        title="Sign this invoice?"
        description="Signing applies the digital signature and locks the invoice. This action cannot be undone."
        confirmLabel="Sign Invoice"
        icon="sign"
        variant="primary"
        loading={pending === 'sign'}
        onCancel={() => setConfirm(null)}
        onConfirm={() => dispatchAction(`/invoices/${invoice.id}/sign`, 'post', 'sign')}
      />

      <ConfirmActionDialog
        open={confirm === 'submit'}
        title="Submit to TTN?"
        description="The signed invoice will be transmitted to TTN for acceptance. You can monitor the result in the TTN Log."
        confirmLabel="Submit to TTN"
        icon="submit"
        variant="primary"
        loading={pending === 'submit'}
        onCancel={() => setConfirm(null)}
        onConfirm={() => dispatchAction(`/invoices/${invoice.id}/submit`, 'post', 'submit')}
      />

      <ConfirmActionDialog
        open={confirm === 'delete'}
        title="Delete this invoice?"
        description="This permanently removes the invoice and all associated payments. This cannot be undone."
        confirmLabel="Delete"
        icon="delete"
        variant="danger"
        loading={pending === 'delete'}
        onCancel={() => setConfirm(null)}
        onConfirm={() => dispatchAction(`/invoices/${invoice.id}`, 'delete', 'delete')}
      />

      {/* Payment modal — kept simple, mirrors original behaviour */}
      <Modal show={showPayment} onClose={() => setShowPayment(false)}>
        <form
          className="space-y-4 p-6"
          onSubmit={(e) => {
            e.preventDefault();
            paymentForm.post(`/invoices/${invoice.id}/payments`, {
              preserveScroll: true,
              onSuccess: () => {
                paymentForm.reset();
                setShowPayment(false);
              },
            });
          }}
        >
          <h3 className="text-base font-semibold text-gray-900">Record Payment</h3>

          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">Amount (TND)</label>
            <Input
              type="number"
              step="0.001"
              required
              value={paymentForm.data.amount}
              onChange={(e) => paymentForm.setData('amount', e.target.value)}
            />
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">Date</label>
            <Input
              type="date"
              required
              value={paymentForm.data.payment_date}
              onChange={(e) => paymentForm.setData('payment_date', e.target.value)}
            />
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">Method</label>
            <Input
              value={paymentForm.data.method}
              onChange={(e) => paymentForm.setData('method', e.target.value)}
            />
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">Reference</label>
            <Input
              value={paymentForm.data.reference}
              onChange={(e) => paymentForm.setData('reference', e.target.value)}
            />
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <Button variant="ghost" type="button" onClick={() => setShowPayment(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={paymentForm.processing}>
              Save Payment
            </Button>
          </div>
        </form>
      </Modal>
    </AuthenticatedLayout>
  );
}
