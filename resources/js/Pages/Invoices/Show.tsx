import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { InvoiceStatusBadge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Modal } from '@/Components/ui/Modal';
import { formatTND, formatNumber } from '@/utils/format';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Invoice, InvoicePartner, InvoiceLine, InvoiceTaxSummary, PageProps } from '@/types';

interface Props extends PageProps {
    invoice: Invoice & {
        sender: InvoicePartner | null;
        receiver: InvoicePartner | null;
        lines: InvoiceLine[];
        taxes: InvoiceTaxSummary[];
        creator: { id: string; name: string } | null;
    };
    canEdit: boolean;
    canDelete: boolean;
    canValidate: boolean;
    canSign: boolean;
    canSubmit: boolean;
}

export default function Show({ invoice, canEdit, canDelete, canValidate, canSign, canSubmit }: Props) {
    const [showDelete, setShowDelete] = useState(false);
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const performAction = (route: string, method: 'post' | 'delete' = 'post', actionName?: string) => {
        setActionLoading(actionName || route);
        router[method](route, {}, {
            onFinish: () => setActionLoading(null),
        });
    };

    const statusColors: Record<string, string> = {
        draft: 'bg-gray-50 border-gray-200',
        validated: 'bg-blue-50 border-blue-200',
        signed: 'bg-indigo-50 border-indigo-200',
        submitted: 'bg-yellow-50 border-yellow-200',
        accepted: 'bg-green-50 border-green-200',
        rejected: 'bg-red-50 border-red-200',
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Invoice ${invoice.document_identifier}`} />

            <div className="space-y-6">
                {/* Header with status */}
                <div className={`rounded-lg border p-6 shadow-sm ${statusColors[invoice.status] ?? 'bg-white'}`}>
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold text-gray-900">{invoice.document_identifier}</h1>
                                <InvoiceStatusBadge status={invoice.status} />
                            </div>
                            <p className="mt-1 text-sm text-gray-500">
                                <span className="rounded bg-gray-200 px-1.5 py-0.5 text-xs font-medium">{invoice.document_type_code}</span>
                                {' '}{invoice.document_type_name}
                                {' • '}Created on {new Date(invoice.created_at).toLocaleDateString('en-US')}
                                {invoice.creator && ` by ${invoice.creator.name}`}
                            </p>
                        </div>

                        {/* Action buttons */}
                        <div className="flex flex-wrap gap-2">
                            {canEdit && (
                                <Link href={`/invoices/${invoice.id}/edit`}>
                                    <Button size="sm">Edit</Button>
                                </Link>
                            )}
                            {canValidate && (
                                <Button 
                                    size="sm" 
                                    variant="secondary" 
                                    onClick={() => performAction(`/invoices/${invoice.id}/validate`, 'post', 'validate')}
                                    loading={actionLoading === 'validate'}
                                >
                                    Validate
                                </Button>
                            )}
                            {canSign && (
                                <Button 
                                    size="sm" 
                                    variant="secondary" 
                                    onClick={() => performAction(`/invoices/${invoice.id}/sign`, 'post', 'sign')}
                                    loading={actionLoading === 'sign'}
                                >
                                    Sign (XAdES)
                                </Button>
                            )}
                            {canSubmit && (
                                <Button 
                                    size="sm" 
                                    onClick={() => performAction(`/invoices/${invoice.id}/submit`, 'post', 'submit')}
                                    loading={actionLoading === 'submit'}
                                >
                                    Submit to TTN
                                </Button>
                            )}
                            <a href={`/invoices/${invoice.id}/xml`} target="_blank" rel="noopener noreferrer">
                                <Button size="sm" variant="ghost">Download XML</Button>
                            </a>
                            <Button 
                                size="sm" 
                                variant="ghost" 
                                onClick={() => performAction(`/invoices/${invoice.id}/duplicate`, 'post', 'duplicate')}
                                loading={actionLoading === 'duplicate'}
                            >
                                Duplicate
                            </Button>
                            {canDelete && (
                                <Button size="sm" variant="danger" onClick={() => setShowDelete(true)}>
                                    Delete
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Workflow status indicator */}
                    <div className="mt-4 pt-4 border-t border-gray-200">
                        <div className="flex items-center justify-between text-xs">
                            {['draft', 'validated', 'signed', 'submitted', 'accepted'].map((step, idx) => {
                                const steps = ['draft', 'validated', 'signed', 'submitted', 'accepted'];
                                const currentIdx = steps.indexOf(invoice.status);
                                const isCompleted = idx <= currentIdx;
                                const isCurrent = step === invoice.status;
                                const isRejected = invoice.status === 'rejected';

                                return (
                                    <div key={step} className="flex items-center">
                                        <div className={`flex h-8 w-8 items-center justify-center rounded-full text-xs font-medium ${
                                            isRejected && step === invoice.status ? 'bg-red-500 text-white' :
                                            isCompleted ? 'bg-green-500 text-white' : 
                                            'bg-gray-200 text-gray-500'
                                        }`}>
                                            {isCompleted ? '✓' : idx + 1}
                                        </div>
                                        <span className={`ml-2 capitalize ${isCurrent ? 'font-medium text-gray-900' : 'text-gray-500'}`}>
                                            {step}
                                        </span>
                                        {idx < 4 && (
                                            <div className={`mx-4 h-0.5 w-12 ${isCompleted && idx < currentIdx ? 'bg-green-500' : 'bg-gray-200'}`} />
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Sender & Receiver Cards */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Sender */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-4 flex items-center gap-2">
                            <div className="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">I-62</div>
                            <h2 className="text-lg font-semibold">Sender (Seller)</h2>
                        </div>
                        {invoice.sender ? (
                            <div className="space-y-2">
                                <p className="font-medium text-gray-900">{invoice.sender.name}</p>
                                <p className="text-sm text-gray-500 font-mono">{invoice.sender.identifier_type}: {invoice.sender.identifier}</p>
                                {invoice.sender.street && (
                                    <p className="text-sm text-gray-600">
                                        {invoice.sender.street}
                                        {invoice.sender.city && `, ${invoice.sender.city}`}
                                        {invoice.sender.postal_code && ` ${invoice.sender.postal_code}`}
                                        {invoice.sender.country && `, ${invoice.sender.country}`}
                                    </p>
                                )}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No sender information</p>
                        )}
                    </div>

                    {/* Receiver */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-4 flex items-center gap-2">
                            <div className="rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-800">I-64</div>
                            <h2 className="text-lg font-semibold">Receiver (Buyer)</h2>
                        </div>
                        {invoice.receiver ? (
                            <div className="space-y-2">
                                <p className="font-medium text-gray-900">{invoice.receiver.name}</p>
                                <p className="text-sm text-gray-500 font-mono">{invoice.receiver.identifier_type}: {invoice.receiver.identifier}</p>
                                {invoice.receiver.street && (
                                    <p className="text-sm text-gray-600">
                                        {invoice.receiver.street}
                                        {invoice.receiver.city && `, ${invoice.receiver.city}`}
                                        {invoice.receiver.postal_code && ` ${invoice.receiver.postal_code}`}
                                        {invoice.receiver.country && `, ${invoice.receiver.country}`}
                                    </p>
                                )}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No receiver information</p>
                        )}
                    </div>
                </div>

                {/* Dates & Amounts Summary */}
                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold">Dates</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Invoice Date</dt>
                                <dd>{invoice.invoice_date || '—'}</dd>
                            </div>
                            {invoice.submitted_at && (
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Submitted</dt>
                                    <dd>{new Date(invoice.submitted_at).toLocaleDateString('en-US')}</dd>
                                </div>
                            )}
                            {invoice.accepted_at && (
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Accepted</dt>
                                    <dd>{new Date(invoice.accepted_at).toLocaleDateString('en-US')}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow lg:col-span-2">
                        <h2 className="mb-3 text-lg font-semibold">Amount Summary</h2>
                        <dl className="grid gap-4 sm:grid-cols-3 text-sm">
                            <div className="rounded-lg bg-gray-50 p-4 text-center">
                                <dt className="text-xs text-gray-500 uppercase">Total HT</dt>
                                <dd className="mt-1 text-xl font-semibold">{formatNumber(invoice.total_ht)} <span className="text-sm font-normal text-gray-500">TND</span></dd>
                            </div>
                            <div className="rounded-lg bg-blue-50 p-4 text-center">
                                <dt className="text-xs text-blue-600 uppercase">Total TVA</dt>
                                <dd className="mt-1 text-xl font-semibold text-blue-700">{formatNumber(invoice.total_tva)} <span className="text-sm font-normal">TND</span></dd>
                            </div>
                            <div className="rounded-lg bg-indigo-50 p-4 text-center">
                                <dt className="text-xs text-indigo-600 uppercase">Total TTC</dt>
                                <dd className="mt-1 text-xl font-bold text-indigo-700">{formatNumber(invoice.total_ttc)} <span className="text-sm font-normal">TND</span></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {/* Invoice Lines */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Invoice Lines</h2>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500 bg-gray-50">
                                <tr>
                                    <th className="px-3 py-3">#</th>
                                    <th className="px-3 py-3">Code</th>
                                    <th className="px-3 py-3">Description</th>
                                    <th className="px-3 py-3 text-right">Qty</th>
                                    <th className="px-3 py-3">Unit</th>
                                    <th className="px-3 py-3 text-right">Unit Price</th>
                                    <th className="px-3 py-3 text-right">TVA %</th>
                                    <th className="px-3 py-3 text-right">Net Amount</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {invoice.lines.map((line, i) => (
                                    <tr key={line.id} className="hover:bg-gray-50">
                                        <td className="px-3 py-3 text-gray-500">{line.item_identifier || i + 1}</td>
                                        <td className="px-3 py-3 font-mono text-xs">{line.item_code}</td>
                                        <td className="px-3 py-3">{line.item_description}</td>
                                        <td className="px-3 py-3 text-right">{formatNumber(line.quantity)}</td>
                                        <td className="px-3 py-3 text-gray-500">{line.measurement_unit}</td>
                                        <td className="px-3 py-3 text-right">{formatNumber(line.unit_price)}</td>
                                        <td className="px-3 py-3 text-right">{formatNumber(line.tax_rate, 0)}%</td>
                                        <td className="px-3 py-3 text-right font-medium">{formatNumber(line.line_net)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Tax Summary */}
                {invoice.taxes && invoice.taxes.length > 0 && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">Tax Summary</h2>
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500 bg-gray-50">
                                <tr>
                                    <th className="px-3 py-3">Tax Type</th>
                                    <th className="px-3 py-3 text-right">Rate</th>
                                    <th className="px-3 py-3 text-right">Taxable Amount</th>
                                    <th className="px-3 py-3 text-right">Tax Amount</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {invoice.taxes.map((tax, i) => (
                                    <tr key={i} className="hover:bg-gray-50">
                                        <td className="px-3 py-3">
                                            <span className="rounded bg-gray-100 px-2 py-0.5 text-xs mr-2">{tax.tax_type_code}</span>
                                            {tax.tax_type_name}
                                        </td>
                                        <td className="px-3 py-3 text-right">{formatNumber(tax.tax_rate, 2)}%</td>
                                        <td className="px-3 py-3 text-right">{formatTND(tax.taxable_amount)}</td>
                                        <td className="px-3 py-3 text-right font-medium">{formatTND(tax.tax_amount)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* TTN Information */}
                {invoice.ref_ttn_value && (
                    <div className="rounded-lg bg-green-50 border border-green-200 p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold text-green-800">TTN Information</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-green-700">TTN Reference</dt>
                                <dd className="font-mono font-medium">{invoice.ref_ttn_value}</dd>
                            </div>
                            {invoice.ref_cev && (
                                <div>
                                    <dt className="text-green-700 mb-1">CEV / QR Code Data</dt>
                                    <dd className="bg-white p-2 rounded text-xs font-mono break-all">{invoice.ref_cev}</dd>
                                </div>
                            )}
                        </dl>
                    </div>
                )}

                {/* Rejection reason */}
                {invoice.rejection_reason && (
                    <div className="rounded-lg bg-red-50 border border-red-200 p-6 shadow">
                        <h2 className="mb-2 text-lg font-semibold text-red-800">Rejection Reason</h2>
                        <p className="text-sm text-red-700">{invoice.rejection_reason}</p>
                    </div>
                )}

                {/* Notes */}
                {invoice.notes && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-2 text-lg font-semibold">Notes</h2>
                        <p className="text-sm text-gray-700 whitespace-pre-line">{invoice.notes}</p>
                    </div>
                )}

                {/* Back link */}
                <div className="flex justify-start">
                    <Link href="/invoices">
                        <Button variant="ghost">← Back to Invoice List</Button>
                    </Link>
                </div>
            </div>

            {/* Delete Confirmation Modal */}
            <Modal show={showDelete} onClose={() => setShowDelete(false)} title="Confirm Deletion">
                <p className="text-sm text-gray-600">
                    Are you sure you want to delete invoice <strong>{invoice.document_identifier}</strong>? 
                    This action cannot be undone.
                </p>
                <div className="mt-4 flex justify-end gap-3">
                    <Button variant="secondary" onClick={() => setShowDelete(false)}>Cancel</Button>
                    <Button variant="danger" onClick={() => router.delete(`/invoices/${invoice.id}`)}>Delete Invoice</Button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
