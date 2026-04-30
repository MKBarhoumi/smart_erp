import { Head } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Input } from '@/Components/ui/Input';
import { Button } from '@/Components/ui/Button';
import { Toast } from '@/Components/ui/Toast';

type CompanySettingsForm = {
    company_name: string;
    matricule_fiscal: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    country: string;
    bank_name: string;
    iban: string;
    default_timbre_fiscal: string;
};

type ApiResponse = {
    data: null | (CompanySettingsForm & { id: string });
    message?: string;
    errors?: Record<string, string[]>;
};

type FieldErrors = Partial<Record<keyof CompanySettingsForm, string>>;

const emptyForm: CompanySettingsForm = {
    company_name: '',
    matricule_fiscal: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: '',
    bank_name: '',
    iban: '',
    default_timbre_fiscal: '1',
};

const ShieldIcon = () => (
    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 3l7 3v6c0 4.97-3.13 8.83-7 9-3.87-.17-7-4.03-7-9V6l7-3z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="m9.5 12.5 1.75 1.75L15 10.5" />
    </svg>
);

const csrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

const mapApiToForm = (data: ApiResponse['data']): CompanySettingsForm => {
    if (!data) {
        return emptyForm;
    }

    return {
        company_name: data.company_name ?? '',
        matricule_fiscal: data.matricule_fiscal ?? '',
        email: data.email ?? '',
        phone: data.phone ?? '',
        address: data.address ?? '',
        city: data.city ?? '',
        country: data.country ?? '',
        bank_name: data.bank_name ?? '',
        iban: data.iban ?? '',
        default_timbre_fiscal: data.default_timbre_fiscal !== null && data.default_timbre_fiscal !== undefined
            ? String(data.default_timbre_fiscal)
            : '1',
    };
};

export default function CompanySettings() {
    const [form, setForm] = useState<CompanySettingsForm>(emptyForm);
    const [errors, setErrors] = useState<FieldErrors>({});
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [toasts, setToasts] = useState<Array<{ id: number; type: 'success' | 'error'; message: string }>>([]);

    const pushToast = (type: 'success' | 'error', message: string) => {
        const id = Date.now() + Math.random();
        setToasts((current) => [...current, { id, type, message }]);
        window.setTimeout(() => {
            setToasts((current) => current.filter((toast) => toast.id !== id));
        }, 5000);
    };

    useEffect(() => {
        const controller = new AbortController();

        const loadSettings = async () => {
            try {
                const response = await fetch('/api/company-settings', {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error('Unable to load company settings.');
                }

                const payload = (await response.json()) as ApiResponse;
                setForm(mapApiToForm(payload.data));
                setErrors({});
            } catch (error) {
                if ((error as DOMException)?.name === 'AbortError') {
                    return;
                }

                pushToast('error', error instanceof Error ? error.message : 'Unable to load company settings.');
            } finally {
                setIsLoading(false);
            }
        };

        void loadSettings();

        return () => controller.abort();
    }, []);

    const updateField = <K extends keyof CompanySettingsForm>(field: K, value: CompanySettingsForm[K]) => {
        setForm((current) => ({ ...current, [field]: value }));
        setErrors((current) => ({ ...current, [field]: undefined }));
    };

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setIsSaving(true);
        setErrors({});

        try {
            const response = await fetch('/api/company-settings', {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(form),
            });

            const payload = (await response.json()) as ApiResponse;

            if (!response.ok) {
                if (response.status === 422 && payload.errors) {
                    const nextErrors: FieldErrors = {};

                    Object.entries(payload.errors).forEach(([field, messages]) => {
                        if (field in emptyForm) {
                            nextErrors[field as keyof CompanySettingsForm] = messages[0];
                        }
                    });

                    setErrors(nextErrors);

                    const firstMessage = Object.values(nextErrors).find(Boolean) ?? 'Please correct the highlighted fields.';
                    pushToast('error', firstMessage);
                    return;
                }

                throw new Error(payload.message ?? 'Unable to save company settings.');
            }

            setForm(mapApiToForm(payload.data));
            pushToast('success', 'Company settings saved successfully.');
        } catch (error) {
            pushToast('error', error instanceof Error ? error.message : 'Unable to save company settings.');
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Company Settings" />

            <div className="space-y-6">
                <div className="flex flex-col gap-3">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-blue-500/25">
                            <ShieldIcon />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-gray-900">Company Settings</h1>
                            <p className="mt-1 text-sm text-gray-600">Company identity, banking, and e-invoicing certificate.</p>
                        </div>
                    </div>

                    <div className="inline-flex w-fit items-center gap-2 rounded-full bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm">
                        <ShieldIcon />
                        Certificate: active
                    </div>
                </div>

                {isLoading ? (
                    <div className="rounded-lg bg-white p-6 shadow animate-pulse">
                        <div className="h-5 w-40 rounded bg-gray-200" />
                        <div className="mt-2 h-4 w-72 rounded bg-gray-100" />
                        <div className="mt-6 grid gap-4 sm:grid-cols-2">
                            <div className="h-10 rounded bg-gray-100" />
                            <div className="h-10 rounded bg-gray-100" />
                            <div className="h-10 rounded bg-gray-100" />
                            <div className="h-10 rounded bg-gray-100" />
                            <div className="sm:col-span-2 h-10 rounded bg-gray-100" />
                            <div className="h-10 rounded bg-gray-100" />
                            <div className="h-10 rounded bg-gray-100" />
                            <div className="h-10 rounded bg-gray-100" />
                            <div className="max-w-xs h-10 rounded bg-gray-100" />
                        </div>
                    </div>
                ) : (
                    <form onSubmit={submit} className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-6">
                            <h2 className="text-lg font-semibold text-gray-900">Company identity</h2>
                            <p className="mt-1 text-sm text-gray-600">Used on invoice headers and TTN submissions.</p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Input
                                label="Company name"
                                value={form.company_name}
                                onChange={(event) => updateField('company_name', event.target.value)}
                                error={errors.company_name}
                                required
                            />
                            <Input
                                label="Matricule fiscal"
                                value={form.matricule_fiscal}
                                onChange={(event) => updateField('matricule_fiscal', event.target.value)}
                                error={errors.matricule_fiscal}
                                required
                            />

                            <Input
                                label="Email"
                                type="email"
                                value={form.email}
                                onChange={(event) => updateField('email', event.target.value)}
                                error={errors.email}
                                required
                            />
                            <Input
                                label="Phone"
                                type="tel"
                                value={form.phone}
                                onChange={(event) => updateField('phone', event.target.value)}
                                error={errors.phone}
                                required
                            />

                            <div className="sm:col-span-2">
                                <Input
                                    label="Address"
                                    value={form.address}
                                    onChange={(event) => updateField('address', event.target.value)}
                                    error={errors.address}
                                    required
                                />
                            </div>

                            <Input
                                label="City"
                                value={form.city}
                                onChange={(event) => updateField('city', event.target.value)}
                                error={errors.city}
                                required
                            />
                            <Input
                                label="Country"
                                value={form.country}
                                onChange={(event) => updateField('country', event.target.value.toUpperCase())}
                                error={errors.country}
                                placeholder="TN"
                                maxLength={3}
                                required
                            />

                            <Input
                                label="Bank name"
                                value={form.bank_name}
                                onChange={(event) => updateField('bank_name', event.target.value)}
                                error={errors.bank_name}
                                required
                            />
                            <Input
                                label="IBAN"
                                value={form.iban}
                                onChange={(event) => updateField('iban', event.target.value.toUpperCase())}
                                error={errors.iban}
                                placeholder="TN59 1000 6035 1835 9874 1234"
                                required
                            />

                            <div className="max-w-xs">
                                <Input
                                    label="Default timbre fiscal (TND)"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    value={form.default_timbre_fiscal}
                                    onChange={(event) => updateField('default_timbre_fiscal', event.target.value)}
                                    error={errors.default_timbre_fiscal}
                                    required
                                />
                            </div>
                        </div>

                        <div className="mt-6 flex items-center justify-start">
                            <Button
                                type="submit"
                                loading={isSaving}
                                className="bg-gradient-to-r from-blue-600 to-blue-500 text-white hover:from-blue-700 hover:to-blue-600 focus:ring-blue-500 shadow-lg shadow-blue-500/25"
                            >
                                Save settings
                            </Button>
                        </div>
                    </form>
                )}
            </div>

            <div className="fixed right-4 top-20 z-50 flex w-96 flex-col gap-2">
                {toasts.map((toast) => (
                    <Toast
                        key={toast.id}
                        type={toast.type}
                        message={toast.message}
                        onClose={() => setToasts((current) => current.filter((item) => item.id !== toast.id))}
                    />
                ))}
            </div>
        </AuthenticatedLayout>
    );
}