import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatTND } from '@/utils/format';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    stats: {
        monthly_revenue: string;
        outstanding_balance: string;
        total_customers: number;
        total_products: number;
        oldinvoices_this_month: number;
        pending_oldinvoices: number;
    };
    recentOldInvoices: Array<{
        id: string;
        oldinvoice_number: string;
        customer: { id: string; name: string } | null;
        total_ttc: string;
        status: string;
        oldinvoice_date: string;
    }>;
    revenueChart: Record<string, string>;
    statusDistribution: Record<string, number>;
    topCustomers: Array<{
        id: string;
        name: string;
        oldinvoices_sum_total_ttc: string | null;
    }>;
    lowStockProducts: Array<{
        id: string;
        name: string;
        code: string;
        current_stock: string;
        min_stock_alert: string;
    }>;
}

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700',
    validated: 'bg-blue-100 text-blue-700',
    signed: 'bg-indigo-100 text-indigo-700',
    submitted: 'bg-yellow-100 text-yellow-700',
    accepted: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
    archived: 'bg-gray-100 text-gray-500',
};

export default function Dashboard({
    stats = {
        monthly_revenue: '0.000',
        outstanding_balance: '0.000',
        total_customers: 0,
        total_products: 0,
        oldinvoices_this_month: 0,
        pending_oldinvoices: 0,
    },
    recentOldInvoices = [],
    revenueChart = {},
    topCustomers = [],
    lowStockProducts = [],
}: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>

                {/* Stats cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <StatCard label="Monthly Revenue" value={formatTND(stats.monthly_revenue)} color="blue" />
                    <StatCard label="Outstanding Balance" value={formatTND(stats.outstanding_balance)} color="red" />
                    <StatCard label="OldInvoices This Month" value={String(stats.oldinvoices_this_month)} color="green" />
                    <StatCard label="Pending Drafts" value={String(stats.pending_oldinvoices)} color="yellow" />
                    <StatCard label="Customers" value={String(stats.total_customers)} color="indigo" />
                    <StatCard label="Products" value={String(stats.total_products)} color="purple" />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Recent OldInvoices */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold text-gray-900">Recent OldInvoices</h2>
                        <div className="space-y-3">
                            {recentOldInvoices.length === 0 && (
                                <p className="text-sm text-gray-500">No oldinvoices yet.</p>
                            )}
                            {recentOldInvoices.map((inv) => (
                                <div key={inv.id} className="flex items-center justify-between rounded-lg border p-3">
                                    <div>
                                        <p className="font-medium text-gray-900">{inv.oldinvoice_number}</p>
                                        <p className="text-sm text-gray-500">{inv.customer?.name ?? '—'}</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-medium">{formatTND(inv.total_ttc)}</p>
                                        <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${statusColors[inv.status] ?? 'bg-gray-100'}`}>
                                            {inv.status}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Top Customers */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold text-gray-900">Top Customers</h2>
                        <div className="space-y-3">
                            {topCustomers.length === 0 && (
                                <p className="text-sm text-gray-500">No customers yet.</p>
                            )}
                            {topCustomers.map((cust, idx) => (
                                <div key={cust.id} className="flex items-center justify-between rounded-lg border p-3">
                                    <div className="flex items-center gap-3">
                                        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
                                            {idx + 1}
                                        </span>
                                        <span className="font-medium text-gray-900">{cust.name}</span>
                                    </div>
                                    <span className="font-medium text-gray-700">
                                        {formatTND(cust.oldinvoices_sum_total_ttc ?? '0')}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Low stock alerts */}
                {lowStockProducts.length > 0 && (
                    <div className="rounded-lg border-l-4 border-yellow-400 bg-yellow-50 p-4">
                        <h3 className="font-semibold text-yellow-800">Low Stock Alerts</h3>
                        <div className="mt-2 space-y-1">
                            {lowStockProducts.map((p) => (
                                <p key={p.id} className="text-sm text-yellow-700">
                                    <strong>{p.code}</strong> — {p.name}: {p.current_stock} remaining (threshold: {p.min_stock_alert})
                                </p>
                            ))}
                        </div>
                    </div>
                )}

                {/* Revenue Chart placeholder */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">Monthly Revenue</h2>
                    <div className="flex h-48 items-end gap-2">
                        {Object.entries(revenueChart).map(([month, total]) => {
                            const maxVal = Math.max(...Object.values(revenueChart).map(Number), 1);
                            const height = (Number(total) / maxVal) * 100;
                            return (
                                <div key={month} className="flex flex-1 flex-col items-center">
                                    <div
                                        className="w-full rounded-t bg-gradient-to-t from-indigo-600 to-indigo-400 transition-all"
                                        style={{ height: `${Math.max(height, 2)}%` }}
                                        title={`${month}: ${formatTND(total)}`}
                                    />
                                    <span className="mt-1 text-xs text-gray-500">{month.slice(5)}</span>
                                </div>
                            );
                        })}
                        {Object.keys(revenueChart).length === 0 && (
                            <p className="mx-auto text-sm text-gray-400">No data available</p>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

const statIcons: Record<string, JSX.Element> = {
    blue: (
        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
        </svg>
    ),
    red: (
        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    ),
    green: (
        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
        </svg>
    ),
    yellow: (
        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
        </svg>
    ),
    indigo: (
        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
        </svg>
    ),
    purple: (
        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
    ),
};

function StatCard({ label, value, color }: { label: string; value: string; color: string }) {
    const colorMap: Record<string, { bg: string; text: string; iconBg: string; gradient: string }> = {
        blue: { bg: 'bg-white', text: 'text-blue-600', iconBg: 'bg-gradient-to-br from-blue-500 to-blue-600', gradient: 'from-blue-50 to-white' },
        red: { bg: 'bg-white', text: 'text-rose-600', iconBg: 'bg-gradient-to-br from-rose-500 to-red-600', gradient: 'from-rose-50 to-white' },
        green: { bg: 'bg-white', text: 'text-emerald-600', iconBg: 'bg-gradient-to-br from-emerald-500 to-green-600', gradient: 'from-emerald-50 to-white' },
        yellow: { bg: 'bg-white', text: 'text-amber-600', iconBg: 'bg-gradient-to-br from-amber-500 to-yellow-500', gradient: 'from-amber-50 to-white' },
        indigo: { bg: 'bg-white', text: 'text-indigo-600', iconBg: 'bg-gradient-to-br from-indigo-500 to-indigo-600', gradient: 'from-indigo-50 to-white' },
        purple: { bg: 'bg-white', text: 'text-purple-600', iconBg: 'bg-gradient-to-br from-purple-500 to-violet-600', gradient: 'from-purple-50 to-white' },
    };
    const c = colorMap[color] ?? { bg: 'bg-white', text: 'text-gray-900', iconBg: 'bg-gray-500', gradient: 'from-gray-50 to-white' };

    return (
        <div className={`relative rounded-2xl ${c.bg} p-6 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 overflow-hidden group`}>
            <div className={`absolute inset-0 bg-gradient-to-br ${c.gradient} opacity-0 group-hover:opacity-100 transition-opacity duration-300`} />
            <div className="relative flex items-start justify-between">
                <div>
                    <p className="text-sm font-medium text-gray-500">{label}</p>
                    <p className={`mt-2 text-3xl font-bold ${c.text}`}>{value}</p>
                </div>
                <div className={`${c.iconBg} p-3 rounded-xl text-white shadow-lg`}>
                    {statIcons[color] ?? statIcons.blue}
                </div>
            </div>
        </div>
    );
}
