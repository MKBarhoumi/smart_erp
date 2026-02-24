import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { OldInvoiceStatusBadge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Pagination } from '@/Components/ui/Pagination';
import { Select } from '@/Components/ui/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { OldInvoice, PaginatedData } from '@/types';

interface Props {
    oldinvoices: PaginatedData<OldInvoice & { customer: { id: string; name: string } | null }>;
    filters: { search?: string; status?: string; date_from?: string; date_to?: string };
    statuses: Array<{ value: string; label: string }>;
}

export default function OldInvoicesIndex({ oldinvoices, filters, statuses }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const applyFilters = () => {
        router.get('/oldinvoices', { search, status, date_from: dateFrom, date_to: dateTo }, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="OldInvoices" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">OldInvoices</h1>
                    <Link href="/oldinvoices/create">
                        <Button>New OldInvoice</Button>
                    </Link>
                </div>

                {/* Filters */}
                <div className="grid gap-3 rounded-lg bg-white p-4 shadow sm:grid-cols-5">
                    <Input placeholder="Search..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} />
                    <Select
                        options={[{ value: '', label: 'All statuses' }, ...statuses]}
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    />
                    <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
                    <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                    <Button onClick={applyFilters}>Filter</Button>
                </div>

                {/* OldInvoice table */}
                <div className="overflow-x-auto rounded-lg bg-white shadow">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">OldInvoice #</th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Customer</th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Total incl. tax</th>
                                <th className="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Status</th>
                                <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {oldinvoices.data.length === 0 && (
                                <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-500">No oldinvoices found.</td></tr>
                            )}
                            {oldinvoices.data.map((inv) => (
                                <tr key={inv.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <Link href={`/oldinvoices/${inv.id}`} className="font-medium text-blue-600 hover:underline">
                                            {inv.oldinvoice_number}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-700">{inv.customer?.name ?? '—'}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500">{inv.oldinvoice_date}</td>
                                    <td className="px-4 py-3 text-right font-medium">{Number(inv.total_ttc).toFixed(3)} TND</td>
                                    <td className="px-4 py-3 text-center"><OldInvoiceStatusBadge status={inv.status} /></td>
                                    <td className="px-4 py-3 text-right">
                                        <Link href={`/oldinvoices/${inv.id}`} className="text-sm text-blue-600 hover:underline">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination links={oldinvoices.links} />
            </div>
        </AuthenticatedLayout>
    );
}
