import { Head, Link } from '@inertiajs/react';
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

const statusColors: Record<string, { bg: string; text: string; dot: string }> = {
    draft: { bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-400' },
    validated: { bg: 'bg-blue-100', text: 'text-blue-700', dot: 'bg-blue-500' },
    signed: { bg: 'bg-indigo-100', text: 'text-indigo-700', dot: 'bg-indigo-500' },
    submitted: { bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
    accepted: { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
    archived: { bg: 'bg-gray-100', text: 'text-gray-500', dot: 'bg-gray-400' },
};

export default function Dashboard({
    stats = { monthly_revenue: '0.000', outstanding_balance: '0.000', total_customers: 0, total_products: 0, oldinvoices_this_month: 0, pending_oldinvoices: 0 },
    recentOldInvoices = [],
    revenueChart = {},
    topCustomers = [],
    lowStockProducts = [],
}: Props) {
    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="space-y-8">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                        <p className="mt-1 text-gray-500">Welcome back! Here&apos;s what&apos;s happening with your business.</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Link href="/oldinvoices/create" className="inline-flex items-center gap-2 px-4 py-2.5 bg-user-600 text-white text-sm font-semibold rounded-xl hover:bg-user-700 transition-all shadow-lg shadow-user-600/25 hover:shadow-xl hover:shadow-user-600/30 hover:-translate-y-0.5">
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            New Invoice
                        </Link>
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <StatCard label="Monthly Revenue" value={formatTND(stats.monthly_revenue)} icon="revenue" color="emerald" trend="+12.5%" />
                    <StatCard label="Outstanding Balance" value={formatTND(stats.outstanding_balance)} icon="balance" color="rose" trend="-3.2%" />
                    <StatCard label="Invoices This Month" value={String(stats.oldinvoices_this_month)} icon="invoices" color="blue" />
                    <StatCard label="Pending Drafts" value={String(stats.pending_oldinvoices)} icon="pending" color="amber" />
                    <StatCard label="Total Customers" value={String(stats.total_customers)} icon="customers" color="indigo" />
                    <StatCard label="Total Products" value={String(stats.total_products)} icon="products" color="purple" />
                </div>

                {/* Main Content Grid */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Recent Invoices */}
                    <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                        <div className="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-xl bg-user-100">
                                    <svg className="w-5 h-5 text-user-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                </div>
                                <h2 className="text-lg font-semibold text-gray-900">Recent Invoices</h2>
                            </div>
                            <Link href="/oldinvoices" className="text-sm font-medium text-user-600 hover:text-user-700 transition-colors">View all →</Link>
                        </div>
                        <div className="divide-y divide-gray-50">
                            {recentOldInvoices.length === 0 ? (
                                <div className="px-6 py-12 text-center">
                                    <div className="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                                        <svg className="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    </div>
                                    <p className="text-sm text-gray-500">No invoices yet</p>
                                    <Link href="/oldinvoices/create" className="mt-2 inline-flex text-sm font-medium text-user-600 hover:text-user-700">Create your first invoice →</Link>
                                </div>
                            ) : (
                                recentOldInvoices.map((inv) => {
                                    const status = statusColors[inv.status] ?? statusColors.draft;
                                    return (
                                        <Link key={inv.id} href={`/oldinvoices/${inv.id}`} className="flex items-center justify-between px-6 py-4 hover:bg-gray-50/80 transition-colors group">
                                            <div className="flex items-center gap-4">
                                                <div className={`w-10 h-10 rounded-xl ${status.bg} flex items-center justify-center`}>
                                                    <span className={`w-2.5 h-2.5 rounded-full ${status.dot}`} />
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-gray-900 group-hover:text-user-600 transition-colors">{inv.oldinvoice_number}</p>
                                                    <p className="text-sm text-gray-500">{inv.customer?.name ?? 'Unknown'}</p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="font-semibold text-gray-900">{formatTND(inv.total_ttc)}</p>
                                                <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${status.bg} ${status.text}`}>{inv.status}</span>
                                            </div>
                                        </Link>
                                    );
                                })
                            )}
                        </div>
                    </div>

                    {/* Top Customers */}
                    <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                        <div className="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-xl bg-indigo-100">
                                    <svg className="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                </div>
                                <h2 className="text-lg font-semibold text-gray-900">Top Customers</h2>
                            </div>
                            <Link href="/customers" className="text-sm font-medium text-user-600 hover:text-user-700 transition-colors">View all →</Link>
                        </div>
                        <div className="divide-y divide-gray-50">
                            {topCustomers.length === 0 ? (
                                <div className="px-6 py-12 text-center">
                                    <div className="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                                        <svg className="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                    </div>
                                    <p className="text-sm text-gray-500">No customers yet</p>
                                </div>
                            ) : (
                                topCustomers.map((cust, idx) => (
                                    <Link key={cust.id} href={`/customers/${cust.id}`} className="flex items-center justify-between px-6 py-4 hover:bg-gray-50/80 transition-colors group">
                                        <div className="flex items-center gap-4">
                                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold ${idx === 0 ? 'bg-gradient-to-br from-amber-400 to-amber-500 text-white' : idx === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-400 text-white' : idx === 2 ? 'bg-gradient-to-br from-amber-600 to-amber-700 text-white' : 'bg-gray-100 text-gray-600'}`}>
                                                {idx + 1}
                                            </div>
                                            <span className="font-medium text-gray-900 group-hover:text-user-600 transition-colors">{cust.name}</span>
                                        </div>
                                        <span className="font-semibold text-gray-900">{formatTND(cust.oldinvoices_sum_total_ttc ?? '0')}</span>
                                    </Link>
                                ))
                            )}
                        </div>
                    </div>
                </div>

                {/* Low Stock Alert */}
                {lowStockProducts.length > 0 && (
                    <div className="rounded-2xl bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200 p-6">
                        <div className="flex items-start gap-4">
                            <div className="p-3 rounded-xl bg-amber-100">
                                <svg className="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            </div>
                            <div className="flex-1">
                                <h3 className="font-semibold text-amber-800">Low Stock Alerts</h3>
                                <p className="text-sm text-amber-700 mt-1">The following products are running low on stock:</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {lowStockProducts.map((p) => (
                                        <Link key={p.id} href={`/products/${p.id}`} className="inline-flex items-center gap-2 px-3 py-1.5 bg-white rounded-lg border border-amber-200 text-sm hover:border-amber-300 transition-colors">
                                            <span className="font-mono text-xs text-amber-600">{p.code}</span>
                                            <span className="text-gray-700">{p.name}</span>
                                            <span className="text-red-600 font-semibold">{p.current_stock}</span>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Revenue Chart */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-6">
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-emerald-100">
                                <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            </div>
                            <h2 className="text-lg font-semibold text-gray-900">Monthly Revenue</h2>
                        </div>
                        <div className="flex items-center gap-2 text-sm text-gray-500">
                            <span className="w-3 h-3 rounded-full bg-gradient-to-r from-user-500 to-purple-500" />
                            Revenue (TND)
                        </div>
                    </div>
                    <div className="flex h-64 items-end gap-3">
                        {(() => {
                            const maxVal = Math.max(...Object.values(revenueChart).map(Number), 1);
                            return Object.entries(revenueChart).map(([month, total]) => {
                                const heightPx = Math.max((Number(total) / maxVal) * 200, 8);
                                return (
                                    <div key={month} className="flex flex-1 flex-col items-center justify-end group">
                                        <div className="relative w-full flex items-end justify-center" style={{ height: '200px' }}>
                                            <div className="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">{formatTND(total)}</div>
                                            <div className="w-full rounded-t-lg bg-gradient-to-t from-user-600 to-purple-500 transition-all duration-300 group-hover:from-user-500 group-hover:to-purple-400" style={{ height: `${heightPx}px` }} />
                                        </div>
                                        <span className="mt-3 text-xs font-medium text-gray-500">{month.slice(5)}</span>
                                    </div>
                                );
                            });
                        })()}
                        {Object.keys(revenueChart).length === 0 && (
                            <div className="flex-1 flex items-center justify-center">
                                <p className="text-sm text-gray-400">No revenue data available</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

const icons: Record<string, JSX.Element> = {
    revenue: <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>,
    balance: <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>,
    invoices: <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>,
    pending: <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>,
    customers: <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>,
    products: <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>,
};

const colorMap: Record<string, { iconBg: string; iconText: string; valueBg: string }> = {
    emerald: { iconBg: 'bg-gradient-to-br from-emerald-500 to-green-600', iconText: 'text-white', valueBg: 'text-emerald-600' },
    rose: { iconBg: 'bg-gradient-to-br from-rose-500 to-red-600', iconText: 'text-white', valueBg: 'text-rose-600' },
    blue: { iconBg: 'bg-gradient-to-br from-blue-500 to-blue-600', iconText: 'text-white', valueBg: 'text-blue-600' },
    amber: { iconBg: 'bg-gradient-to-br from-amber-500 to-yellow-500', iconText: 'text-white', valueBg: 'text-amber-600' },
    indigo: { iconBg: 'bg-gradient-to-br from-indigo-500 to-indigo-600', iconText: 'text-white', valueBg: 'text-indigo-600' },
    purple: { iconBg: 'bg-gradient-to-br from-purple-500 to-violet-600', iconText: 'text-white', valueBg: 'text-purple-600' },
};

function StatCard({ label, value, icon, color, trend }: { label: string; value: string; icon: string; color: string; trend?: string }) {
    const c = colorMap[color] ?? colorMap.blue;
    const isPositive = trend?.startsWith('+');

    return (
        <div className="relative bg-white rounded-2xl p-6 shadow-soft border border-gray-100 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 overflow-hidden group">
            <div className="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-gray-50 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity" />
            <div className="relative flex items-start justify-between">
                <div className="flex-1">
                    <p className="text-sm font-medium text-gray-500">{label}</p>
                    <p className={`mt-2 text-3xl font-bold tracking-tight ${c.valueBg}`}>{value}</p>
                    {trend && (
                        <div className={`mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold ${isPositive ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}`}>
                            {isPositive ? <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg> : <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>}
                            {trend}
                        </div>
                    )}
                </div>
                <div className={`${c.iconBg} p-3.5 rounded-2xl ${c.iconText} shadow-lg`}>{icons[icon] ?? icons.revenue}</div>
            </div>
        </div>
    );
}
