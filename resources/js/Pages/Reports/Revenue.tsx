import { Head, Link, router } from '@inertiajs/react';
import { Select } from '@/Components/ui/Select';
import { formatTND, formatNumber } from '@/utils/format';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface MonthlyData {
    month: string;
    total: string;
    count: number;
}

interface Props {
    data: MonthlyData[];
    year: number;
    availableYears: number[];
    yearlyTotal: string;
}

export default function Revenue({ data = [], year, availableYears = [], yearlyTotal = '0' }: Props) {
    const maxVal = Math.max(...data.map((d) => parseFloat(d.total)), 1);

    const changeYear = (y: string) => {
        router.get('/reports/revenue', { year: y }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Revenue" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <Link href="/reports" className="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Reports
                        </Link>
                        <h1 className="mt-2 text-2xl font-bold text-gray-900">Revenue</h1>
                        <p className="mt-1 text-sm text-gray-500">Total {year}: <strong>{formatTND(yearlyTotal)}</strong></p>
                    </div>
                    <div className="flex items-center gap-3">
                        <a
                            href={`/reports/revenue/pdf?year=${year}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download PDF
                        </a>
                        <div className="w-32">
                            <Select
                                options={availableYears.map((y) => ({ value: String(y), label: String(y) }))}
                                value={String(year)}
                                onChange={(e) => changeYear(e.target.value)}
                            />
                        </div>
                    </div>
                </div>

                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="space-y-3">
                        {data.map((row) => {
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
                        })}
                    </div>
                </div>

                {/* Summary Table */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Monthly Detail</h2>
                    <table className="min-w-full text-sm">
                        <thead className="border-b text-left text-xs uppercase text-gray-500">
                            <tr><th className="px-3 py-2">Month</th><th className="px-3 py-2 text-right">OldInvoices</th><th className="px-3 py-2 text-right">Total incl. tax</th></tr>
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
            </div>
        </AuthenticatedLayout>
    );
}
