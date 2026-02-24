import { Head, Link, useForm, router } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useState } from 'react';
import { InvoiceStatusBadge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Modal } from '@/Components/ui/Modal';
import { Select } from '@/Components/ui/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Invoice, Payment, PageProps } from '@/types';

interface Props extends PageProps {
    invoice: Invoice & {
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

export default function Show({ invoice, canValidate, canSign, canSubmit, canEdit, canDelete }: Props) {
    const [showPayment, setShowPayment] = useState(false);
    const [showDelete, setShowDelete] = useState(false);

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
            <Head title={`Invoice ${invoice.invoice_number}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className={`rounded-lg p-6 shadow ${statusColor[invoice.status] ?? 'bg-white'}`}>
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{invoice.invoice_number}</h1>
                            <p className="mt-1 text-sm text-gray-500">
                                Type: {invoice.document_type_code} &bull; Created on {new Date(invoice.created_at).toLocaleDateString('en-US')}
                                {invoice.creator && ` by ${invoice.creator.name}`}
                            </p>
                            <div className="mt-2"><InvoiceStatusBadge status={invoice.status} /></div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {canEdit && <Link href={`/invoices/${invoice.id}/edit`}><Button size="sm">Edit</Button></Link>}
                            {canValidate && <Button size="sm" variant="secondary" onClick={() => action(`/invoices/${invoice.id}/validate`)}>Validate</Button>}
                            {canSign && <Button size="sm" variant="secondary" onClick={() => action(`/invoices/${invoice.id}/sign`)}>Sign</Button>}
                            {canSubmit && <Button size="sm" onClick={() => action(`/invoices/${invoice.id}/submit`)}>Submit TTN</Button>}
                            <a href={`/invoices/${invoice.id}/pdf`} target="_blank"><Button size="sm" variant="ghost">PDF</Button></a>
                            <a href={`/invoices/${invoice.id}/xml`} target="_blank"><Button size="sm" variant="ghost">XML</Button></a>
                            <Button size="sm" variant="ghost" onClick={() => action(`/invoices/${invoice.id}/duplicate`)}>Duplicate</Button>
                            {canDelete && <Button size="sm" variant="danger" onClick={() => setShowDelete(true)}>Delete</Button>}
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Client */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold">Customer</h2>
                        <p className="font-medium">{invoice.customer.name}</p>
                        <p className="text-sm text-gray-500">{invoice.customer.identifier_type}: {invoice.customer.identifier_value}</p>
                        {invoice.customer.address_street && <p className="mt-2 text-sm text-gray-600">{invoice.customer.address_street}, {invoice.customer.address_city}</p>}
                    </div>

                    {/* Dates */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold">Dates</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between"><dt className="text-gray-500">Invoice Date</dt><dd>{invoice.invoice_date}</dd></div>
                            {invoice.due_date && <div className="flex justify-between"><dt className="text-gray-500">Due Date</dt><dd>{invoice.due_date}</dd></div>}
                        </dl>
                    </div>

                    {/* Totals */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold">Amounts</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between"><dt className="text-gray-500">Total excl. tax</dt><dd>{parseFloat(invoice.total_ht).toFixed(3)} TND</dd></div>
                            <div className="flex justify-between"><dt className="text-gray-500">Total VAT</dt><dd>{parseFloat(invoice.total_tva).toFixed(3)} TND</dd></div>
                            <div className="flex justify-between"><dt className="text-gray-500">Stamp duty</dt><dd>{parseFloat(invoice.timbre_fiscal).toFixed(3)} TND</dd></div>
                            <hr />
                            <div className="flex justify-between text-lg font-bold"><dt>Total incl. tax</dt><dd>{parseFloat(invoice.total_ttc).toFixed(3)} TND</dd></div>
                        </dl>
                    </div>
                </div>

                {/* Lines */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Invoice Lines</h2>
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
                                {invoice.lines.map((line, i) => (
                                    <tr key={line.id}>
                                        <td className="px-3 py-2">{i + 1}</td>
                                        <td className="px-3 py-2 font-mono text-xs">{line.item_code}</td>
                                        <td className="px-3 py-2">{line.item_description}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(line.quantity).toFixed(3)}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(line.unit_price).toFixed(3)}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(line.discount_rate).toFixed(2)}%</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(line.line_total_ht ?? '0').toFixed(3)}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(line.tva_rate).toFixed(0)}%</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(line.tva_amount).toFixed(3)}</td>
                                        <td className="px-3 py-2 text-right font-medium">{parseFloat(line.line_total_ttc ?? '0').toFixed(3)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Tax Summary */}
                {invoice.tax_lines.length > 0 && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">Tax Summary</h2>
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr><th className="px-3 py-2">Type</th><th className="px-3 py-2 text-right">Rate</th><th className="px-3 py-2 text-right">Taxable Amount</th><th className="px-3 py-2 text-right">Tax Amount</th></tr>
                            </thead>
                            <tbody className="divide-y">
                                {invoice.tax_lines.map((tl, i) => (
                                    <tr key={i}>
                                        <td className="px-3 py-2">{tl.tax_type_code}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(tl.tax_rate).toFixed(2)}%</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(tl.taxable_amount).toFixed(3)}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(tl.tax_amount).toFixed(3)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* TTN Info */}
                {invoice.ref_ttn_val && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-3 text-lg font-semibold">TTN Information</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between"><dt className="text-gray-500">TTN Ref.</dt><dd className="font-mono">{invoice.ref_ttn_val}</dd></div>
                            {invoice.cev_qr_content && <div className="flex justify-between"><dt className="text-gray-500">CEV / QR</dt><dd className="break-all font-mono text-xs">{invoice.cev_qr_content}</dd></div>}
                        </dl>
                    </div>
                )}

                {/* Payments */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Payments</h2>
                        {['accepted', 'validated', 'signed', 'submitted'].includes(invoice.status) && (
                            <Button size="sm" onClick={() => setShowPayment(true)}>+ Payment</Button>
                        )}
                    </div>
                    {invoice.payments.length === 0 ? (
                        <p className="text-sm text-gray-500">No payments recorded.</p>
                    ) : (
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr><th className="px-3 py-2">Date</th><th className="px-3 py-2">Method</th><th className="px-3 py-2">Ref.</th><th className="px-3 py-2 text-right">Amount</th><th className="px-3 py-2"></th></tr>
                            </thead>
                            <tbody className="divide-y">
                                {invoice.payments.map((p) => (
                                    <tr key={p.id}>
                                        <td className="px-3 py-2">{p.payment_date}</td>
                                        <td className="px-3 py-2 capitalize">{p.method.replace('_', ' ')}</td>
                                        <td className="px-3 py-2">{p.reference || '—'}</td>
                                        <td className="px-3 py-2 text-right font-medium">{parseFloat(p.amount).toFixed(3)} TND</td>
                                        <td className="px-3 py-2 text-right">
                                            <button onClick={() => router.delete(`/invoices/${invoice.id}/payments/${p.id}`)} className="text-red-600 hover:underline text-xs">Delete</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* TTN Logs */}
                {invoice.ttn_logs && invoice.ttn_logs.length > 0 && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">TTN Log</h2>
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr><th className="px-3 py-2">Date</th><th className="px-3 py-2">Direction</th><th className="px-3 py-2">HTTP</th></tr>
                            </thead>
                            <tbody className="divide-y">
                                {invoice.ttn_logs.map((log) => (
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
                {invoice.notes && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-2 text-lg font-semibold">Notes</h2>
                        <p className="text-sm text-gray-700 whitespace-pre-line">{invoice.notes}</p>
                    </div>
                )}
            </div>

            {/* Payment Modal */}
            <Modal show={showPayment} onClose={() => setShowPayment(false)} title="Record a Payment">
                <form onSubmit={submitPayment} className="space-y-4">
                    <Input label="Amount (TND)" type="number" step="0.001" value={paymentForm.data.amount} onChange={(e) => paymentForm.setData('amount', e.target.value)} error={paymentForm.errors.amount} required />
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
                <p className="text-sm text-gray-600">Delete invoice <strong>{invoice.invoice_number}</strong>? This action is irreversible.</p>
                <div className="mt-4 flex justify-end gap-3">
                    <Button variant="secondary" onClick={() => setShowDelete(false)}>Cancel</Button>
                    <Button variant="danger" onClick={() => router.delete(`/invoices/${invoice.id}`)}>Delete</Button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
