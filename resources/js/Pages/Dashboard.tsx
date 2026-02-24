import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

function formatTND(amount: string | number): string {
    return Number(amount).toLocaleString('en-US', {
        minimumFractionDigits: 3,
        maximumFractionDigits: 3,
    }) + ' TND';
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

function StatCard({ label, value, color }: { label: string; value: string; color: string }) {
    const colorMap: Record<string, { bg: string; text: string; border: string }> = {
        blue: { bg: 'bg-blue-50', text: 'text-blue-600', border: 'border-blue-200' },
        red: { bg: 'bg-red-50', text: 'text-red-600', border: 'border-red-200' },
        green: { bg: 'bg-green-50', text: 'text-green-600', border: 'border-green-200' },
        yellow: { bg: 'bg-yellow-50', text: 'text-yellow-600', border: 'border-yellow-200' },
        indigo: { bg: 'bg-indigo-50', text: 'text-indigo-600', border: 'border-indigo-200' },
        purple: { bg: 'bg-purple-50', text: 'text-purple-600', border: 'border-purple-200' },
    };
    const c = colorMap[color] ?? { bg: 'bg-gray-50', text: 'text-gray-900', border: 'border-gray-200' };

    return (
        <div className={`rounded-xl border ${c.border} ${c.bg} p-5 transition-shadow hover:shadow-md`}>
            <p className="text-sm font-medium text-gray-500">{label}</p>
            <p className={`mt-1 text-2xl font-bold ${c.text}`}>{value}</p>
        </div>
    );
}
