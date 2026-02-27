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

// Helper function to format audit log values for display
function formatAuditValue(key: string, value: unknown): string {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') {
        if (Array.isArray(value)) {
            return value.length > 0 ? `${value.length} item(s)` : 'Empty';
        }
        return JSON.stringify(value, null, 2);
    }
    return String(value);
}

// Helper function to get user-friendly field names
function formatFieldName(field: string): string {
    const fieldNames: Record<string, string> = {
        status: 'Status',
        total_ttc: 'Total TTC',
        total_ht: 'Total HT',
        total_tva: 'Total TVA',
        oldinvoice_number: 'Invoice Number',
        document_identifier: 'Document ID',
        customer_id: 'Customer',
        created_by: 'Created By',
        updated_at: 'Updated At',
        created_at: 'Created At',
        oldinvoice_date: 'Invoice Date',
        due_date: 'Due Date',
        notes: 'Notes',
        ref_ttn_val: 'TTN Reference',
    };
    return fieldNames[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

// Component to render value changes nicely
function ValueChangeDisplay({ label, oldVal, newVal }: { label: string; oldVal?: unknown; newVal?: unknown }) {
    const formattedOld = formatAuditValue(label, oldVal);
    const formattedNew = formatAuditValue(label, newVal);
    
    // Skip if both are the same or both empty
    if (formattedOld === formattedNew) return null;
    
    return (
        <div className="flex items-start gap-2 py-1.5 border-b border-gray-100 last:border-0">
            <span className="w-32 flex-shrink-0 text-xs font-medium text-gray-600">{formatFieldName(label)}</span>
            <div className="flex-1 flex items-center gap-2">
                {oldVal !== undefined && (
                    <span className="inline-flex items-center rounded bg-red-50 px-2 py-0.5 text-xs text-red-700 line-through">
                        {formattedOld}
                    </span>
                )}
                {oldVal !== undefined && newVal !== undefined && (
                    <svg className="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                )}
                {newVal !== undefined && (
                    <span className="inline-flex items-center rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">
                        {formattedNew}
                    </span>
                )}
            </div>
        </div>
    );
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
                                                    <div className="rounded-lg bg-white p-4 shadow-sm">
                                                        <h4 className="mb-3 text-sm font-semibold text-gray-700">Changes</h4>
                                                        <div className="space-y-0">
                                                            {/* Collect all unique keys from both old and new values */}
                                                            {(() => {
                                                                const oldVals = typeof log.old_values === 'string' 
                                                                    ? JSON.parse(log.old_values) 
                                                                    : (log.old_values || {});
                                                                const newVals = typeof log.new_values === 'string' 
                                                                    ? JSON.parse(log.new_values) 
                                                                    : (log.new_values || {});
                                                                const allKeys = [...new Set([...Object.keys(oldVals), ...Object.keys(newVals)])];
                                                                
                                                                if (allKeys.length === 0) {
                                                                    return <p className="text-sm text-gray-500">No detailed changes available.</p>;
                                                                }
                                                                
                                                                return allKeys.map(key => (
                                                                    <ValueChangeDisplay 
                                                                        key={key}
                                                                        label={key}
                                                                        oldVal={oldVals[key]}
                                                                        newVal={newVals[key]}
                                                                    />
                                                                ));
                                                            })()}
                                                        </div>
                                                    </div>
                                                    <p className="mt-3 text-xs text-gray-400">
                                                        <span className="font-medium">User-Agent:</span> {log.user_agent?.substring(0, 80)}...
                                                    </p>
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
