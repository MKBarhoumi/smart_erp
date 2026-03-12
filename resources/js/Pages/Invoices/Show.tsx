import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo, FormEvent } from 'react';
import { InvoiceStatusBadge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Modal } from '@/Components/ui/Modal';
import { Select } from '@/Components/ui/Select';
import { formatTND, formatNumber, formatDate } from '@/utils/format';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Invoice, InvoicePartner, InvoiceLine, InvoiceTaxSummary, PageProps } from '@/types';

interface PaymentData {
    id: string;
    payment_date: string;
    amount: string;
    method: string;
    reference: string | null;
    creator: string | null;
}

interface Props extends PageProps {
    invoice: Invoice & {
        sender: InvoicePartner | null;
        receiver: InvoicePartner | null;
        lines: InvoiceLine[];
        taxes: InvoiceTaxSummary[];
        payments: PaymentData[];
        creator: { id: string; name: string } | null;
        validation_rejection_reason: string | null;
    };
    canEdit: boolean;
    canDelete: boolean;
    canRequestValidation: boolean;
    canValidate: boolean;
    canDirectValidate: boolean;
    canSign: boolean;
    canSubmit: boolean;
    isAdmin: boolean;
    validationInfo: {
        requested_by: string | null;
        requested_at: string | null;
        validated_by: string | null;
        validated_at: string | null;
        rejection_reason: string | null;
    };
}

const methodStyles: Record<string, { bg: string; text: string; label: string }> = {
    cash: { bg: 'bg-emerald-100', text: 'text-emerald-700', label: 'Cash' },
    bank_transfer: { bg: 'bg-blue-100', text: 'text-blue-700', label: 'Bank Transfer' },
    cheque: { bg: 'bg-amber-100', text: 'text-amber-700', label: 'Cheque' },
    effect: { bg: 'bg-violet-100', text: 'text-violet-700', label: 'Bill of Exchange' },
};

export default function Show({ invoice, canEdit, canDelete, canRequestValidation, canValidate, canDirectValidate, canSign, canSubmit, isAdmin, validationInfo }: Props) {
    const [showDelete, setShowDelete] = useState(false);
    const [showPayment, setShowPayment] = useState(false);
    const [showReject, setShowReject] = useState(false);
    const [actionLoading, setActionLoading] = useState<string | null>(null);
    const [rejectReason, setRejectReason] = useState('');

    // Payment calculations
    const paymentSummary = useMemo(() => {
        const totalPaid = invoice.payments?.reduce((sum, p) => sum + parseFloat(p.amount), 0) || 0;
        const totalTTC = parseFloat(invoice.total_ttc);
        const remaining = totalTTC - totalPaid;
        return {
            totalPaid,
            remaining: Math.max(0, remaining),
            isPaidInFull: remaining <= 0,
        };
    }, [invoice.payments, invoice.total_ttc]);

    const paymentForm = useForm({
        amount: '',
        method: 'bank_transfer',
        reference: '',
        payment_date: new Date().toISOString().split('T')[0],
    });

    const submitPayment = (e: FormEvent) => {
        e.preventDefault();
        paymentForm.post(`/invoices/${invoice.id}/payments`, {
            onSuccess: () => {
                setShowPayment(false);
                paymentForm.reset();
            },
        });
    };

    const performAction = (route: string, method: 'post' | 'delete' = 'post', actionName?: string, data?: Record<string, string | number | boolean>) => {
        setActionLoading(actionName || route);
        if (method === 'post') {
            router.post(route, data, {
                onFinish: () => setActionLoading(null),
            });
        } else {
            router.delete(route, {
                onFinish: () => setActionLoading(null),
            });
        }
    };

    const handleReject = () => {
        if (!rejectReason.trim()) {
            return;
        }
        performAction(`/invoices/${invoice.id}/reject-validation`, 'post', 'reject', { reason: rejectReason });
        setShowReject(false);
        setRejectReason('');
    };

    const statusColors: Record<string, string> = {
        draft: 'bg-gray-50 border-gray-200',
        pending_validation: 'bg-orange-50 border-orange-200',
        validated: 'bg-blue-50 border-blue-200',
        signed: 'bg-indigo-50 border-indigo-200',
        submitted: 'bg-yellow-50 border-yellow-200',
        accepted: 'bg-green-50 border-green-200',
        rejected: 'bg-red-50 border-red-200',
    };

    const canAddPayment = ['validated', 'signed', 'submitted', 'accepted'].includes(invoice.status) && !paymentSummary.isPaidInFull;

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
                            {/* Regular user: Request Validation */}
                            {canRequestValidation && (
                                <Button 
                                    size="sm" 
                                    variant="secondary" 
                                    onClick={() => performAction(`/invoices/${invoice.id}/request-validation`, 'post', 'requestValidation')}
                                    loading={actionLoading === 'requestValidation'}
                                >
                                    Request Validation
                                </Button>
                            )}
                            {/* Admin: Direct Validate from Draft */}
                            {canDirectValidate && (
                                <Button 
                                    size="sm" 
                                    variant="secondary" 
                                    onClick={() => performAction(`/invoices/${invoice.id}/validate`, 'post', 'validate')}
                                    loading={actionLoading === 'validate'}
                                >
                                    Validate
                                </Button>
                            )}
                            {/* Admin: Approve/Reject Pending Validation */}
                            {canValidate && (
                                <>
                                    <Button 
                                        size="sm" 
                                        onClick={() => performAction(`/invoices/${invoice.id}/validate`, 'post', 'approve')}
                                        loading={actionLoading === 'approve'}
                                        className="bg-green-600 hover:bg-green-700"
                                    >
                                        Approve
                                    </Button>
                                    <Button 
                                        size="sm" 
                                        variant="danger" 
                                        onClick={() => setShowReject(true)}
                                    >
                                        Reject
                                    </Button>
                                </>
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
                            <a href={`/invoices/${invoice.id}/pdf`} target="_blank" rel="noopener noreferrer">
                                <Button size="sm" variant="ghost">Download PDF</Button>
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

                    {/* Validation Info for pending_validation */}
                    {invoice.status === 'pending_validation' && validationInfo.requested_by && (
                        <div className="mt-4 p-3 bg-orange-100 rounded-lg text-sm">
                            <p className="font-medium text-orange-800">⏳ Pending Validation</p>
                            <p className="text-orange-700 mt-1">
                                Requested by <span className="font-medium">{validationInfo.requested_by}</span>
                                {validationInfo.requested_at && ` on ${formatDate(validationInfo.requested_at)}`}
                            </p>
                        </div>
                    )}

                    {/* Show validation rejection reason */}
                    {invoice.validation_rejection_reason && invoice.status === 'draft' && (
                        <div className="mt-4 p-3 bg-red-100 rounded-lg text-sm">
                            <p className="font-medium text-red-800">❌ Validation Rejected</p>
                            <p className="text-red-700 mt-1">{invoice.validation_rejection_reason}</p>
                            {validationInfo.validated_by && (
                                <p className="text-red-600 text-xs mt-2">
                                    Rejected by {validationInfo.validated_by}
                                    {validationInfo.validated_at && ` on ${formatDate(validationInfo.validated_at)}`}
                                </p>
                            )}
                        </div>
                    )}

                    {/* Workflow status indicator */}
                    <div className="mt-4 pt-4 border-t border-gray-200">
                        <div className="flex items-center justify-between text-xs overflow-x-auto">
                            {['draft', 'pending_validation', 'validated', 'signed', 'submitted', 'accepted'].map((step, idx) => {
                                const steps = ['draft', 'pending_validation', 'validated', 'signed', 'submitted', 'accepted'];
                                const currentIdx = steps.indexOf(invoice.status);
                                const isCompleted = idx < currentIdx || (idx === currentIdx && invoice.status === 'accepted');
                                const isCurrent = step === invoice.status;
                                const isRejected = invoice.status === 'rejected';
                                const isPending = step === 'pending_validation';

                                return (
                                    <div key={step} className="flex items-center flex-shrink-0">
                                        <div className={`flex h-8 w-8 items-center justify-center rounded-full text-xs font-medium ${
                                            isRejected && isCurrent ? 'bg-red-500 text-white' :
                                            isCurrent && isPending ? 'bg-orange-500 text-white' :
                                            isCompleted || isCurrent ? 'bg-green-500 text-white' : 
                                            'bg-gray-200 text-gray-500'
                                        }`}>
                                            {isCompleted ? '✓' : isCurrent && isPending ? '⏳' : idx + 1}
                                        </div>
                                        <span className={`ml-2 capitalize whitespace-nowrap ${isCurrent ? 'font-medium text-gray-900' : 'text-gray-500'}`}>
                                            {step === 'pending_validation' ? 'Pending' : step}
                                        </span>
                                        {idx < 5 && (
                                            <div className={`mx-3 h-0.5 w-8 ${isCompleted ? 'bg-green-500' : 'bg-gray-200'}`} />
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

                {/* Payments Section */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-semibold">Payments</h2>
                            <div className="mt-1 flex gap-4 text-sm">
                                <span className="text-gray-500">
                                    Paid: <span className="font-medium text-green-600">{formatTND(paymentSummary.totalPaid)}</span>
                                </span>
                                <span className="text-gray-500">
                                    Remaining: <span className={`font-medium ${paymentSummary.isPaidInFull ? 'text-green-600' : 'text-orange-600'}`}>
                                        {formatTND(paymentSummary.remaining)}
                                    </span>
                                </span>
                            </div>
                        </div>
                        {canAddPayment && (
                            <Button size="sm" onClick={() => setShowPayment(true)}>+ Payment</Button>
                        )}
                    </div>

                    {/* Payment Progress Bar */}
                    {parseFloat(invoice.total_ttc) > 0 && (
                        <div className="mb-4">
                            <div className="h-2 w-full rounded-full bg-gray-200">
                                <div 
                                    className={`h-2 rounded-full transition-all ${paymentSummary.isPaidInFull ? 'bg-green-500' : 'bg-indigo-500'}`}
                                    style={{ width: `${Math.min(100, (paymentSummary.totalPaid / parseFloat(invoice.total_ttc)) * 100)}%` }}
                                />
                            </div>
                            <p className="mt-1 text-xs text-gray-400 text-right">
                                {((paymentSummary.totalPaid / parseFloat(invoice.total_ttc)) * 100).toFixed(1)}% paid
                            </p>
                        </div>
                    )}

                    {(!invoice.payments || invoice.payments.length === 0) ? (
                        <p className="text-sm text-gray-500">No payments recorded.</p>
                    ) : (
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="px-3 py-2">Date</th>
                                    <th className="px-3 py-2">Method</th>
                                    <th className="px-3 py-2">Ref.</th>
                                    <th className="px-3 py-2">By</th>
                                    <th className="px-3 py-2 text-right">Amount</th>
                                    <th className="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {invoice.payments.map((p) => {
                                    const style = methodStyles[p.method] || methodStyles.cash;
                                    return (
                                        <tr key={p.id}>
                                            <td className="px-3 py-2">{formatDate(p.payment_date)}</td>
                                            <td className="px-3 py-2">
                                                <span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${style.bg} ${style.text}`}>
                                                    {style.label}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2">{p.reference || '—'}</td>
                                            <td className="px-3 py-2 text-gray-500">{p.creator || '—'}</td>
                                            <td className="px-3 py-2 text-right font-medium">{formatTND(p.amount)}</td>
                                            <td className="px-3 py-2 text-right">
                                                <button 
                                                    onClick={() => router.delete(`/invoices/${invoice.id}/payments/${p.id}`)} 
                                                    className="text-red-600 hover:underline text-xs"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </div>

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

            {/* Payment Modal */}
            <Modal show={showPayment} onClose={() => setShowPayment(false)} title="Record a Payment">
                <form onSubmit={submitPayment} className="space-y-4">
                    <div className="rounded-lg bg-blue-50 p-3 text-sm">
                        <div className="flex justify-between">
                            <span className="text-blue-600">Invoice Total:</span>
                            <span className="font-medium">{formatTND(invoice.total_ttc)}</span>
                        </div>
                        <div className="flex justify-between mt-1">
                            <span className="text-blue-600">Already Paid:</span>
                            <span className="font-medium">{formatTND(paymentSummary.totalPaid)}</span>
                        </div>
                        <hr className="my-2 border-blue-200" />
                        <div className="flex justify-between">
                            <span className="text-blue-700 font-medium">Remaining Balance:</span>
                            <span className="font-bold text-blue-800">{formatTND(paymentSummary.remaining)}</span>
                        </div>
                    </div>
                    <Input 
                        label="Amount (TND)" 
                        type="number" 
                        step="0.001" 
                        max={paymentSummary.remaining.toFixed(3)}
                        value={paymentForm.data.amount} 
                        onChange={(e) => paymentForm.setData('amount', e.target.value)} 
                        error={paymentForm.errors.amount} 
                        required 
                    />
                    <button 
                        type="button" 
                        className="text-xs text-indigo-600 hover:underline"
                        onClick={() => paymentForm.setData('amount', paymentSummary.remaining.toFixed(3))}
                    >
                        Fill remaining amount ({formatNumber(paymentSummary.remaining)} TND)
                    </button>
                    <Select label="Method" options={[
                        { value: 'cash', label: 'Cash' },
                        { value: 'bank_transfer', label: 'Bank Transfer' },
                        { value: 'cheque', label: 'Cheque' },
                        { value: 'effect', label: 'Bill of Exchange' },
                    ]} value={paymentForm.data.method} onChange={(e) => paymentForm.setData('method', e.target.value)} />
                    <Input label="Reference" value={paymentForm.data.reference} onChange={(e) => paymentForm.setData('reference', e.target.value)} />
                    <Input label="Payment Date" type="date" value={paymentForm.data.payment_date} onChange={(e) => paymentForm.setData('payment_date', e.target.value)} required />
                    <div className="flex justify-end gap-3 pt-2">
                        <Button type="button" variant="secondary" onClick={() => setShowPayment(false)}>Cancel</Button>
                        <Button type="submit" loading={paymentForm.processing}>Record Payment</Button>
                    </div>
                </form>
            </Modal>

            {/* Reject Validation Modal */}
            <Modal show={showReject} onClose={() => setShowReject(false)} title="Reject Validation Request">
                <div className="space-y-4">
                    <p className="text-sm text-gray-600">
                        Please provide a reason for rejecting the validation request. The user will be able to see this reason and make corrections.
                    </p>
                    <textarea 
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
                        rows={4}
                        placeholder="Enter rejection reason..."
                        value={rejectReason}
                        onChange={(e) => setRejectReason(e.target.value)}
                    />
                    <div className="flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setShowReject(false)}>Cancel</Button>
                        <Button 
                            variant="danger" 
                            onClick={handleReject}
                            disabled={!rejectReason.trim()}
                        >
                            Reject Validation
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
