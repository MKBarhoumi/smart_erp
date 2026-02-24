import { Head, router } from '@inertiajs/react';
import { Select } from '@/Components/ui/Select';
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
                        <h1 className="text-2xl font-bold text-gray-900">Revenue</h1>
                        <p className="mt-1 text-sm text-gray-500">Total {year}: <strong>{parseFloat(yearlyTotal).toFixed(3)} TND</strong></p>
                    </div>
                    <div className="w-32">
                        <Select
                            options={availableYears.map((y) => ({ value: String(y), label: String(y) }))}
                            value={String(year)}
                            onChange={(e) => changeYear(e.target.value)}
                        />
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
                                                {val > 0 ? `${val.toFixed(3)} TND` : ''}
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
                                    <td className="px-3 py-2 text-right font-medium">{parseFloat(row.total).toFixed(3)} TND</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="border-t font-bold">
                            <tr>
                                <td className="px-3 py-2">Total</td>
                                <td className="px-3 py-2 text-right">{data.reduce((s, r) => s + r.count, 0)}</td>
                                <td className="px-3 py-2 text-right">{parseFloat(yearlyTotal).toFixed(3)} TND</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
