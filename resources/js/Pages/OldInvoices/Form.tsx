import { Head, useForm, Link } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface LineData {
    product_id: string;
    item_code: string;
    item_description: string;
    item_lang: string;
    quantity: string;
    unit_of_measure: string;
    unit_price: string;
    discount_rate: string;
    tva_rate: string;
}

interface OldInvoiceFormData {
    customer_id: string;
    document_type_code: string;
    oldinvoice_date: string;
    due_date: string;
    billing_period_start: string;
    billing_period_end: string;
    parent_oldinvoice_id: string;
    timbre_fiscal: string;
    notes: string;
    lines: LineData[];
}

interface Product {
    id: string;
    code: string;
    name: string;
    unit_price: string;
    unit_of_measure: string;
    tva_rate: string;
    is_subject_to_timbre: boolean;
}

import type { OldInvoice } from '@/types';

interface Props {
    customers: Array<{ id: string; name: string; identifier_value: string }>;
    products: Product[];
    documentTypes: Array<{ value: string; label: string }>;
    oldinvoice?: Partial<OldInvoice>;
    isEdit?: boolean;
}

const emptyLine: LineData = {
    product_id: '',
    item_code: '',
    item_description: '',
    item_lang: 'fr',
    quantity: '1',
    unit_of_measure: 'U',
    unit_price: '0',
    discount_rate: '0',
    tva_rate: '19',
};

export default function OldInvoiceForm({ customers, products, documentTypes, oldinvoice, isEdit = false }: Props) {
    const { data, setData, post, put, processing, errors } = useForm<OldInvoiceFormData>({
        customer_id: oldinvoice?.customer_id ?? '',
        document_type_code: oldinvoice?.document_type_code ?? 'I-11',
        oldinvoice_date: oldinvoice?.oldinvoice_date ?? new Date().toISOString().split('T')[0],
        due_date: oldinvoice?.due_date ?? '',
        billing_period_start: oldinvoice?.billing_period_start ?? '',
        billing_period_end: oldinvoice?.billing_period_end ?? '',
        parent_oldinvoice_id: oldinvoice?.parent_oldinvoice_id ?? '',
        timbre_fiscal: oldinvoice?.timbre_fiscal ?? '1.000',
        notes: oldinvoice?.notes ?? '',
        lines: oldinvoice?.lines?.length
            ? oldinvoice.lines.map((l) => ({
                product_id: l.product_id ?? '',
                item_code: l.item_code ?? '',
                item_description: l.item_description ?? '',
                item_lang: l.item_lang ?? 'fr',
                quantity: String(l.quantity ?? '1'),
                unit_of_measure: l.unit_of_measure ?? 'U',
                unit_price: String(l.unit_price ?? '0'),
                discount_rate: String(l.discount_rate ?? '0'),
                tva_rate: String(l.tva_rate ?? '19'),
            }))
            : [{ ...emptyLine }],
    });

    const addLine = () => {
        setData('lines', [...data.lines, { ...emptyLine }]);
    };

    const removeLine = (index: number) => {
        if (data.lines.length <= 1) return;
        setData('lines', data.lines.filter((_, i) => i !== index));
    };

    const updateLine = (index: number, field: keyof LineData, value: string) => {
        const updated = [...data.lines];
        updated[index] = { ...updated[index], [field]: value };
        setData('lines', updated);
    };

    const selectProduct = (index: number, productId: string) => {
        const product = products.find((p) => p.id === productId);
        if (!product) return;
        const updated = [...data.lines];
        updated[index] = {
            ...updated[index],
            product_id: product.id,
            item_code: product.code,
            item_description: product.name,
            unit_price: product.unit_price,
            unit_of_measure: product.unit_of_measure,
            tva_rate: product.tva_rate,
        };
        setData('lines', updated);
    };

    // Calculate live totals
    const lineTotals = data.lines.map((line) => {
        const qty = parseFloat(line.quantity) || 0;
        const price = parseFloat(line.unit_price) || 0;
        const discountRate = parseFloat(line.discount_rate) || 0;
        const gross = qty * price;
        const discount = gross * (discountRate / 100);
        const net = gross - discount;
        const tva = net * (parseFloat(line.tva_rate) / 100);
        return { gross, discount, net, tva, total: net + tva };
    });

    const totalHT = lineTotals.reduce((s, l) => s + l.net, 0);
    const totalTVA = lineTotals.reduce((s, l) => s + l.tva, 0);
    const timbre = parseFloat(data.timbre_fiscal) || 0;
    const totalTTC = totalHT + totalTVA + timbre;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit && oldinvoice?.id) {
            put(`/oldinvoices/${oldinvoice.id}`);
        } else {
            post('/oldinvoices');
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={isEdit ? 'Edit OldInvoice' : 'New OldInvoice'} />

            <form onSubmit={submit} className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">
                        {isEdit ? `Edit: ${oldinvoice?.oldinvoice_number}` : 'New OldInvoice'}
                    </h1>
                    <Link href="/oldinvoices"><Button variant="ghost" type="button">Back</Button></Link>
                </div>

                {/* Header */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Header</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Select
                            label="Customer"
                            options={customers.map((c) => ({ value: c.id, label: `${c.name} (${c.identifier_value})` }))}
                            value={data.customer_id}
                            onChange={(e) => setData('customer_id', e.target.value)}
                            error={errors.customer_id}
                            placeholder="Select a customer"
                        />
                        <Select
                            label="Document Type"
                            options={documentTypes}
                            value={data.document_type_code}
                            onChange={(e) => setData('document_type_code', e.target.value)}
                            error={errors.document_type_code}
                        />
                        <Input label="OldInvoice Date" type="date" value={data.oldinvoice_date} onChange={(e) => setData('oldinvoice_date', e.target.value)} error={errors.oldinvoice_date} required />
                        <Input label="Due Date" type="date" value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} error={errors.due_date} />
                        <Input label="Stamp Duty (TND)" type="number" step="0.001" value={data.timbre_fiscal} onChange={(e) => setData('timbre_fiscal', e.target.value)} error={errors.timbre_fiscal} />
                    </div>
                </div>

                {/* Lines */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">OldInvoice Lines</h2>
                        <Button type="button" size="sm" onClick={addLine}>+ Add Line</Button>
                    </div>

                    {errors.lines && <p className="mb-2 text-sm text-red-600">{errors.lines}</p>}

                    <div className="space-y-4">
                        {data.lines.map((line, index) => (
                            <div key={index} className="rounded-lg border border-gray-200 p-4">
                                <div className="mb-3 flex items-center justify-between">
                                    <span className="text-sm font-medium text-gray-500">Line {index + 1}</span>
                                    {data.lines.length > 1 && (
                                        <button type="button" onClick={() => removeLine(index)} className="text-sm text-red-600 hover:text-red-800">
                                            Delete
                                        </button>
                                    )}
                                </div>

                                <div className="grid gap-3 sm:grid-cols-6">
                                    <div className="sm:col-span-2">
                                        <Select
                                            label="Product"
                                            options={products.map((p) => ({ value: p.id, label: `${p.code} - ${p.name}` }))}
                                            value={line.product_id}
                                            onChange={(e) => selectProduct(index, e.target.value)}
                                            placeholder="Select..."
                                        />
                                    </div>
                                    <Input label="Code" value={line.item_code} onChange={(e) => updateLine(index, 'item_code', e.target.value)} />
                                    <div className="sm:col-span-3">
                                        <Input label="Description" value={line.item_description} onChange={(e) => updateLine(index, 'item_description', e.target.value)} />
                                    </div>
                                    <Input label="Quantity" type="number" step="0.001" value={line.quantity} onChange={(e) => updateLine(index, 'quantity', e.target.value)} />
                                    <Input label="Unit Price" type="number" step="0.001" value={line.unit_price} onChange={(e) => updateLine(index, 'unit_price', e.target.value)} />
                                    <Input label="Discount %" type="number" step="0.01" value={line.discount_rate} onChange={(e) => updateLine(index, 'discount_rate', e.target.value)} />
                                    <Input label="VAT %" type="number" step="1" value={line.tva_rate} onChange={(e) => updateLine(index, 'tva_rate', e.target.value)} />
                                    <div className="sm:col-span-2 flex items-end">
                                        <div className="w-full rounded bg-gray-50 px-3 py-2 text-right">
                                            <span className="text-xs text-gray-500">Line Total</span>
                                            <p className="font-semibold">{lineTotals[index]?.total.toFixed(3)} TND</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Totals */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="ml-auto max-w-xs space-y-2">
                        <div className="flex justify-between text-sm"><span className="text-gray-500">Total excl. tax</span><span>{totalHT.toFixed(3)} TND</span></div>
                        <div className="flex justify-between text-sm"><span className="text-gray-500">Total VAT</span><span>{totalTVA.toFixed(3)} TND</span></div>
                        <div className="flex justify-between text-sm"><span className="text-gray-500">Stamp duty</span><span>{timbre.toFixed(3)} TND</span></div>
                        <hr />
                        <div className="flex justify-between text-lg font-bold"><span>Total incl. tax</span><span>{totalTTC.toFixed(3)} TND</span></div>
                    </div>
                </div>

                {/* Notes */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <label className="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" rows={3} value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                </div>

                {/* Actions */}
                <div className="flex justify-end gap-3">
                    <Link href="/oldinvoices"><Button variant="secondary" type="button">Cancel</Button></Link>
                    <Button type="submit" loading={processing}>
                        {isEdit ? 'Update' : 'Create OldInvoice'}
                    </Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
