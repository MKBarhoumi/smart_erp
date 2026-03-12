import { Head, Link, router } from '@inertiajs/react';
import { Select } from '@/Components/ui/Select';
import { formatTND, formatNumber } from '@/utils/format';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';

interface Customer {
    id: string;
    name: string;
}

interface MonthlyData {
    month: string;
    total: string;
    count: number;
}

interface Props {
    data: MonthlyData[];
    year: number;
    month?: number | null;
    quarter?: number | null;
    customer_id?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    availableYears: number[];
    yearlyTotal: string;
    customers: Customer[];
    filters: {
        year: number;
        month?: number | null;
        quarter?: number | null;
        customer_id?: string | null;
        start_date?: string | null;
        end_date?: string | null;
    };
}

const months = [
    { value: '', label: 'All Months' },
    { value: '1', label: 'January' },
    { value: '2', label: 'February' },
    { value: '3', label: 'March' },
    { value: '4', label: 'April' },
    { value: '5', label: 'May' },
    { value: '6', label: 'June' },
    { value: '7', label: 'July' },
    { value: '8', label: 'August' },
    { value: '9', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

const quarters = [
    { value: '', label: 'All Quarters' },
    { value: '1', label: 'Q1 (Jan-Mar)' },
    { value: '2', label: 'Q2 (Apr-Jun)' },
    { value: '3', label: 'Q3 (Jul-Sep)' },
    { value: '4', label: 'Q4 (Oct-Dec)' },
];

export default function Revenue({ data = [], year, availableYears = [], yearlyTotal = '0', customers = [], filters }: Props) {
    const maxVal = Math.max(...data.map((d) => parseFloat(d.total)), 1);
    const [showAdvanced, setShowAdvanced] = useState(false);
    
    const currentFilters = filters || { year: year };

    const applyFilters = (newFilters: Record<string, string | number | null | undefined>) => {
        const params: Record<string, string | number | null | undefined> = { ...currentFilters, ...newFilters };
        // Remove empty values
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null || params[key] === undefined) {
                delete params[key];
            }
        });
        router.get('/reports/revenue', params as Record<string, string | number>, { preserveState: true });
    };

    const resetFilters = () => {
        router.get('/reports/revenue', { year: new Date().getFullYear() }, { preserveState: true });
    };

    const hasActiveFilters = currentFilters.month || currentFilters.quarter || currentFilters.customer_id || currentFilters.start_date || currentFilters.end_date;

    return (
        <AuthenticatedLayout>
            <Head title="Revenue" />

            <div className="space-y-6">
                <div className="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div>
                        <Link href="/reports" className="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Reports
                        </Link>
                        <h1 className="mt-2 text-2xl font-bold text-gray-900">Revenue Report</h1>
                        <p className="mt-1 text-sm text-gray-500">Total: <strong className="text-emerald-600">{formatTND(yearlyTotal)}</strong></p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <a
                            href={`/reports/revenue/pdf?${new URLSearchParams(currentFilters as any).toString()}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download PDF
                        </a>
                    </div>
                </div>

                {/* Filters */}
                <div className="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <svg className="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                            </svg>
                            Filters
                        </h3>
                        <button
                            onClick={() => setShowAdvanced(!showAdvanced)}
                            className="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
                        >
                            {showAdvanced ? 'Simple Filters' : 'Advanced Filters'}
                        </button>
                    </div>
                    
                    {/* Basic Filters */}
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">Year</label>
                            <Select
                                options={availableYears.map((y) => ({ value: String(y), label: String(y) }))}
                                value={String(currentFilters.year || year)}
                                onChange={(e) => applyFilters({ year: parseInt(e.target.value) })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">Month</label>
                            <Select
                                options={months}
                                value={String(currentFilters.month || '')}
                                onChange={(e) => applyFilters({ month: e.target.value ? parseInt(e.target.value) : null, quarter: null })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">Quarter</label>
                            <Select
                                options={quarters}
                                value={String(currentFilters.quarter || '')}
                                onChange={(e) => applyFilters({ quarter: e.target.value ? parseInt(e.target.value) : null, month: null })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">Customer</label>
                            <Select
                                options={[{ value: '', label: 'All Customers' }, ...customers.map((c) => ({ value: String(c.id), label: c.name }))]}
                                value={String(currentFilters.customer_id || '')}
                                onChange={(e) => applyFilters({ customer_id: e.target.value || null })}
                            />
                        </div>
                        {hasActiveFilters && (
                            <div className="flex items-end">
                                <button
                                    onClick={resetFilters}
                                    className="w-full px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg border border-red-200 transition-colors"
                                >
                                    Clear Filters
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Advanced Filters */}
                    {showAdvanced && (
                        <div className="mt-4 pt-4 border-t border-gray-100">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-1">Start Date</label>
                                    <input
                                        type="date"
                                        className="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={currentFilters.start_date || ''}
                                        onChange={(e) => applyFilters({ start_date: e.target.value || null })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-1">End Date</label>
                                    <input
                                        type="date"
                                        className="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={currentFilters.end_date || ''}
                                        onChange={(e) => applyFilters({ end_date: e.target.value || null })}
                                    />
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Active Filter Tags */}
                {hasActiveFilters && (
                    <div className="flex flex-wrap gap-2">
                        {currentFilters.month && (
                            <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-sm">
                                Month: {months.find(m => m.value === String(currentFilters.month))?.label}
                                <button onClick={() => applyFilters({ month: null })} className="hover:text-indigo-900">×</button>
                            </span>
                        )}
                        {currentFilters.quarter && (
                            <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-sm">
                                {quarters.find(q => q.value === String(currentFilters.quarter))?.label}
                                <button onClick={() => applyFilters({ quarter: null })} className="hover:text-purple-900">×</button>
                            </span>
                        )}
                        {currentFilters.customer_id && (
                            <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm">
                                Customer: {customers.find(c => c.id === currentFilters.customer_id)?.name}
                                <button onClick={() => applyFilters({ customer_id: null })} className="hover:text-emerald-900">×</button>
                            </span>
                        )}
                        {currentFilters.start_date && (
                            <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm">
                                From: {currentFilters.start_date}
                                <button onClick={() => applyFilters({ start_date: null })} className="hover:text-amber-900">×</button>
                            </span>
                        )}
                        {currentFilters.end_date && (
                            <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm">
                                To: {currentFilters.end_date}
                                <button onClick={() => applyFilters({ end_date: null })} className="hover:text-amber-900">×</button>
                            </span>
                        )}
                    </div>
                )}

                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="space-y-3">
                        {data.length === 0 ? (
                            <div className="text-center py-12">
                                <svg className="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" strokeWidth={1} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <p className="mt-2 text-sm text-gray-500">No revenue data for selected filters</p>
                            </div>
                        ) : (
                            data.map((row) => {
                                const val = parseFloat(row.total);
                                const pct = (val / maxVal) * 100;
                                return (
                                    <div key={row.month} className="flex items-center gap-4">
                                        <span className="w-12 text-right text-sm font-medium text-gray-600">{row.month}</span>
                                        <div className="flex-1">
                                            <div className="h-8 rounded bg-gray-100">
                                                <div className="flex h-8 items-center rounded bg-gradient-to-r from-indigo-600 to-indigo-400 px-2 text-xs font-medium text-white" style={{ width: `${Math.max(pct, 2)}%` }}>
                                                    {val > 0 ? formatTND(val) : ''}
                                                </div>
                                            </div>
                                        </div>
                                        <span className="w-12 text-right text-xs text-gray-400">{row.count} inv.</span>
                                    </div>
                                );
                            })
                        )}
                    </div>
                </div>

                {/* Summary Table */}
                {data.length > 0 && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">Monthly Detail</h2>
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr><th className="px-3 py-2">Month</th><th className="px-3 py-2 text-right">Invoices</th><th className="px-3 py-2 text-right">Total incl. tax</th></tr>
                            </thead>
                            <tbody className="divide-y">
                                {data.map((row) => (
                                    <tr key={row.month}>
                                        <td className="px-3 py-2">{row.month}</td>
                                        <td className="px-3 py-2 text-right">{row.count}</td>
                                        <td className="px-3 py-2 text-right font-medium">{formatTND(row.total)}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="border-t font-bold">
                                <tr>
                                    <td className="px-3 py-2">Total</td>
                                    <td className="px-3 py-2 text-right">{data.reduce((s, r) => s + r.count, 0)}</td>
                                    <td className="px-3 py-2 text-right">{formatTND(yearlyTotal)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
