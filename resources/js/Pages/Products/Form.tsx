import { FormEvent } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Button } from '@/Components/ui/Button';

interface ProductFormData {
    code: string;
    name: string;
    description: string;
    item_lang: string;
    unit_price: string;
    unit_of_measure: string;
    tva_rate: string;
    is_subject_to_timbre: boolean;
    track_inventory: boolean;
    current_stock: string;
    min_stock_alert: string;
}

const tvaRates = [
    { value: '0', label: '0%' },
    { value: '7', label: '7%' },
    { value: '13', label: '13%' },
    { value: '19', label: '19%' },
];

const uomOptions = [
    { value: 'U', label: 'Unit' },
    { value: 'KG', label: 'Kilogram' },
    { value: 'L', label: 'Liter' },
    { value: 'M', label: 'Meter' },
    { value: 'M2', label: 'Square Meter' },
    { value: 'H', label: 'Hour' },
    { value: 'J', label: 'Day' },
];

interface Props {
    product?: ProductFormData & { id: string };
    isEdit?: boolean;
}

export default function ProductForm({ product, isEdit = false }: Props) {
    const { data, setData, post, put, processing, errors } = useForm<ProductFormData>({
        code: product?.code ?? '',
        name: product?.name ?? '',
        description: product?.description ?? '',
        item_lang: product?.item_lang ?? 'fr',
        unit_price: product?.unit_price ?? '',
        unit_of_measure: product?.unit_of_measure ?? 'U',
        tva_rate: product?.tva_rate ?? '19',
        is_subject_to_timbre: product?.is_subject_to_timbre ?? false,
        track_inventory: product?.track_inventory ?? false,
        current_stock: product?.current_stock ?? '0',
        min_stock_alert: product?.min_stock_alert ?? '0',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit && product?.id) {
            put(`/products/${product.id}`);
        } else {
            post('/products');
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={isEdit ? 'Edit Product' : 'New Product'} />

            <div className="mx-auto max-w-2xl">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">
                        {isEdit ? 'Edit Product' : 'New Product'}
                    </h1>
                    <Link href="/products"><Button variant="ghost">Back</Button></Link>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="Product Code" value={data.code} onChange={(e) => setData('code', e.target.value)} error={errors.code} required />
                        <Input label="Name" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Description</label>
                        <textarea className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <Input label="Unit Price excl. tax (TND)" type="number" step="0.001" value={data.unit_price} onChange={(e) => setData('unit_price', e.target.value)} error={errors.unit_price} required />
                        <Select label="TVA %" options={tvaRates} value={data.tva_rate} onChange={(e) => setData('tva_rate', e.target.value)} error={errors.tva_rate} />
                        <Select label="Unit of Measure" options={uomOptions} value={data.unit_of_measure} onChange={(e) => setData('unit_of_measure', e.target.value)} />
                    </div>

                    <div className="flex items-center gap-6">
                        <label className="flex items-center gap-2">
                            <input type="checkbox" className="rounded border-gray-300 text-blue-600" checked={data.is_subject_to_timbre} onChange={(e) => setData('is_subject_to_timbre', e.target.checked)} />
                            <span className="text-sm text-gray-700">Subject to stamp duty</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input type="checkbox" className="rounded border-gray-300 text-blue-600" checked={data.track_inventory} onChange={(e) => setData('track_inventory', e.target.checked)} />
                            <span className="text-sm text-gray-700">Track inventory</span>
                        </label>
                    </div>

                    {data.track_inventory && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Input label="Current Stock" type="number" step="0.001" value={data.current_stock} onChange={(e) => setData('current_stock', e.target.value)} />
                            <Input label="Alert Threshold" type="number" step="0.001" value={data.min_stock_alert} onChange={(e) => setData('min_stock_alert', e.target.value)} />
                        </div>
                    )}

                    <div className="flex justify-end gap-3">
                        <Link href="/products"><Button variant="secondary" type="button">Cancel</Button></Link>
                        <Button type="submit" loading={processing}>
                            {isEdit ? 'Update' : 'Create Product'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
