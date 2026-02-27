import { Head, Link, useForm, router } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useState, useMemo } from 'react';
import { OldInvoiceStatusBadge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Modal } from '@/Components/ui/Modal';
import { Select } from '@/Components/ui/Select';
import { formatTND, formatNumber } from '@/utils/format';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { OldInvoice, Payment, PageProps } from '@/types';

interface Props extends PageProps {
    oldinvoice: OldInvoice & {
        customer: { id: string; name: string; identifier_value: string; identifier_type: string; address_street: string; address_city: string };
        lines: Array<{
            id: string;
            item_code: string;
            item_description: string;
            quantity: string;
            unit_price: string;
            discount_rate: string;
            line_total_ht: string;
            tva_rate: string;
            tva_amount: string;
            line_total_ttc: string;
        }>;
        tax_lines: Array<{ tax_type_code: string; tax_rate: string; taxable_amount: string; tax_amount: string }>;
        payments: Payment[];
        ttn_logs: Array<{ id: string; direction: string; http_status: number; created_at: string }>;
        creator: { name: string };
    };
    canValidate: boolean;
    canSign: boolean;
    canSubmit: boolean;
    canEdit: boolean;
    canDelete: boolean;
}

export default function Show({ oldinvoice, canValidate, canSign, canSubmit, canEdit, canDelete }: Props) {
    const [showPayment, setShowPayment] = useState(false);
    const [showDelete, setShowDelete] = useState(false);

    // Calculate paid amount and remaining balance
    const paymentSummary = useMemo(() => {
        const totalPaid = oldinvoice.payments.reduce((sum, p) => sum + parseFloat(p.amount), 0);
        const totalTTC = parseFloat(oldinvoice.total_ttc);
        const remaining = totalTTC - totalPaid;
        return {
            totalPaid,
            remaining: Math.max(0, remaining),
            isPaidInFull: remaining <= 0,
        };
    }, [oldinvoice.payments, oldinvoice.total_ttc]);

    const paymentForm = useForm({
        amount: '',
        method: 'bank_transfer',
        reference: '',
        payment_date: new Date().toISOString().split('T')[0],
    });

    const submitPayment = (e: FormEvent) => {
        e.preventDefault();
        paymentForm.post(`/oldinvoices/${oldinvoice.id}/payments`, {
            onSuccess: () => {
                setShowPayment(false);
                paymentForm.reset();
            },
        });
    };

    const action = (route: string, method: 'post' | 'delete' = 'post') => {
        router[method](route);
    };

    const statusColor: Record<string, string> = {
        draft: 'bg-gray-100',
        validated: 'bg-blue-50',
        signed: 'bg-indigo-50',
        submitted: 'bg-yellow-50',
        accepted: 'bg-green-50',
        rejected: 'bg-red-50',
        archived: 'bg-gray-50',
    };

    return (
        <AuthenticatedLayout>
            <Head title={`OldInvoice ${oldinvoice.oldinvoice_number}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className={`rounded-lg p-6 shadow ${statusColor[oldinvoice.status] ?? 'bg-white'}`}>
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{oldinvoice.oldinvoice_number}</h1>
                            <p className="mt-1 text-sm text-gray-500">
                                Type: {oldinvoice.document_type_code} &bull; Created on {new Date(oldinvoice.created_at).toLocaleDateString('en-US')}
                                {oldinvoice.creator && ` by ${oldinvoice.creator.name}`}
                            </p>
                            <div className="mt-2"><OldInvoiceStatusBadge status={oldinvoice.status} /></div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {canEdit && <Link href={`/oldinvoices/${oldinvoice.id}/edit`}><Button size="sm">Edit</Button></Link>}
                            {canValidate && <Button size="sm" variant="secondary" onClick={() => action(`/oldinvoices/${oldinvoice.id}/validate`)}>Validate</Button>}
                            {canSign && <Button size="sm" variant="secondary" onClick={() => action(`/oldinvoices/${oldinvoice.id}/sign`)}>Sign</Button>}
                            {canSubmit && <Button size="sm" onClick={() => action(`/oldinvoices/${oldinvoice.id}/submit`)}>Submit TTN</Button>}
                            <a href={`/oldinvoices/${oldinvoice.id}/pdf`} target="_blank"><Button size="sm" variant="ghost">PDF</Button></a>
                            <a href={`/oldinvoices/${oldinvoice.id}/xml`} target="_blank"><Button size="sm" variant="ghost">XML</Button></a>
                            <Button size="sm" variant="ghost" onClick={() => action(`/oldinvoices/${oldinvoice.id}/duplicate`)}>Duplicate</Button>
                            {canDelete && <Button size="sm" variant="danger" onClick={() => setShowDelete(true)}>Delete</Button>}
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Client */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold">Customer</h2>
                        <p className="font-medium">{oldinvoice.customer.name}</p>
                        <p className="text-sm text-gray-500">{oldinvoice.customer.identifier_type}: {oldinvoice.customer.identifier_value}</p>
                        {oldinvoice.customer.address_street && <p className="mt-2 text-sm text-gray-600">{oldinvoice.customer.address_street}, {oldinvoice.customer.address_city}</p>}
                    </div>

                    {/* Dates */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold">Dates</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between"><dt className="text-gray-500">OldInvoice Date</dt><dd>{oldinvoice.oldinvoice_date}</dd></div>
                            {oldinvoice.due_date && <div className="flex justify-between"><dt className="text-gray-500">Due Date</dt><dd>{oldinvoice.due_date}</dd></div>}
                        </dl>
                    </div>

                    {/* Totals */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold">Amounts</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between"><dt className="text-gray-500">Total excl. tax</dt><dd>{formatTND(oldinvoice.total_ht)}</dd></div>
                            <div className="flex justify-between"><dt className="text-gray-500">Total VAT</dt><dd>{formatTND(oldinvoice.total_tva)}</dd></div>
                            <div className="flex justify-between"><dt className="text-gray-500">Stamp duty</dt><dd>{formatTND(oldinvoice.timbre_fiscal)}</dd></div>
                            <hr />
                            <div className="flex justify-between text-lg font-bold"><dt>Total incl. tax</dt><dd>{formatTND(oldinvoice.total_ttc)}</dd></div>
                        </dl>
                    </div>
                </div>

                {/* Lines */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">OldInvoice Lines</h2>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="px-3 py-2">#</th>
                                    <th className="px-3 py-2">Code</th>
                                    <th className="px-3 py-2">Description</th>
                                    <th className="px-3 py-2 text-right">Qty</th>
                                    <th className="px-3 py-2 text-right">Unit Price</th>
                                    <th className="px-3 py-2 text-right">Discount</th>
                                    <th className="px-3 py-2 text-right">Excl. tax</th>
                                    <th className="px-3 py-2 text-right">VAT %</th>
                                    <th className="px-3 py-2 text-right">VAT</th>
                                    <th className="px-3 py-2 text-right">Incl. tax</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {oldinvoice.lines.map((line, i) => (
                                    <tr key={line.id}>
                                        <td className="px-3 py-2">{i + 1}</td>
                                        <td className="px-3 py-2 font-mono text-xs">{line.item_code}</td>
                                        <td className="px-3 py-2">{line.item_description}</td>
                                        <td className="px-3 py-2 text-right">{formatNumber(line.quantity)}</td>
                                        <td className="px-3 py-2 text-right">{formatNumber(line.unit_price)}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(line.discount_rate).toFixed(2)}%</td>
                                        <td className="px-3 py-2 text-right">{formatNumber(line.line_total_ht)}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(line.tva_rate).toFixed(0)}%</td>
                                        <td className="px-3 py-2 text-right">{formatNumber(line.tva_amount)}</td>
                                        <td className="px-3 py-2 text-right font-medium">{formatNumber(line.line_total_ttc)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Tax Summary */}
                {oldinvoice.tax_lines.length > 0 && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">Tax Summary</h2>
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr><th className="px-3 py-2">Type</th><th className="px-3 py-2 text-right">Rate</th><th className="px-3 py-2 text-right">Taxable Amount</th><th className="px-3 py-2 text-right">Tax Amount</th></tr>
                            </thead>
                            <tbody className="divide-y">
                                {oldinvoice.tax_lines.map((tl, i) => (
                                    <tr key={i}>
                                        <td className="px-3 py-2">{tl.tax_type_code}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(tl.tax_rate).toFixed(2)}%</td>
                                        <td className="px-3 py-2 text-right">{formatNumber(tl.taxable_amount)}</td>
                                        <td className="px-3 py-2 text-right">{formatNumber(tl.tax_amount)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* TTN Info */}
                {oldinvoice.ref_ttn_val && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold">TTN Information</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between"><dt className="text-gray-500">TTN Ref.</dt><dd className="font-mono">{oldinvoice.ref_ttn_val}</dd></div>
                            {oldinvoice.cev_qr_content && <div className="flex justify-between"><dt className="text-gray-500">CEV / QR</dt><dd className="break-all font-mono text-xs">{oldinvoice.cev_qr_content}</dd></div>}
                        </dl>
                    </div>
                )}

                {/* Payments */}
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
                        {['accepted', 'validated', 'signed', 'submitted'].includes(oldinvoice.status) && !paymentSummary.isPaidInFull && (
                            <Button size="sm" onClick={() => setShowPayment(true)}>+ Payment</Button>
                        )}
                    </div>

                    {/* Payment Progress Bar */}
                    {parseFloat(oldinvoice.total_ttc) > 0 && (
                        <div className="mb-4">
                            <div className="h-2 w-full rounded-full bg-gray-200">
                                <div 
                                    className={`h-2 rounded-full transition-all ${paymentSummary.isPaidInFull ? 'bg-green-500' : 'bg-indigo-500'}`}
                                    style={{ width: `${Math.min(100, (paymentSummary.totalPaid / parseFloat(oldinvoice.total_ttc)) * 100)}%` }}
                                />
                            </div>
                            <p className="mt-1 text-xs text-gray-400 text-right">
                                {((paymentSummary.totalPaid / parseFloat(oldinvoice.total_ttc)) * 100).toFixed(1)}% paid
                            </p>
                        </div>
                    )}

                    {oldinvoice.payments.length === 0 ? (
                        <p className="text-sm text-gray-500">No payments recorded.</p>
                    ) : (
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr><th className="px-3 py-2">Date</th><th className="px-3 py-2">Method</th><th className="px-3 py-2">Ref.</th><th className="px-3 py-2 text-right">Amount</th><th className="px-3 py-2"></th></tr>
                            </thead>
                            <tbody className="divide-y">
                                {oldinvoice.payments.map((p) => (
                                    <tr key={p.id}>
                                        <td className="px-3 py-2">{p.payment_date}</td>
                                        <td className="px-3 py-2 capitalize">{p.method.replace('_', ' ')}</td>
                                        <td className="px-3 py-2">{p.reference || '—'}</td>
                                        <td className="px-3 py-2 text-right font-medium">{formatTND(p.amount)}</td>
                                        <td className="px-3 py-2 text-right">
                                            <button onClick={() => router.delete(`/oldinvoices/${oldinvoice.id}/payments/${p.id}`)} className="text-red-600 hover:underline text-xs">Delete</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* TTN Logs */}
                {oldinvoice.ttn_logs && oldinvoice.ttn_logs.length > 0 && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">TTN Log</h2>
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr><th className="px-3 py-2">Date</th><th className="px-3 py-2">Direction</th><th className="px-3 py-2">HTTP</th></tr>
                            </thead>
                            <tbody className="divide-y">
                                {oldinvoice.ttn_logs.map((log) => (
                                    <tr key={log.id}>
                                        <td className="px-3 py-2">{new Date(log.created_at).toLocaleString('en-US')}</td>
                                        <td className="px-3 py-2">{log.direction}</td>
                                        <td className="px-3 py-2">{log.http_status}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Notes */}
                {oldinvoice.notes && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-2 text-lg font-semibold">Notes</h2>
                        <p className="text-sm text-gray-700 whitespace-pre-line">{oldinvoice.notes}</p>
                    </div>
                )}
            </div>

            {/* Payment Modal */}
            <Modal show={showPayment} onClose={() => setShowPayment(false)} title="Record a Payment">
                <form onSubmit={submitPayment} className="space-y-4">
                    <div className="rounded-lg bg-blue-50 p-3 text-sm">
                        <div className="flex justify-between">
                            <span className="text-blue-600">Invoice Total:</span>
                            <span className="font-medium">{formatTND(oldinvoice.total_ttc)}</span>
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
                    <Input label="Date" type="date" value={paymentForm.data.payment_date} onChange={(e) => paymentForm.setData('payment_date', e.target.value)} required />
                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="secondary" onClick={() => setShowPayment(false)}>Cancel</Button>
                        <Button type="submit" loading={paymentForm.processing}>Save</Button>
                    </div>
                </form>
            </Modal>

            {/* Delete Modal */}
            <Modal show={showDelete} onClose={() => setShowDelete(false)} title="Confirm Deletion">
                <p className="text-sm text-gray-600">Delete oldinvoice <strong>{oldinvoice.oldinvoice_number}</strong>? This action is irreversible.</p>
                <div className="mt-4 flex justify-end gap-3">
                    <Button variant="secondary" onClick={() => setShowDelete(false)}>Cancel</Button>
                    <Button variant="danger" onClick={() => router.delete(`/oldinvoices/${oldinvoice.id}`)}>Delete</Button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
