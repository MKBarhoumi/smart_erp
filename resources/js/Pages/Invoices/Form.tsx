import { Head, useForm, Link } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Customer, Product, InvoiceFormData, InvoiceFormLine } from '@/types';

interface Props {
    customers: Customer[];
    products: Product[];
    documentTypes: Array<{ value: string; label: string }>;
    identifierTypes: Array<{ value: string; label: string }>;
    companySettings: {
        identifier: string;
        name: string;
        street: string;
        city: string;
        postal_code: string;
        country: string;
    };
    invoice?: Partial<InvoiceFormData & { id: string; document_identifier: string }>;
    isEdit?: boolean;
}

const emptyLine: InvoiceFormLine = {
    product_id: '',
    item_code: '',
    item_description: '',
    quantity: '1',
    unit_of_measure: 'UNIT',
    unit_price: '0',
    tva_rate: '19',
};

export default function InvoiceForm({ 
    customers, 
    products, 
    documentTypes, 
    identifierTypes,
    companySettings,
    invoice, 
    isEdit = false 
}: Props) {
    const { data, setData, post, put, processing, errors } = useForm<InvoiceFormData>({
        document_type_code: invoice?.document_type_code ?? 'I-11',
        invoice_date: invoice?.invoice_date ?? new Date().toISOString().split('T')[0],
        due_date: invoice?.due_date ?? '',
        notes: invoice?.notes ?? '',
        
        // Sender (seller) - pre-fill from company settings
        sender_identifier: invoice?.sender_identifier ?? companySettings.identifier,
        sender_type: invoice?.sender_type ?? 'I-01',
        sender_name: invoice?.sender_name ?? companySettings.name,
        sender_street: invoice?.sender_street ?? companySettings.street,
        sender_city: invoice?.sender_city ?? companySettings.city,
        sender_postal_code: invoice?.sender_postal_code ?? companySettings.postal_code,
        sender_country: invoice?.sender_country ?? (companySettings.country || 'TN'),
        
        // Receiver (buyer)
        receiver_identifier: invoice?.receiver_identifier ?? '',
        receiver_type: invoice?.receiver_type ?? 'I-01',
        receiver_name: invoice?.receiver_name ?? '',
        receiver_street: invoice?.receiver_street ?? '',
        receiver_city: invoice?.receiver_city ?? '',
        receiver_postal_code: invoice?.receiver_postal_code ?? '',
        receiver_country: invoice?.receiver_country ?? 'TN',
        
        // Lines
        lines: invoice?.lines?.length
            ? invoice.lines.map((l) => ({
                product_id: '',
                item_code: l.item_code ?? '',
                item_description: l.item_description ?? '',
                quantity: String(l.quantity ?? '1'),
                unit_of_measure: l.unit_of_measure ?? 'UNIT',
                unit_price: String(l.unit_price ?? '0'),
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

    const updateLine = (index: number, field: keyof InvoiceFormLine, value: string) => {
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

    const selectCustomer = (customerId: string) => {
        const customer = customers.find((c) => c.id === customerId);
        if (!customer) return;
        setData({
            ...data,
            receiver_identifier: customer.identifier_value,
            receiver_type: customer.identifier_type,
            receiver_name: customer.name,
            receiver_street: customer.street ?? '',
            receiver_city: customer.city ?? '',
            receiver_postal_code: customer.postal_code ?? '',
            receiver_country: customer.country_code ?? 'TN',
        });
    };

    // Calculate live totals
    const lineTotals = data.lines.map((line) => {
        const qty = parseFloat(line.quantity) || 0;
        const price = parseFloat(line.unit_price) || 0;
        const net = qty * price;
        const tva = net * (parseFloat(line.tva_rate) / 100);
        return { net, tva, total: net + tva };
    });

    const totalHT = lineTotals.reduce((s, l) => s + l.net, 0);
    const totalTVA = lineTotals.reduce((s, l) => s + l.tva, 0);
    const totalTTC = totalHT + totalTVA;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit && invoice?.id) {
            put(`/invoices/${invoice.id}`);
        } else {
            post('/invoices');
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={isEdit ? 'Edit Invoice' : 'New Invoice'} />

            <form onSubmit={submit} className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            {isEdit ? `Edit: ${invoice?.document_identifier}` : 'New Invoice'}
                        </h1>
                        <p className="text-sm text-gray-500">TEIF-compliant invoice following Elfatoora specifications</p>
                    </div>
                    <Link href="/invoices"><Button variant="ghost" type="button">Back to list</Button></Link>
                </div>

                {/* Document Header */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">Document Information</h2>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Select
                            label="Document Type"
                            options={documentTypes}
                            value={data.document_type_code}
                            onChange={(e) => setData('document_type_code', e.target.value)}
                            error={errors.document_type_code}
                        />
                        <Input 
                            label="Invoice Date" 
                            type="date" 
                            value={data.invoice_date} 
                            onChange={(e) => setData('invoice_date', e.target.value)} 
                            error={errors.invoice_date} 
                            required 
                        />
                        <Input 
                            label="Due Date" 
                            type="date" 
                            value={data.due_date} 
                            onChange={(e) => setData('due_date', e.target.value)} 
                            error={errors.due_date} 
                        />
                    </div>
                </div>

                {/* Sender & Receiver */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Sender (Seller) */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-4 flex items-center gap-2">
                            <div className="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">I-62</div>
                            <h2 className="text-lg font-semibold text-gray-900">Sender (Seller)</h2>
                        </div>
                        <div className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Input
                                    label="Identifier (Matricule Fiscal)"
                                    value={data.sender_identifier}
                                    onChange={(e) => setData('sender_identifier', e.target.value)}
                                    error={errors.sender_identifier}
                                    placeholder="0736202XAM000"
                                    required
                                />
                                <Select
                                    label="Identifier Type"
                                    options={identifierTypes}
                                    value={data.sender_type}
                                    onChange={(e) => setData('sender_type', e.target.value)}
                                    error={errors.sender_type}
                                />
                            </div>
                            <Input
                                label="Company Name"
                                value={data.sender_name}
                                onChange={(e) => setData('sender_name', e.target.value)}
                                error={errors.sender_name}
                                required
                            />
                            <Input
                                label="Street Address"
                                value={data.sender_street}
                                onChange={(e) => setData('sender_street', e.target.value)}
                                error={errors.sender_street}
                            />
                            <div className="grid gap-4 sm:grid-cols-3">
                                <Input
                                    label="City"
                                    value={data.sender_city}
                                    onChange={(e) => setData('sender_city', e.target.value)}
                                    error={errors.sender_city}
                                />
                                <Input
                                    label="Postal Code"
                                    value={data.sender_postal_code}
                                    onChange={(e) => setData('sender_postal_code', e.target.value)}
                                    error={errors.sender_postal_code}
                                />
                                <Input
                                    label="Country"
                                    value={data.sender_country}
                                    onChange={(e) => setData('sender_country', e.target.value)}
                                    error={errors.sender_country}
                                    placeholder="TN"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Receiver (Buyer) */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-4 flex items-center gap-2">
                            <div className="rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-800">I-64</div>
                            <h2 className="text-lg font-semibold text-gray-900">Receiver (Buyer)</h2>
                        </div>

                        {/* Quick select from customers */}
                        <div className="mb-4">
                            <Select
                                label="Select from customers"
                                options={[
                                    { value: '', label: 'Select a customer...' },
                                    ...customers.map((c) => ({ value: c.id, label: `${c.name} (${c.identifier_value})` }))
                                ]}
                                value=""
                                onChange={(e) => selectCustomer(e.target.value)}
                                placeholder="Quick fill from existing customer"
                            />
                        </div>

                        <div className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Input
                                    label="Identifier (Matricule Fiscal)"
                                    value={data.receiver_identifier}
                                    onChange={(e) => setData('receiver_identifier', e.target.value)}
                                    error={errors.receiver_identifier}
                                    placeholder="0914089JAM000"
                                    required
                                />
                                <Select
                                    label="Identifier Type"
                                    options={identifierTypes}
                                    value={data.receiver_type}
                                    onChange={(e) => setData('receiver_type', e.target.value)}
                                    error={errors.receiver_type}
                                />
                            </div>
                            <Input
                                label="Company / Person Name"
                                value={data.receiver_name}
                                onChange={(e) => setData('receiver_name', e.target.value)}
                                error={errors.receiver_name}
                                required
                            />
                            <Input
                                label="Street Address"
                                value={data.receiver_street}
                                onChange={(e) => setData('receiver_street', e.target.value)}
                                error={errors.receiver_street}
                            />
                            <div className="grid gap-4 sm:grid-cols-3">
                                <Input
                                    label="City"
                                    value={data.receiver_city}
                                    onChange={(e) => setData('receiver_city', e.target.value)}
                                    error={errors.receiver_city}
                                />
                                <Input
                                    label="Postal Code"
                                    value={data.receiver_postal_code}
                                    onChange={(e) => setData('receiver_postal_code', e.target.value)}
                                    error={errors.receiver_postal_code}
                                />
                                <Input
                                    label="Country"
                                    value={data.receiver_country}
                                    onChange={(e) => setData('receiver_country', e.target.value)}
                                    error={errors.receiver_country}
                                    placeholder="TN"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Lines */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900">Invoice Lines</h2>
                            <p className="text-sm text-gray-500">Add items/services to this invoice</p>
                        </div>
                        <Button type="button" size="sm" onClick={addLine}>+ Add Line</Button>
                    </div>

                    {errors.lines && <p className="mb-2 text-sm text-red-600">{errors.lines}</p>}

                    <div className="space-y-4">
                        {data.lines.map((line, index) => (
                            <div key={index} className="rounded-lg border border-gray-200 p-4 transition-colors hover:border-gray-300">
                                <div className="mb-3 flex items-center justify-between">
                                    <span className="flex items-center gap-2 text-sm font-medium text-gray-500">
                                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs">{index + 1}</span>
                                        Line Item
                                    </span>
                                    {data.lines.length > 1 && (
                                        <button 
                                            type="button" 
                                            onClick={() => removeLine(index)} 
                                            className="text-sm text-red-600 hover:text-red-800 transition-colors"
                                        >
                                            Remove
                                        </button>
                                    )}
                                </div>

                                <div className="grid gap-3 sm:grid-cols-7">
                                    <div className="sm:col-span-2">
                                        <Select
                                            label="Product"
                                            options={[
                                                { value: '', label: 'Select product...' },
                                                ...products.map((p) => ({ value: p.id, label: `${p.code} - ${p.name}` }))
                                            ]}
                                            value={line.product_id}
                                            onChange={(e) => selectProduct(index, e.target.value)}
                                        />
                                    </div>
                                    <Input 
                                        label="Code" 
                                        value={line.item_code} 
                                        onChange={(e) => updateLine(index, 'item_code', e.target.value)} 
                                        error={(errors as Record<string, string>)[`lines.${index}.item_code`]}
                                        required
                                    />
                                    <div className="sm:col-span-2">
                                        <Input 
                                            label="Description" 
                                            value={line.item_description} 
                                            onChange={(e) => updateLine(index, 'item_description', e.target.value)} 
                                            error={(errors as Record<string, string>)[`lines.${index}.item_description`]}
                                            required
                                        />
                                    </div>
                                    <Input 
                                        label="Unit" 
                                        value={line.unit_of_measure} 
                                        onChange={(e) => updateLine(index, 'unit_of_measure', e.target.value)} 
                                        placeholder="UNIT"
                                    />
                                </div>
                                <div className="mt-3 grid gap-3 sm:grid-cols-5">
                                    <Input 
                                        label="Quantity" 
                                        type="number" 
                                        step="0.001" 
                                        value={line.quantity} 
                                        onChange={(e) => updateLine(index, 'quantity', e.target.value)} 
                                        error={(errors as Record<string, string>)[`lines.${index}.quantity`]}
                                        required
                                    />
                                    <Input 
                                        label="Unit Price (TND)" 
                                        type="number" 
                                        step="0.001" 
                                        value={line.unit_price} 
                                        onChange={(e) => updateLine(index, 'unit_price', e.target.value)} 
                                        error={(errors as Record<string, string>)[`lines.${index}.unit_price`]}
                                        required
                                    />
                                    <Input 
                                        label="TVA %" 
                                        type="number" 
                                        step="1" 
                                        value={line.tva_rate} 
                                        onChange={(e) => updateLine(index, 'tva_rate', e.target.value)} 
                                        error={(errors as Record<string, string>)[`lines.${index}.tva_rate`]}
                                        required
                                    />
                                    <div className="sm:col-span-2 flex items-end">
                                        <div className="w-full rounded bg-gradient-to-r from-gray-50 to-gray-100 px-3 py-2">
                                            <div className="flex justify-between text-xs text-gray-500">
                                                <span>Net: {lineTotals[index]?.net.toFixed(3)}</span>
                                                <span>TVA: {lineTotals[index]?.tva.toFixed(3)}</span>
                                            </div>
                                            <p className="text-right font-semibold text-gray-900">{lineTotals[index]?.total.toFixed(3)} TND</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Totals */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="ml-auto max-w-sm">
                        <h2 className="mb-4 text-lg font-semibold text-gray-900">Invoice Summary</h2>
                        <div className="space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">Total excl. tax (HT)</span>
                                <span className="font-medium">{totalHT.toFixed(3)} TND</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">Total TVA</span>
                                <span className="font-medium">{totalTVA.toFixed(3)} TND</span>
                            </div>
                            <hr className="my-2" />
                            <div className="flex justify-between text-lg font-bold">
                                <span>Total incl. tax (TTC)</span>
                                <span className="text-indigo-600">{totalTTC.toFixed(3)} TND</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Notes */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <label className="block text-sm font-medium text-gray-700 mb-2">Notes / Comments</label>
                    <textarea 
                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                        rows={3} 
                        value={data.notes} 
                        onChange={(e) => setData('notes', e.target.value)} 
                        placeholder="Optional notes for this invoice..."
                    />
                </div>

                {/* Actions */}
                <div className="flex items-center justify-between rounded-lg bg-gray-50 p-4">
                    <p className="text-sm text-gray-500">
                        {isEdit ? 'Update the invoice details and save changes.' : 'Invoice will be created as a draft. You can validate, sign, and submit it later.'}
                    </p>
                    <div className="flex gap-3">
                        <Link href="/invoices"><Button variant="secondary" type="button">Cancel</Button></Link>
                        <Button type="submit" loading={processing}>
                            {isEdit ? 'Update Invoice' : 'Create Invoice'}
                        </Button>
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
