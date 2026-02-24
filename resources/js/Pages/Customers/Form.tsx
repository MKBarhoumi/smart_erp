import { Head, useForm, Link } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Customer } from '@/types';

interface CustomerFormData {
    name: string;
    identifier_type: string;
    identifier_value: string;
    matricule_fiscal: string;
    category_type: string;
    person_type: string;
    tax_office: string;
    registre_commerce: string;
    legal_form: string;
    address_description: string;
    street: string;
    city: string;
    postal_code: string;
    country_code: string;
    phone: string;
    fax: string;
    email: string;
    website: string;
    notes: string;
}

const identifierTypes = [
    { value: 'I-01', label: 'Matricule Fiscal (MF)' },
    { value: 'I-02', label: 'CIN Number' },
    { value: 'I-03', label: 'Passport Number' },
    { value: 'I-04', label: 'Residence Number' },
];

const categoryTypes = [
    { value: '', label: '— Sélectionner —' },
    { value: 'A', label: 'A' },
    { value: 'B', label: 'B' },
    { value: 'D', label: 'D' },
    { value: 'N', label: 'N' },
    { value: 'P', label: 'P' },
];

const personTypes = [
    { value: '', label: '— Select —' },
    { value: 'C', label: 'C - Legal Entity' },
    { value: 'M', label: 'M - Natural Person' },
    { value: 'N', label: 'N - Non-Resident' },
    { value: 'P', label: 'P - Main Establishment' },
];

interface Props {
    customer?: Partial<Customer> & { id: string };
    isEdit?: boolean;
}

export default function CustomerForm({ customer, isEdit = false }: Props) {
    const { data, setData, post, put, processing, errors } = useForm<CustomerFormData>({
        name: customer?.name ?? '',
        identifier_type: customer?.identifier_type ?? 'I-01',
        identifier_value: customer?.identifier_value ?? '',
        matricule_fiscal: customer?.matricule_fiscal ?? '',
        category_type: customer?.category_type ?? '',
        person_type: customer?.person_type ?? '',
        tax_office: customer?.tax_office ?? '',
        registre_commerce: customer?.registre_commerce ?? '',
        legal_form: customer?.legal_form ?? '',
        address_description: customer?.address_description ?? '',
        street: customer?.street ?? '',
        city: customer?.city ?? '',
        postal_code: customer?.postal_code ?? '',
        country_code: customer?.country_code ?? 'TN',
        phone: customer?.phone ?? '',
        fax: customer?.fax ?? '',
        email: customer?.email ?? '',
        website: customer?.website ?? '',
        notes: customer?.notes ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit && customer?.id) {
            put(`/customers/${customer.id}`);
        } else {
            post('/customers');
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={isEdit ? 'Edit Customer' : 'New Customer'} />

            <div className="mx-auto max-w-3xl">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">
                        {isEdit ? 'Edit Customer' : 'New Customer'}
                    </h1>
                    <Link href="/customers">
                        <Button variant="ghost">Back</Button>
                    </Link>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow">
                    {/* Identity */}
                    <fieldset className="space-y-4">
                        <legend className="text-lg font-semibold text-gray-900">Identity</legend>
                        <Input label="Name / Company Name" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Select label="Identifier Type" options={identifierTypes} value={data.identifier_type} onChange={(e) => setData('identifier_type', e.target.value)} error={errors.identifier_type} />
                            <Input label="Identifier Value" value={data.identifier_value} onChange={(e) => setData('identifier_value', e.target.value)} error={errors.identifier_value} required />
                        </div>
                    </fieldset>

                    {/* Fiscal */}
                    <fieldset className="space-y-4">
                        <legend className="text-lg font-semibold text-gray-900">Tax Data</legend>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Input label="Matricule Fiscal" value={data.matricule_fiscal} onChange={(e) => setData('matricule_fiscal', e.target.value)} error={errors.matricule_fiscal} helpText="Format: 7 digits + letter + category + person + 000" />
                            <Select label="Category (I-811)" options={categoryTypes} value={data.category_type} onChange={(e) => setData('category_type', e.target.value)} error={errors.category_type} />
                            <Select label="Person Type (I-812)" options={personTypes} value={data.person_type} onChange={(e) => setData('person_type', e.target.value)} error={errors.person_type} />
                            <Input label="Tax Office (I-813)" value={data.tax_office} onChange={(e) => setData('tax_office', e.target.value)} error={errors.tax_office} />
                            <Input label="Trade Register" value={data.registre_commerce} onChange={(e) => setData('registre_commerce', e.target.value)} error={errors.registre_commerce} />
                            <Input label="Legal Form" value={data.legal_form} onChange={(e) => setData('legal_form', e.target.value)} error={errors.legal_form} />
                        </div>
                    </fieldset>

                    {/* Address */}
                    <fieldset className="space-y-4">
                        <legend className="text-lg font-semibold text-gray-900">Address</legend>
                        <Input label="Description" value={data.address_description} onChange={(e) => setData('address_description', e.target.value)} error={errors.address_description} />
                        <Input label="Street" value={data.street} onChange={(e) => setData('street', e.target.value)} error={errors.street} />
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Input label="City" value={data.city} onChange={(e) => setData('city', e.target.value)} error={errors.city} />
                            <Input label="Postal Code" value={data.postal_code} onChange={(e) => setData('postal_code', e.target.value)} error={errors.postal_code} />
                            <Input label="Country Code" value={data.country_code} onChange={(e) => setData('country_code', e.target.value)} error={errors.country_code} />
                        </div>
                    </fieldset>

                    {/* Contact */}
                    <fieldset className="space-y-4">
                        <legend className="text-lg font-semibold text-gray-900">Contact</legend>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Input label="Phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} error={errors.phone} />
                            <Input label="Fax" value={data.fax} onChange={(e) => setData('fax', e.target.value)} error={errors.fax} />
                            <Input label="Email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                            <Input label="Website" value={data.website} onChange={(e) => setData('website', e.target.value)} error={errors.website} />
                        </div>
                    </fieldset>

                    {/* Notes */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" rows={3} value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                    </div>

                    <div className="flex justify-end gap-3">
                        <Link href="/customers">
                            <Button variant="secondary" type="button">Cancel</Button>
                        </Link>
                        <Button type="submit" loading={processing}>
                            {isEdit ? 'Update' : 'Create Customer'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
