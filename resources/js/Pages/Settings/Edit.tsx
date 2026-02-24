import { Head, useForm, router } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useRef, useState } from 'react';
import { Badge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { CompanySettings, PageProps } from '@/types';

interface Props extends PageProps {
    settings: CompanySettings;
    certificateInfo?: {
        subject: string;
        issuer: string;
        valid_from: string;
        valid_to: string;
        is_expiring_soon: boolean;
        serial_number: string;
    } | null;
}

export default function Edit({ settings, certificateInfo }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        company_name: settings.company_name ?? '',
        matricule_fiscal: settings.matricule_fiscal ?? '',
        tax_category_code: settings.tax_category_code ?? '',
        secondary_establishment: settings.secondary_establishment ?? '000',
        tax_office: settings.tax_office ?? '',
        registre_commerce: settings.registre_commerce ?? '',
        legal_form: settings.legal_form ?? '',
        address_street: settings.address_street ?? '',
        address_city: settings.address_city ?? '',
        address_postal_code: settings.address_postal_code ?? '',
        country_code: settings.country_code ?? 'TN',
        phone: settings.phone ?? '',
        fax: settings.fax ?? '',
        email: settings.email ?? '',
        website: settings.website ?? '',
        bank_name: settings.bank_name ?? '',
        bank_rib: settings.bank_rib ?? '',
        postal_account: settings.postal_account ?? '',
        oldinvoice_prefix: settings.oldinvoice_prefix ?? 'FAC',
        oldinvoice_number_format: settings.oldinvoice_number_format ?? '{PREFIX}-{YYYY}-{SEQ}',
        next_oldinvoice_counter: settings.next_oldinvoice_counter ?? 1,
    });

    const certFileRef = useRef<HTMLInputElement>(null);
    const logoFileRef = useRef<HTMLInputElement>(null);
    const [certUploading, setCertUploading] = useState(false);
    const [logoUploading, setLogoUploading] = useState(false);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put('/settings');
    };

    const uploadCertificate = () => {
        const file = certFileRef.current?.files?.[0];
        if (!file) return;
        const passphrase = prompt('Certificate password (.p12):');
        if (passphrase === null) return;

        const formData = new FormData();
        formData.append('certificate_file', file);
        formData.append('certificate_passphrase', passphrase);

        setCertUploading(true);
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        router.post('/settings/certificate', formData as any, {
            onFinish: () => setCertUploading(false),
        });
    };

    const uploadLogo = () => {
        const file = logoFileRef.current?.files?.[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('logo', file);

        setLogoUploading(true);
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        router.post('/settings/logo', formData as any, {
            onFinish: () => setLogoUploading(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Settings" />

            <form onSubmit={submit} className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Company Settings</h1>

                {/* Company Identity */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Tax Identity</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Input label="Company Name" value={data.company_name} onChange={(e) => setData('company_name', e.target.value)} error={errors.company_name} required />
                        <Input label="Tax ID" value={data.matricule_fiscal} onChange={(e) => setData('matricule_fiscal', e.target.value)} error={errors.matricule_fiscal} placeholder="1234567A/B/C/000" required />
                        <Input label="Category Code" value={data.tax_category_code} onChange={(e) => setData('tax_category_code', e.target.value)} error={errors.tax_category_code} />
                        <Input label="Secondary Establishment" value={data.secondary_establishment} onChange={(e) => setData('secondary_establishment', e.target.value)} error={errors.secondary_establishment} />
                        <Input label="Tax Office" value={data.tax_office} onChange={(e) => setData('tax_office', e.target.value)} error={errors.tax_office} />
                        <Input label="Trade Register" value={data.registre_commerce} onChange={(e) => setData('registre_commerce', e.target.value)} error={errors.registre_commerce} />
                        <Input label="Legal Form" value={data.legal_form} onChange={(e) => setData('legal_form', e.target.value)} error={errors.legal_form} />
                    </div>
                </div>

                {/* Address */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Address</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="sm:col-span-2"><Input label="Street" value={data.address_street} onChange={(e) => setData('address_street', e.target.value)} error={errors.address_street} /></div>
                        <Input label="City" value={data.address_city} onChange={(e) => setData('address_city', e.target.value)} error={errors.address_city} />
                        <Input label="Postal Code" value={data.address_postal_code} onChange={(e) => setData('address_postal_code', e.target.value)} error={errors.address_postal_code} />
                        <Input label="Country" value={data.country_code} onChange={(e) => setData('country_code', e.target.value)} error={errors.country_code} />
                    </div>
                </div>

                {/* Contact */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Contact</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Input label="Phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} error={errors.phone} />
                        <Input label="Fax" value={data.fax} onChange={(e) => setData('fax', e.target.value)} error={errors.fax} />
                        <Input label="Email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                        <Input label="Website" value={data.website} onChange={(e) => setData('website', e.target.value)} error={errors.website} />
                    </div>
                </div>

                {/* Banking */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Bank Details</h2>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Input label="Bank" value={data.bank_name} onChange={(e) => setData('bank_name', e.target.value)} error={errors.bank_name} />
                        <Input label="RIB" value={data.bank_rib} onChange={(e) => setData('bank_rib', e.target.value)} error={errors.bank_rib} />
                        <Input label="CCP" value={data.postal_account} onChange={(e) => setData('postal_account', e.target.value)} error={errors.postal_account} />
                    </div>
                </div>

                {/* Invoicing */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Invoicing</h2>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Input label="OldInvoice Prefix" value={data.oldinvoice_prefix} onChange={(e) => setData('oldinvoice_prefix', e.target.value)} error={errors.oldinvoice_prefix} />
                        <Input label="Number Format" value={data.oldinvoice_number_format} onChange={(e) => setData('oldinvoice_number_format', e.target.value)} error={errors.oldinvoice_number_format} />
                        <Input label="Next Counter" type="number" value={String(data.next_oldinvoice_counter)} onChange={(e) => setData('next_oldinvoice_counter', parseInt(e.target.value) || 1)} error={errors.next_oldinvoice_counter} />
                    </div>
                </div>

                <div className="flex justify-end">
                    <Button type="submit" loading={processing}>Save</Button>
                </div>
            </form>

            {/* Certificate Upload */}
            <div className="mt-6 rounded-lg bg-white p-6 shadow">
                <h2 className="mb-4 text-lg font-semibold">Digital Certificate</h2>
                {certificateInfo ? (
                    <div className="mb-4 space-y-2 text-sm">
                        <div className="flex items-center gap-2">
                            <span className="text-gray-500">Subject:</span>
                            <span>{certificateInfo.subject}</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-gray-500">Issuer:</span>
                            <span>{certificateInfo.issuer}</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-gray-500">Validity:</span>
                            <span>{certificateInfo.valid_from} — {certificateInfo.valid_to}</span>
                            {certificateInfo.is_expiring_soon && <Badge variant="warning">Expiring Soon</Badge>}
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-gray-500">Serial No.:</span>
                            <span className="font-mono text-xs">{certificateInfo.serial_number}</span>
                        </div>
                    </div>
                ) : (
                    <p className="mb-4 text-sm text-yellow-600">No certificate configured. Please upload a .p12 file for XAdES-BES signing.</p>
                )}
                <div className="flex items-center gap-3">
                    <input ref={certFileRef} type="file" accept=".p12,.pfx" className="text-sm" />
                    <Button type="button" size="sm" variant="secondary" loading={certUploading} onClick={uploadCertificate}>
                        Upload Certificate
                    </Button>
                </div>
            </div>

            {/* Logo Upload */}
            <div className="mt-6 rounded-lg bg-white p-6 shadow">
                <h2 className="mb-4 text-lg font-semibold">Logo</h2>
                {settings.logo_path && (
                    <img src={`/storage/${settings.logo_path}`} alt="Logo" className="mb-4 h-16" />
                )}
                <div className="flex items-center gap-3">
                    <input ref={logoFileRef} type="file" accept="image/*" className="text-sm" />
                    <Button type="button" size="sm" variant="secondary" loading={logoUploading} onClick={uploadLogo}>
                        Upload Logo
                    </Button>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
