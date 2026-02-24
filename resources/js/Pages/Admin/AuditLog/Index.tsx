import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Input } from '@/Components/ui/Input';
import { Pagination } from '@/Components/ui/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { PaginatedData, PageProps } from '@/types';

interface AuditLogEntry {
    id: string;
    user_id: string;
    user?: { name: string; email: string };
    event: string;
    auditable_type: string;
    auditable_id: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string;
    user_agent: string;
    created_at: string;
}

interface Props extends PageProps {
    logs: PaginatedData<AuditLogEntry>;
    filters: { search?: string; date_from?: string; date_to?: string };
}

export default function Index({ logs, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');
    const [expandedId, setExpandedId] = useState<string | null>(null);

    const applyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/audit-log', { search, date_from: dateFrom, date_to: dateTo }, { preserveState: true });
    };

    const eventColor: Record<string, string> = {
        created: 'text-green-600 bg-green-50',
        updated: 'text-blue-600 bg-blue-50',
        deleted: 'text-red-600 bg-red-50',
    };

    return (
        <AuthenticatedLayout>
            <Head title="Audit Log" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Audit Log</h1>

                <form onSubmit={applyFilters} className="flex flex-wrap gap-3">
                    <div className="w-64"><Input placeholder="Search..." value={search} onChange={(e) => setSearch(e.target.value)} /></div>
                    <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
                    <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                    <button type="submit" className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                </form>

                <div className="rounded-lg bg-white shadow">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Date</th>
                                    <th className="px-4 py-3">User</th>
                                    <th className="px-4 py-3">Event</th>
                                    <th className="px-4 py-3">Entity</th>
                                    <th className="px-4 py-3">IP</th>
                                    <th className="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {logs.data.map((log) => (
                                    <>
                                        <tr key={log.id} className="hover:bg-gray-50 cursor-pointer" onClick={() => setExpandedId(expandedId === log.id ? null : log.id)}>
                                            <td className="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{new Date(log.created_at).toLocaleString('en-US')}</td>
                                            <td className="px-4 py-3">{log.user?.name ?? 'System'}</td>
                                            <td className="px-4 py-3">
                                                <span className={`inline-block rounded px-2 py-0.5 text-xs font-medium ${eventColor[log.event] ?? 'text-gray-600 bg-gray-50'}`}>
                                                    {log.event}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs">{log.auditable_type.split('\\').pop()} #{log.auditable_id?.slice(0, 8)}</td>
                                            <td className="px-4 py-3 text-xs text-gray-400">{log.ip_address}</td>
                                            <td className="px-4 py-3 text-xs text-indigo-500">{expandedId === log.id ? '▲' : '▼'}</td>
                                        </tr>
                                        {expandedId === log.id && (
                                            <tr key={`${log.id}-details`}>
                                                <td colSpan={6} className="bg-gray-50 px-6 py-4">
                                                    <div className="grid gap-4 sm:grid-cols-2">
                                                        {log.old_values && (
                                                            <div>
                                                                <p className="mb-1 text-xs font-semibold text-gray-500">Old Values</p>
                                                                <pre className="max-h-40 overflow-auto rounded bg-white p-2 text-xs">{JSON.stringify(log.old_values, null, 2)}</pre>
                                                            </div>
                                                        )}
                                                        {log.new_values && (
                                                            <div>
                                                                <p className="mb-1 text-xs font-semibold text-gray-500">New Values</p>
                                                                <pre className="max-h-40 overflow-auto rounded bg-white p-2 text-xs">{JSON.stringify(log.new_values, null, 2)}</pre>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <p className="mt-2 text-xs text-gray-400">User-Agent: {log.user_agent}</p>
                                                </td>
                                            </tr>
                                        )}
                                    </>
                                ))}
                                {logs.data.length === 0 && (
                                    <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-500">No events found.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={logs.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
