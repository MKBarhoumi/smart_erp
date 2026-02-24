import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface AgingCustomer {
    id: string;
    name: string;
    identifier_value: string;
    total_outstanding: string;
    current: string;      // 0-30 days
    days_30_60: string;
    days_60_90: string;
    over_90: string;
    oldest_oldinvoice_date: string;
}

interface Props {
    customers: AgingCustomer[];
    totals: {
        total_outstanding: string;
        current: string;
        days_30_60: string;
        days_60_90: string;
        over_90: string;
    };
}

const defaultTotals = {
    total_outstanding: '0',
    current: '0',
    days_30_60: '0',
    days_60_90: '0',
    over_90: '0',
};

export default function CustomerAging({ customers = [], totals }: Props) {
    const safeTotals = totals ?? defaultTotals;
    const fmt = (val: string) => parseFloat(val || '0').toFixed(3);

    return (
        <AuthenticatedLayout>
            <Head title="Customer Aging" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Customer Aging Report</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Total outstanding: <strong>{fmt(safeTotals.total_outstanding)} TND</strong>
                    </p>
                </div>

                {/* Summary cards */}
                <div className="grid gap-4 sm:grid-cols-4">
                    <div className="rounded-lg bg-green-50 p-4 shadow">
                        <p className="text-xs text-gray-500">0-30 days</p>
                        <p className="mt-1 text-xl font-bold text-green-700">{fmt(safeTotals.current)} TND</p>
                    </div>
                    <div className="rounded-lg bg-yellow-50 p-4 shadow">
                        <p className="text-xs text-gray-500">30-60 days</p>
                        <p className="mt-1 text-xl font-bold text-yellow-700">{fmt(safeTotals.days_30_60)} TND</p>
                    </div>
                    <div className="rounded-lg bg-orange-50 p-4 shadow">
                        <p className="text-xs text-gray-500">60-90 days</p>
                        <p className="mt-1 text-xl font-bold text-orange-700">{fmt(safeTotals.days_60_90)} TND</p>
                    </div>
                    <div className="rounded-lg bg-red-50 p-4 shadow">
                        <p className="text-xs text-gray-500">+90 days</p>
                        <p className="mt-1 text-xl font-bold text-red-700">{fmt(safeTotals.over_90)} TND</p>
                    </div>
                </div>

                {/* Detail Table */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Customer</th>
                                    <th className="px-4 py-3">Tax ID</th>
                                    <th className="px-4 py-3 text-right">0-30d</th>
                                    <th className="px-4 py-3 text-right">30-60d</th>
                                    <th className="px-4 py-3 text-right">60-90d</th>
                                    <th className="px-4 py-3 text-right">+90d</th>
                                    <th className="px-4 py-3 text-right">Total</th>
                                    <th className="px-4 py-3">Oldest</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {customers.map((c) => {
                                    const over90 = parseFloat(c.over_90 || '0');
                                    return (
                                        <tr key={c.id} className={over90 > 0 ? 'bg-red-50' : ''}>
                                            <td className="px-4 py-3 font-medium">{c.name}</td>
                                            <td className="px-4 py-3 font-mono text-xs">{c.identifier_value}</td>
                                            <td className="px-4 py-3 text-right">{fmt(c.current)}</td>
                                            <td className="px-4 py-3 text-right">{fmt(c.days_30_60)}</td>
                                            <td className="px-4 py-3 text-right">{fmt(c.days_60_90)}</td>
                                            <td className="px-4 py-3 text-right font-medium text-red-600">{fmt(c.over_90)}</td>
                                            <td className="px-4 py-3 text-right font-semibold">{fmt(c.total_outstanding)}</td>
                                            <td className="px-4 py-3 text-xs text-gray-500">{c.oldest_oldinvoice_date}</td>
                                        </tr>
                                    );
                                })}
                                {customers.length === 0 && (
                                    <tr><td colSpan={8} className="px-4 py-8 text-center text-gray-500">No outstanding receivables.</td></tr>
                                )}
                            </tbody>
                            {customers.length > 0 && (
                                <tfoot className="border-t font-bold">
                                    <tr>
                                        <td className="px-4 py-3" colSpan={2}>Total</td>
                                        <td className="px-4 py-3 text-right">{fmt(totals.current)}</td>
                                        <td className="px-4 py-3 text-right">{fmt(totals.days_30_60)}</td>
                                        <td className="px-4 py-3 text-right">{fmt(totals.days_60_90)}</td>
                                        <td className="px-4 py-3 text-right text-red-600">{fmt(totals.over_90)}</td>
                                        <td className="px-4 py-3 text-right">{fmt(totals.total_outstanding)}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
