import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
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

function formatAuditValue(key: string, value: unknown): string {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') {
        if (Array.isArray(value)) return value.length > 0 ? `${value.length} item(s)` : 'Empty';
        return JSON.stringify(value, null, 2);
    }
    return String(value);
}

function formatFieldName(field: string): string {
    const fieldNames: Record<string, string> = { status: 'Status', total_ttc: 'Total TTC', total_ht: 'Total HT', total_tva: 'Total TVA', oldinvoice_number: 'Invoice Number', document_identifier: 'Document ID', customer_id: 'Customer', created_by: 'Created By', updated_at: 'Updated At', created_at: 'Created At', oldinvoice_date: 'Invoice Date', due_date: 'Due Date', notes: 'Notes', ref_ttn_val: 'TTN Reference' };
    return fieldNames[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function ValueChangeDisplay({ label, oldVal, newVal }: { label: string; oldVal?: unknown; newVal?: unknown }) {
    const formattedOld = formatAuditValue(label, oldVal);
    const formattedNew = formatAuditValue(label, newVal);
    if (formattedOld === formattedNew) return null;
    return (
        <div className="flex items-start gap-3 py-2 border-b border-gray-100 last:border-0">
            <span className="w-32 flex-shrink-0 text-xs font-medium text-gray-500">{formatFieldName(label)}</span>
            <div className="flex-1 flex flex-wrap items-center gap-2">
                {oldVal !== undefined && (<span className="inline-flex items-center rounded-lg bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 line-through">{formattedOld}</span>)}
                {oldVal !== undefined && newVal !== undefined && (<svg className="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>)}
                {newVal !== undefined && (<span className="inline-flex items-center rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">{formattedNew}</span>)}
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

    const eventStyles: Record<string, { bg: string; text: string; dot: string }> = {
        created: { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
        updated: { bg: 'bg-blue-100', text: 'text-blue-700', dot: 'bg-blue-500' },
        deleted: { bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
    };

    return (
        <AuthenticatedLayout>
            <Head title="Audit Log" />

            <div className="space-y-6">
                {/* Admin Page Header - Orange Theme */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-xl bg-gradient-to-br from-admin-500 to-red-500 text-white shadow-lg shadow-admin-500/25">
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight text-gray-900">Audit Log</h1>
                            <p className="text-admin-600 text-sm font-medium">Admin Panel • System activity tracking</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-gray-500">
                        <svg className="w-5 h-5 text-admin-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{logs.total} events recorded</span>
                    </div>
                </div>

                {/* Filters - Admin themed */}
                <form onSubmit={applyFilters} className="bg-gradient-to-r from-admin-50 to-orange-50 rounded-2xl border border-admin-200/50 p-5">
                    <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1 relative">
                            <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-admin-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                            <input type="text" placeholder="Search events, users..." value={search} onChange={(e) => setSearch(e.target.value)} className="w-full pl-12 pr-4 py-3 rounded-xl border-0 bg-white ring-1 ring-admin-200 focus:ring-2 focus:ring-admin-500 transition-all text-sm" />
                        </div>
                        <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="px-4 py-3 rounded-xl border-0 bg-white ring-1 ring-admin-200 focus:ring-2 focus:ring-admin-500 transition-all text-sm" />
                        <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="px-4 py-3 rounded-xl border-0 bg-white ring-1 ring-admin-200 focus:ring-2 focus:ring-admin-500 transition-all text-sm" />
                        <button type="submit" className="px-5 py-3 bg-gradient-to-r from-admin-600 to-admin-500 text-white text-sm font-semibold rounded-xl hover:from-admin-700 hover:to-admin-600 transition-all shadow-lg shadow-admin-500/25">
                            Apply Filters
                        </button>
                    </div>
                </form>

                {/* Stats Summary */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white rounded-xl p-4 border border-gray-100 shadow-soft">
                        <div className="flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-gray-400"></span><p className="text-sm text-gray-500">Total Events</p></div>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{logs.total}</p>
                    </div>
                    <div className="bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl p-4 border border-emerald-200/50">
                        <div className="flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-emerald-500"></span><p className="text-sm text-emerald-600">Created</p></div>
                        <p className="text-2xl font-bold text-emerald-700 mt-1">{logs.data.filter(l => l.event === 'created').length}</p>
                    </div>
                    <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200/50">
                        <div className="flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-blue-500"></span><p className="text-sm text-blue-600">Updated</p></div>
                        <p className="text-2xl font-bold text-blue-700 mt-1">{logs.data.filter(l => l.event === 'updated').length}</p>
                    </div>
                    <div className="bg-gradient-to-r from-red-50 to-rose-50 rounded-xl p-4 border border-red-200/50">
                        <div className="flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-red-500"></span><p className="text-sm text-red-600">Deleted</p></div>
                        <p className="text-2xl font-bold text-red-700 mt-1">{logs.data.filter(l => l.event === 'deleted').length}</p>
                    </div>
                </div>

                {/* Audit Log Table */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="bg-gradient-to-r from-admin-50 to-orange-50/50">
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-admin-700 uppercase tracking-wider">Date & Time</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-admin-700 uppercase tracking-wider">User</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-admin-700 uppercase tracking-wider">Event</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-admin-700 uppercase tracking-wider">Entity</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-admin-700 uppercase tracking-wider">IP Address</th>
                                    <th className="px-6 py-4 text-center text-xs font-semibold text-admin-700 uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {logs.data.map((log, idx) => {
                                    const style = eventStyles[log.event] ?? { bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-400' };
                                    return (
                                        <>
                                            <tr key={log.id} className={`cursor-pointer transition-colors hover:bg-admin-50/30 ${idx % 2 === 0 ? '' : 'bg-gray-50/30'} ${expandedId === log.id ? 'bg-admin-50' : ''}`} onClick={() => setExpandedId(expandedId === log.id ? null : log.id)}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900">{new Date(log.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div>
                                                    <div className="text-xs text-gray-500">{new Date(log.created_at).toLocaleTimeString('en-US')}</div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-admin-500 to-admin-600 flex items-center justify-center text-white text-sm font-semibold">{log.user?.name?.charAt(0) ?? 'S'}</div>
                                                        <span className="font-medium text-gray-900">{log.user?.name ?? 'System'}</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ${style.bg} ${style.text}`}>
                                                        <span className={`w-1.5 h-1.5 rounded-full ${style.dot}`} />
                                                        {log.event}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="text-sm font-medium text-gray-900">{log.auditable_type.split('\\').pop()}</div>
                                                    <div className="text-xs font-mono text-gray-400">#{log.auditable_id?.slice(0, 8)}</div>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500 font-mono">{log.ip_address}</td>
                                                <td className="px-6 py-4 text-center">
                                                    <button className={`p-1.5 rounded-lg transition-all ${expandedId === log.id ? 'bg-admin-100 text-admin-600' : 'text-gray-400 hover:text-admin-600 hover:bg-admin-50'}`}>
                                                        <svg className={`w-5 h-5 transition-transform ${expandedId === log.id ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                            {expandedId === log.id && (
                                                <tr key={`${log.id}-details`}>
                                                    <td colSpan={6} className="bg-gradient-to-r from-admin-50/50 to-orange-50/30 px-6 py-4">
                                                        <div className="rounded-xl bg-white p-5 shadow-sm border border-admin-100">
                                                            <h4 className="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                                                <svg className="w-4 h-4 text-admin-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                                                Changes
                                                            </h4>
                                                            <div className="space-y-0">
                                                                {(() => {
                                                                    const oldVals = typeof log.old_values === 'string' ? JSON.parse(log.old_values) : (log.old_values || {});
                                                                    const newVals = typeof log.new_values === 'string' ? JSON.parse(log.new_values) : (log.new_values || {});
                                                                    const allKeys = [...new Set([...Object.keys(oldVals), ...Object.keys(newVals)])];
                                                                    if (allKeys.length === 0) return <p className="text-sm text-gray-500">No detailed changes available.</p>;
                                                                    return allKeys.map(key => <ValueChangeDisplay key={key} label={key} oldVal={oldVals[key]} newVal={newVals[key]} />);
                                                                })()}
                                                            </div>
                                                        </div>
                                                        <p className="mt-3 text-xs text-gray-400"><span className="font-medium">User-Agent:</span> {log.user_agent?.substring(0, 80)}...</p>
                                                    </td>
                                                </tr>
                                            )}
                                        </>
                                    );
                                })}
                                {logs.data.length === 0 && (
                                    <tr><td colSpan={6} className="px-6 py-12 text-center">
                                        <div className="mx-auto w-16 h-16 rounded-2xl bg-admin-100 flex items-center justify-center mb-4"><svg className="w-8 h-8 text-admin-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg></div>
                                        <h3 className="text-lg font-semibold text-gray-900">No events found</h3>
                                        <p className="mt-1 text-sm text-gray-500">No audit logs match your current filters.</p>
                                    </td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="border-t border-gray-100 px-4 py-3"><Pagination links={logs.links} /></div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
