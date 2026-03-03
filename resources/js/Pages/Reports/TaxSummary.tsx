import { Head, Link, router } from '@inertiajs/react';
import { Select } from '@/Components/ui/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatTND, formatNumber } from '@/utils/format';

interface QuarterData {
    quarter: number;
    tva_collected: string;
    timbre_fiscal: string;
    total_tax: string;
    taxable_base: string;
    oldinvoice_count: number;
}

interface Props {
    data: QuarterData[];
    year: number;
    availableYears: number[];
    totals: { tva: string; timbre: string; total: string; base: string };
}

const quarterLabels: Record<number, string> = { 1: 'Q1 (Jan-Mar)', 2: 'Q2 (Apr-Jun)', 3: 'Q3 (Jul-Sep)', 4: 'Q4 (Oct-Dec)' };

export default function TaxSummary({ data, year, availableYears, totals }: Props) {
    const safeData = data ?? [];
    const safeAvailableYears = availableYears ?? [];
    const safeTotals = totals ?? { tva: '0', timbre: '0', total: '0', base: '0' };
    const safeYear = year ?? new Date().getFullYear();

    return (
        <AuthenticatedLayout>
            <Head title="Tax Summary" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <Link href="/reports" className="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Reports
                        </Link>
                        <h1 className="mt-2 text-2xl font-bold text-gray-900">Tax Summary — {safeYear}</h1>
                    </div>
                    <div className="flex items-center gap-3">
                        <a
                            href={`/reports/tax-summary/pdf?year=${safeYear}`}
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
                                options={safeAvailableYears.map((y) => ({ value: String(y), label: String(y) }))}
                                value={String(safeYear)}
                                onChange={(e) => router.get('/reports/tax-summary', { year: e.target.value }, { preserveState: true })}
                            />
                        </div>
                    </div>
                </div>

                {/* Summary cards */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="rounded-lg bg-white p-5 shadow">
                        <p className="text-sm text-gray-500">VAT Collected</p>
                        <p className="mt-1 text-2xl font-bold text-blue-600">{formatTND(safeTotals.tva)}</p>
                    </div>
                    <div className="rounded-lg bg-white p-5 shadow">
                        <p className="text-sm text-gray-500">Stamp Duty</p>
                        <p className="mt-1 text-2xl font-bold text-purple-600">{formatTND(safeTotals.timbre)}</p>
                    </div>
                    <div className="rounded-lg bg-white p-5 shadow">
                        <p className="text-sm text-gray-500">Total Taxes</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">{formatTND(safeTotals.total)}</p>
                    </div>
                </div>

                {/* Quarterly Table */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Quarterly Detail</h2>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Quarter</th>
                                    <th className="px-4 py-3 text-right">OldInvoices</th>
                                    <th className="px-4 py-3 text-right">Taxable Base</th>
                                    <th className="px-4 py-3 text-right">VAT</th>
                                    <th className="px-4 py-3 text-right">Stamp</th>
                                    <th className="px-4 py-3 text-right">Total Taxes</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {safeData.map((q) => (
                                    <tr key={q.quarter}>
                                        <td className="px-4 py-3 font-medium">{quarterLabels[q.quarter]}</td>
                                        <td className="px-4 py-3 text-right">{q.oldinvoice_count}</td>
                                        <td className="px-4 py-3 text-right">{formatNumber(q.taxable_base)}</td>
                                        <td className="px-4 py-3 text-right">{formatNumber(q.tva_collected)}</td>
                                        <td className="px-4 py-3 text-right">{formatNumber(q.timbre_fiscal)}</td>
                                        <td className="px-4 py-3 text-right font-semibold">{formatNumber(q.total_tax)}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="border-t font-bold">
                                <tr>
                                    <td className="px-4 py-3">Yearly Total</td>
                                    <td className="px-4 py-3 text-right">{safeData.reduce((s, q) => s + (q.oldinvoice_count || 0), 0)}</td>
                                    <td className="px-4 py-3 text-right">{formatNumber(safeTotals.base)}</td>
                                    <td className="px-4 py-3 text-right">{formatNumber(safeTotals.tva)}</td>
                                    <td className="px-4 py-3 text-right">{formatNumber(safeTotals.timbre)}</td>
                                    <td className="px-4 py-3 text-right">{formatNumber(safeTotals.total)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
