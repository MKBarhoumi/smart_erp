import { Head, useForm, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useRef, useState } from 'react';
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

const SectionCard = ({ title, icon, children }: { title: string; icon: React.ReactNode; children: React.ReactNode }) => (
    <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
        <div className="px-6 py-4 bg-gradient-to-r from-gray-50 to-slate-50 border-b border-gray-100 flex items-center gap-3">
            <div className="p-1.5 rounded-lg bg-user-100 text-user-600">{icon}</div>
            <h2 className="text-lg font-semibold text-gray-900">{title}</h2>
        </div>
        <div className="p-6">{children}</div>
    </div>
);

const InputField = ({ label, error, ...props }: { label: string; error?: string } & React.InputHTMLAttributes<HTMLInputElement>) => (
    <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
        <input {...props} className={`w-full px-4 py-2.5 rounded-xl border ${error ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-200 focus:border-user-500 focus:ring-user-500/20'} focus:ring-2 transition-all text-sm`} />
        {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
);

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

    const submit = (e: FormEvent) => { e.preventDefault(); put('/settings'); };
    const uploadCertificate = () => { const file = certFileRef.current?.files?.[0]; if (!file) return; const passphrase = prompt('Certificate password (.p12):'); if (passphrase === null) return; const formData = new FormData(); formData.append('certificate_file', file); formData.append('certificate_passphrase', passphrase); setCertUploading(true); router.post('/settings/certificate', formData as any, { onFinish: () => setCertUploading(false) }); };
    const uploadLogo = () => { const file = logoFileRef.current?.files?.[0]; if (!file) return; const formData = new FormData(); formData.append('logo', file); setLogoUploading(true); router.post('/settings/logo', formData as any, { onFinish: () => setLogoUploading(false) }); };

    return (
        <AuthenticatedLayout>
            <Head title="Settings" />

            <div className="space-y-6">
                {/* Page Header */}
                <div className="flex items-center gap-3">
                    <div className="p-2 rounded-xl bg-gradient-to-br from-user-600 to-indigo-500 text-white shadow-lg shadow-user-500/25">
                        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900">Company Settings</h1>
                        <p className="text-gray-500">Manage your company profile and preferences</p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <SectionCard title="Tax Identity" icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>}>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <InputField label="Company Name" value={data.company_name} onChange={(e) => setData('company_name', e.target.value)} error={errors.company_name} required />
                            <InputField label="Tax ID" value={data.matricule_fiscal} onChange={(e) => setData('matricule_fiscal', e.target.value)} error={errors.matricule_fiscal} placeholder="1234567A/B/C/000" required />
                            <InputField label="Category Code" value={data.tax_category_code} onChange={(e) => setData('tax_category_code', e.target.value)} error={errors.tax_category_code} />
                            <InputField label="Secondary Establishment" value={data.secondary_establishment} onChange={(e) => setData('secondary_establishment', e.target.value)} error={errors.secondary_establishment} />
                            <InputField label="Tax Office" value={data.tax_office} onChange={(e) => setData('tax_office', e.target.value)} error={errors.tax_office} />
                            <InputField label="Trade Register" value={data.registre_commerce} onChange={(e) => setData('registre_commerce', e.target.value)} error={errors.registre_commerce} />
                            <InputField label="Legal Form" value={data.legal_form} onChange={(e) => setData('legal_form', e.target.value)} error={errors.legal_form} />
                        </div>
                    </SectionCard>

                    <SectionCard title="Address" icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>}>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="sm:col-span-2"><InputField label="Street" value={data.address_street} onChange={(e) => setData('address_street', e.target.value)} error={errors.address_street} /></div>
                            <InputField label="City" value={data.address_city} onChange={(e) => setData('address_city', e.target.value)} error={errors.address_city} />
                            <InputField label="Postal Code" value={data.address_postal_code} onChange={(e) => setData('address_postal_code', e.target.value)} error={errors.address_postal_code} />
                            <InputField label="Country" value={data.country_code} onChange={(e) => setData('country_code', e.target.value)} error={errors.country_code} />
                        </div>
                    </SectionCard>

                    <SectionCard title="Contact" icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>}>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <InputField label="Phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} error={errors.phone} />
                            <InputField label="Fax" value={data.fax} onChange={(e) => setData('fax', e.target.value)} error={errors.fax} />
                            <InputField label="Email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                            <InputField label="Website" value={data.website} onChange={(e) => setData('website', e.target.value)} error={errors.website} />
                        </div>
                    </SectionCard>

                    <SectionCard title="Bank Details" icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg>}>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <InputField label="Bank Name" value={data.bank_name} onChange={(e) => setData('bank_name', e.target.value)} error={errors.bank_name} />
                            <InputField label="RIB" value={data.bank_rib} onChange={(e) => setData('bank_rib', e.target.value)} error={errors.bank_rib} />
                            <InputField label="CCP" value={data.postal_account} onChange={(e) => setData('postal_account', e.target.value)} error={errors.postal_account} />
                        </div>
                    </SectionCard>

                    <SectionCard title="Invoicing" icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>}>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <InputField label="Invoice Prefix" value={data.oldinvoice_prefix} onChange={(e) => setData('oldinvoice_prefix', e.target.value)} error={errors.oldinvoice_prefix} />
                            <InputField label="Number Format" value={data.oldinvoice_number_format} onChange={(e) => setData('oldinvoice_number_format', e.target.value)} error={errors.oldinvoice_number_format} />
                            <InputField label="Next Counter" type="number" value={String(data.next_oldinvoice_counter)} onChange={(e) => setData('next_oldinvoice_counter', parseInt(e.target.value) || 1)} error={errors.next_oldinvoice_counter} />
                        </div>
                    </SectionCard>

                    <div className="flex justify-end">
                        <button type="submit" disabled={processing} className="px-6 py-3 bg-gradient-to-r from-user-600 to-user-500 text-white font-semibold rounded-xl hover:from-user-700 hover:to-user-600 transition-all shadow-lg shadow-user-500/25 disabled:opacity-50">
                            {processing ? 'Saving...' : 'Save Changes'}
                        </button>
                    </div>
                </form>

                {/* Certificate */}
                <SectionCard title="Digital Certificate" icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>}>
                    {certificateInfo ? (
                        <div className="space-y-3 mb-6">
                            <div className="bg-emerald-50 rounded-xl p-4 border border-emerald-200/50">
                                <p className="text-sm font-medium text-emerald-800 flex items-center gap-2"><svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Certificate Configured</p>
                            </div>
                            <div className="grid sm:grid-cols-2 gap-3 text-sm">
                                <div><span className="text-gray-500">Subject:</span> <span className="text-gray-900">{certificateInfo.subject}</span></div>
                                <div><span className="text-gray-500">Issuer:</span> <span className="text-gray-900">{certificateInfo.issuer}</span></div>
                                <div className="flex items-center gap-2"><span className="text-gray-500">Validity:</span> <span className="text-gray-900">{certificateInfo.valid_from} - {certificateInfo.valid_to}</span>{certificateInfo.is_expiring_soon && <span className="inline-flex px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">Expiring Soon</span>}</div>
                                <div><span className="text-gray-500">Serial:</span> <span className="font-mono text-xs text-gray-700">{certificateInfo.serial_number}</span></div>
                            </div>
                        </div>
                    ) : (
                        <div className="bg-amber-50 rounded-xl p-4 border border-amber-200/50 mb-6">
                            <p className="text-sm font-medium text-amber-800 flex items-center gap-2"><svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>No certificate configured</p>
                            <p className="text-xs text-amber-700 mt-1">Upload a .p12 file for XAdES-BES signing</p>
                        </div>
                    )}
                    <div className="flex items-center gap-3">
                        <input ref={certFileRef} type="file" accept=".p12,.pfx" className="text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-user-100 file:text-user-700 hover:file:bg-user-200" />
                        <button type="button" disabled={certUploading} onClick={uploadCertificate} className="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50">{certUploading ? 'Uploading...' : 'Upload Certificate'}</button>
                    </div>
                </SectionCard>

                {/* Logo */}
                <SectionCard title="Company Logo" icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>}>
                    {settings.logo_path && (<div className="mb-4 p-4 bg-gray-50 rounded-xl inline-block"><img src={`/storage/${settings.logo_path}`} alt="Logo" className="h-16" /></div>)}
                    <div className="flex items-center gap-3">
                        <input ref={logoFileRef} type="file" accept="image/*" className="text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-user-100 file:text-user-700 hover:file:bg-user-200" />
                        <button type="button" disabled={logoUploading} onClick={uploadLogo} className="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50">{logoUploading ? 'Uploading...' : 'Upload Logo'}</button>
                    </div>
                </SectionCard>
            </div>
        </AuthenticatedLayout>
    );
}
