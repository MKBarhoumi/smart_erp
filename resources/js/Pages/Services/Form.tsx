import { Head, useForm, Link } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Service } from '@/types';

interface ServiceFormData {
    code: string;
    name: string;
    description: string;
    category: string;
    unit: string;
    unit_price: string;
    discount_rate: string;
    tax_rate: string;
    is_active: boolean;
}

const taxRates = [
    { value: '0', label: '0%' },
    { value: '7', label: '7%' },
    { value: '13', label: '13%' },
    { value: '19', label: '19%' },
];

interface Props {
    service?: Partial<Service> & { id: string };
    categories: string[];
    billingUnits: string[];
    isEdit?: boolean;
}

export default function ServiceForm({ service, categories, billingUnits, isEdit = false }: Props) {
    const { data, setData, post, put, processing, errors } = useForm<ServiceFormData>({
        code: service?.code ?? '',
        name: service?.name ?? '',
        description: service?.description ?? '',
        category: service?.category ?? 'Other',
        unit: service?.unit ?? 'Unit',
        unit_price: service?.unit_price ?? '',
        discount_rate: (service as any)?.discount_rate ?? '0',
        tax_rate: service?.tax_rate ?? '19',
        is_active: service?.is_active ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit && service?.id) {
            put(`/services/${service.id}`);
        } else {
            post('/services');
        }
    };

    const categoryOptions = categories.map(cat => ({ value: cat, label: cat }));
    const unitOptions = billingUnits.map(unit => ({ value: unit, label: unit }));

    return (
        <AuthenticatedLayout>
            <Head title={isEdit ? 'Edit Service' : 'New Service'} />

            <div className="mx-auto max-w-2xl">
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-xl bg-gradient-to-br from-user-600 to-purple-500 text-white shadow-lg shadow-user-500/25">
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                            </svg>
                        </div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            {isEdit ? 'Edit Service' : 'New Service'}
                        </h1>
                    </div>
                    <Link href="/services"><Button variant="ghost">Back</Button></Link>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-2xl bg-white p-6 shadow-soft border border-gray-100">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input 
                            label="Service Code" 
                            value={data.code} 
                            onChange={(e) => setData('code', e.target.value)} 
                            error={errors.code} 
                            placeholder="e.g., SRV-001"
                            required 
                        />
                        <Input 
                            label="Service Name" 
                            value={data.name} 
                            onChange={(e) => setData('name', e.target.value)} 
                            error={errors.name} 
                            placeholder="e.g., IT Support"
                            required 
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea 
                            className="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm" 
                            rows={3} 
                            value={data.description} 
                            onChange={(e) => setData('description', e.target.value)} 
                            placeholder="Describe the service..."
                        />
                        {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Select 
                            label="Category" 
                            options={categoryOptions} 
                            value={data.category} 
                            onChange={(e) => setData('category', e.target.value)} 
                            error={errors.category}
                        />
                        <Select 
                            label="Billing Unit" 
                            options={unitOptions} 
                            value={data.unit} 
                            onChange={(e) => setData('unit', e.target.value)} 
                            error={errors.unit}
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <Input 
                            label="Unit Price (TND)" 
                            type="number" 
                            step="0.001" 
                            value={data.unit_price} 
                            onChange={(e) => setData('unit_price', e.target.value)} 
                            error={errors.unit_price} 
                            placeholder="0.000"
                            required 
                        />
                        <Input 
                            label="Discount %" 
                            type="number" 
                            step="0.01" 
                            value={data.discount_rate} 
                            onChange={(e) => setData('discount_rate', e.target.value)} 
                            error={errors.discount_rate} 
                            placeholder="0.00"
                        />
                        <Select 
                            label="Tax Rate (TVA)" 
                            options={taxRates} 
                            value={data.tax_rate} 
                            onChange={(e) => setData('tax_rate', e.target.value)} 
                            error={errors.tax_rate} 
                        />
                    </div>

                    <div className="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                        <label className="relative inline-flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                checked={data.is_active} 
                                onChange={(e) => setData('is_active', e.target.checked)} 
                                className="sr-only peer"
                            />
                            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-user-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-user-600"></div>
                            <span className="ml-3 text-sm font-medium text-gray-700">Active</span>
                        </label>
                        <span className="text-xs text-gray-500">Inactive services will not appear in invoice line selections</span>
                    </div>

                    <div className="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link href="/services">
                            <Button variant="secondary" type="button">Cancel</Button>
                        </Link>
                        <Button type="submit" loading={processing}>
                            {isEdit ? 'Update Service' : 'Create Service'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
