import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { InvoiceStatusBadge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Pagination } from '@/Components/ui/Pagination';
import { Select } from '@/Components/ui/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Invoice, PaginatedData } from '@/types';

interface InvoiceListItem {
    id: string;
    document_identifier: string;
    document_type_code: string;
    document_type_name: string;
    sender_identifier: string;
    receiver_identifier: string;
    sender_name: string | null;
    receiver_name: string | null;
    invoice_date: string | null;
    total_ht: string;
    total_tva: string;
    total_ttc: string;
    status: Invoice['status'];
    created_at: string;
}

interface Props {
    invoices: PaginatedData<InvoiceListItem>;
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

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setDateFrom('');
        setDateTo('');
        router.get('/invoices', {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Invoices" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Invoices</h1>
                        <p className="text-sm text-gray-500">TEIF-compliant electronic invoices for Tunisia Tax Network</p>
                    </div>
                    <Link href="/invoices/create">
                        <Button>New Invoice</Button>
                    </Link>
                </div>

                {/* Filters */}
                <div className="rounded-lg bg-white p-4 shadow">
                    <div className="grid gap-3 sm:grid-cols-6">
                        <div className="sm:col-span-2">
                            <Input 
                                placeholder="Search by ID, sender, receiver..." 
                                value={search} 
                                onChange={(e) => setSearch(e.target.value)} 
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()} 
                            />
                        </div>
                        <Select
                            options={[{ value: '', label: 'All statuses' }, ...statuses]}
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                        />
                        <Input type="date" placeholder="From" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
                        <Input type="date" placeholder="To" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                        <div className="flex gap-2">
                            <Button onClick={applyFilters}>Filter</Button>
                            <Button variant="ghost" onClick={clearFilters}>Clear</Button>
                        </div>
                    </div>
                </div>

                {/* Invoice table */}
                <div className="overflow-x-auto rounded-lg bg-white shadow">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Invoice #</th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Type</th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Sender</th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Receiver</th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Total TTC</th>
                                <th className="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Status</th>
                                <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {invoices.data.length === 0 && (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12 text-center">
                                        <div className="flex flex-col items-center gap-2">
                                            <svg className="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            <p className="text-gray-500">No invoices found.</p>
                                            <Link href="/invoices/create">
                                                <Button size="sm">Create your first invoice</Button>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            )}
                            {invoices.data.map((inv) => (
                                <tr key={inv.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <Link href={`/invoices/${inv.id}`} className="font-medium text-blue-600 hover:underline">
                                            {inv.document_identifier}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-500">
                                        <span className="rounded bg-gray-100 px-2 py-0.5 text-xs">{inv.document_type_code}</span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="text-sm font-medium text-gray-900">{inv.sender_name || '—'}</div>
                                        <div className="text-xs text-gray-500 font-mono">{inv.sender_identifier}</div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="text-sm font-medium text-gray-900">{inv.receiver_name || '—'}</div>
                                        <div className="text-xs text-gray-500 font-mono">{inv.receiver_identifier}</div>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-500">{inv.invoice_date || '—'}</td>
                                    <td className="px-4 py-3 text-right font-medium">{Number(inv.total_ttc).toFixed(3)} TND</td>
                                    <td className="px-4 py-3 text-center"><InvoiceStatusBadge status={inv.status} /></td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Link href={`/invoices/${inv.id}`} className="text-sm text-blue-600 hover:underline">
                                                View
                                            </Link>
                                            {inv.status === 'draft' && (
                                                <Link href={`/invoices/${inv.id}/edit`} className="text-sm text-gray-600 hover:underline">
                                                    Edit
                                                </Link>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination links={invoices.links} />

                {/* Summary stats */}
                {invoices.total > 0 && (
                    <div className="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        Showing {invoices.from} to {invoices.to} of {invoices.total} invoices
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
