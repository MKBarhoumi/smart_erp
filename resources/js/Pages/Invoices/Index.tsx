import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { InvoiceStatusBadge } from '@/Components/ui/Badge';
import { Pagination } from '@/Components/ui/Pagination';
import type { Invoice, PaginatedData } from '@/types';
import { useState } from 'react';

interface Props {
    invoices: PaginatedData<Invoice & { customer: { id: string; name: string } | null }>;
    filters: { search?: string; status?: string; date_from?: string; date_to?: string };
    statuses: Array<{ value: string; label: string }>;
}

export default function InvoicesIndex({ invoices, filters, statuses }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const applyFilters = () => {
        router.get('/invoices', { search, status, date_from: dateFrom, date_to: dateTo }, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Invoices" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Invoices</h1>
                    <Link href="/invoices/create">
                        <Button>New Invoice</Button>
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

                {/* Invoice table */}
                <div className="overflow-x-auto rounded-lg bg-white shadow">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Invoice #</th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Customer</th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Total incl. tax</th>
                                <th className="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Status</th>
                                <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {invoices.data.length === 0 && (
                                <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-500">No invoices found.</td></tr>
                            )}
                            {invoices.data.map((inv) => (
                                <tr key={inv.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <Link href={`/invoices/${inv.id}`} className="font-medium text-blue-600 hover:underline">
                                            {inv.invoice_number}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-700">{inv.customer?.name ?? '—'}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500">{inv.invoice_date}</td>
                                    <td className="px-4 py-3 text-right font-medium">{Number(inv.total_ttc).toFixed(3)} TND</td>
                                    <td className="px-4 py-3 text-center"><InvoiceStatusBadge status={inv.status} /></td>
                                    <td className="px-4 py-3 text-right">
                                        <Link href={`/invoices/${inv.id}`} className="text-sm text-blue-600 hover:underline">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination links={invoices.links} />
            </div>
        </AuthenticatedLayout>
    );
}
