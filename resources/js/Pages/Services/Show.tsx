import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/Button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatTND } from '@/utils/format';
import type { Service, User } from '@/types';

interface Props {
    service: Service;
}

export default function Show({ service }: Props) {
    const { auth } = usePage<{ auth: { user: User } }>().props;
    const canModify = auth.user?.can_modify ?? auth.user?.role !== 'viewer';
    const isAdmin = auth.user?.role === 'admin' || auth.user?.role === 'super_admin';

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this service?')) {
            router.delete(`/services/${service.id}`);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Service: ${service.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-user-500 to-purple-500 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-user-500/25">
                            {service.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{service.name}</h1>
                            <p className="text-gray-500 font-mono">{service.code}</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/services">
                            <Button variant="ghost">Back</Button>
                        </Link>
                        {canModify && (
                            <Link href={`/services/${service.id}/edit`}>
                                <Button variant="secondary">Edit</Button>
                            </Link>
                        )}
                        {isAdmin && (
                            <Button variant="danger" onClick={handleDelete}>Delete</Button>
                        )}
                    </div>
                </div>

                {/* Details */}
                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl bg-white p-6 shadow-soft border border-gray-100">
                        <h2 className="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg className="w-5 h-5 text-user-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            Service Details
                        </h2>
                        <dl className="space-y-3 text-sm">
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <dt className="text-gray-500">Code</dt>
                                <dd className="font-mono font-medium text-gray-900">{service.code}</dd>
                            </div>
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <dt className="text-gray-500">Name</dt>
                                <dd className="font-medium text-gray-900">{service.name}</dd>
                            </div>
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <dt className="text-gray-500">Category</dt>
                                <dd>
                                    <span className="inline-flex items-center px-2.5 py-1 rounded-lg bg-user-100 text-user-700 text-xs font-medium">
                                        {service.category}
                                    </span>
                                </dd>
                            </div>
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <dt className="text-gray-500">Billing Unit</dt>
                                <dd className="font-medium text-gray-900">{service.unit}</dd>
                            </div>
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <dt className="text-gray-500">Status</dt>
                                <dd>
                                    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium ${service.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'}`}>
                                        <span className={`w-1.5 h-1.5 rounded-full ${service.is_active ? 'bg-emerald-500' : 'bg-gray-400'}`} />
                                        {service.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow-soft border border-gray-100">
                        <h2 className="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg className="w-5 h-5 text-user-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Pricing
                        </h2>
                        <dl className="space-y-3 text-sm">
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <dt className="text-gray-500">Unit Price (excl. tax)</dt>
                                <dd className="font-semibold text-gray-900">{formatTND(service.unit_price)}</dd>
                            </div>
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <dt className="text-gray-500">Tax Rate (TVA)</dt>
                                <dd>
                                    <span className="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">
                                        {service.tax_rate}%
                                    </span>
                                </dd>
                            </div>
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <dt className="text-gray-500">Unit Price (incl. tax)</dt>
                                <dd className="font-semibold text-user-600">
                                    {formatTND(String(Number(service.unit_price) * (1 + Number(service.tax_rate) / 100)))}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {service.description && (
                        <div className="lg:col-span-2 rounded-2xl bg-white p-6 shadow-soft border border-gray-100">
                            <h2 className="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <svg className="w-5 h-5 text-user-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                Description
                            </h2>
                            <p className="text-gray-600 whitespace-pre-wrap">{service.description}</p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
