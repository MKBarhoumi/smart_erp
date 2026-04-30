import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { InvoiceStatusBadge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Pagination } from '@/Components/ui/Pagination';
import { ImportXmlModal } from '@/Components/ImportXmlModal';
import { formatTND } from '@/utils/format';
import { usePermissions } from '@/hooks/usePermissions';
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
    const { canCreate, canEdit, canImportXML } = usePermissions();
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');
    const [showImportModal, setShowImportModal] = useState(false);

    const applyFilters = () => {
        router.get('/invoices', { search, status, date_from: dateFrom, date_to: dateTo }, { preserveState: true, replace: true });
    };

    const clearFilters = () => {
        setSearch(''); setStatus(''); setDateFrom(''); setDateTo('');
        router.get('/invoices', {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="TEIF Invoices" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900">TEIF Invoices</h1>
                        <p className="mt-1 text-gray-500">TEIF-compliant electronic invoices for Tunisia Tax Network</p>
                    </div>
                    <div className="flex gap-2">
                        {canImportXML && (
                            <Button 
                                variant="secondary" 
                                onClick={() => setShowImportModal(true)}
                                icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>}
                            >
                                Import XML
                            </Button>
                        )}
                        {canCreate('invoices') && (
                            <Link href="/invoices/create">
                                <Button icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>}>
                                    New Invoice
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-5">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                        <div className="lg:col-span-2 relative">
                            <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input type="text" placeholder="Search by ID, sender, receiver..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} className="w-full pl-12 pr-4 py-3 rounded-xl border-0 bg-gray-50/80 ring-1 ring-gray-200 focus:ring-2 focus:ring-user-500 focus:bg-white transition-all text-sm" />
                        </div>
                        <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full py-3 px-4 rounded-xl border-0 bg-gray-50/80 ring-1 ring-gray-200 focus:ring-2 focus:ring-user-500 text-sm">
                            <option value="">All statuses</option>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                        </select>
                        <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="w-full py-3 px-4 rounded-xl border-0 bg-gray-50/80 ring-1 ring-gray-200 focus:ring-2 focus:ring-user-500 text-sm" placeholder="From" />
                        <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="w-full py-3 px-4 rounded-xl border-0 bg-gray-50/80 ring-1 ring-gray-200 focus:ring-2 focus:ring-user-500 text-sm" placeholder="To" />
                        <div className="flex gap-2">
                            <Button onClick={applyFilters} className="flex-1">Filter</Button>
                            <Button variant="ghost" onClick={clearFilters}>Clear</Button>
                        </div>
                    </div>
                </div>

                {/* Table */}
                {invoices.data.length === 0 ? (
                    <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-12 text-center">
                        <div className="mx-auto w-16 h-16 rounded-2xl bg-user-100 flex items-center justify-center mb-4">
                            <svg className="w-8 h-8 text-user-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900">No invoices found</h3>
                        <p className="mt-1 text-sm text-gray-500">Create your first TEIF-compliant invoice.</p>
                        <Link href="/invoices/create" className="mt-4 inline-block"><Button size="sm">Create Invoice</Button></Link>
                    </div>
                ) : (
                    <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-100">
                                <thead>
                                    <tr className="bg-gradient-to-r from-gray-50 to-gray-50/50">
                                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Invoice</th>
                                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Parties</th>
                                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                                        <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                        <th className="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {invoices.data.map((inv, idx) => (
                                        <tr key={inv.id} className={`transition-colors hover:bg-user-50/50 ${idx % 2 === 0 ? '' : 'bg-gray-50/30'}`}>
                                            <td className="px-6 py-4">
                                                <Link href={`/invoices/${inv.id}`} className="font-semibold text-user-600 hover:text-user-700 hover:underline">{inv.document_identifier}</Link>
                                            </td>
                                            <td className="px-6 py-4"><span className="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-xs font-medium text-gray-700">{inv.document_type_code}</span></td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm">
                                                    <p className="font-medium text-gray-900">{inv.sender_name || '-'}</p>
                                                    <p className="text-gray-500 text-xs">→ {inv.receiver_name || inv.receiver_identifier}</p>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{inv.invoice_date || '-'}</td>
                                            <td className="px-6 py-4 text-right font-semibold text-gray-900">{formatTND(inv.total_ttc)}</td>
                                            <td className="px-6 py-4 text-center"><InvoiceStatusBadge status={inv.status} /></td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Link href={`/invoices/${inv.id}`} className="p-2 rounded-lg text-gray-500 hover:text-user-600 hover:bg-user-50 transition-all" title="View"><svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg></Link>
                                                    {canEdit('invoices') && inv.status === 'draft' && <Link href={`/invoices/${inv.id}/edit`} className="p-2 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 transition-all" title="Edit"><svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg></Link>}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                <Pagination links={invoices.links} />
            </div>

            {/* Import XML Modal */}
            <ImportXmlModal show={showImportModal} onClose={() => setShowImportModal(false)} />
        </AuthenticatedLayout>
    );
}
