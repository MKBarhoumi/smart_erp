import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatTND } from '@/utils/format';
import type { PageProps } from '@/types';

interface Props extends PageProps {
  year: number;
  monthlyTimbre: Array<{ month: number; total_timbre: string; oldinvoice_count: number }>;
  yearlyTotal: string;
  availableYears: number[];
}

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];

export default function Timbre({ year, monthlyTimbre, yearlyTotal, availableYears }: Props) {
  return (
    <AuthenticatedLayout>
      <Head title="Stamp Duty Report" />

      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <Link href="/reports" className="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Reports
          </Link>
            <h1 className="mt-2 text-2xl font-bold text-gray-900">Stamp Duty Report</h1>
            <p className="mt-1 text-sm text-gray-500">Monthly summary of stamp duty collected.</p>
          </div>
          <div className="flex items-center gap-3">
            <a
              href={`/reports/timbre/pdf?year=${year}`}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
            >
              <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Download PDF
            </a>
            <select
              value={year}
              onChange={(e) => router.get('/reports/timbre', { year: e.target.value }, { preserveState: true })}
              className="rounded-lg border-gray-300 text-sm"
            >
              {availableYears.map((y) => (
                <option key={y} value={y}>{y}</option>
              ))}
            </select>
          </div>
        </div>

        {/* Total Card */}
        <div className="rounded-lg bg-white p-6 shadow">
          <p className="text-sm text-gray-500">Total stamp duty {year}</p>
          <p className="mt-1 text-3xl font-bold text-gray-900">{formatTND(yearlyTotal)}</p>
        </div>

        {/* Monthly Breakdown */}
        <div className="overflow-hidden rounded-lg bg-white shadow">
          <table className="min-w-full text-sm">
            <thead className="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
              <tr>
                <th className="px-4 py-3">Month</th>
                <th className="px-4 py-3 text-right">OldInvoices</th>
                <th className="px-4 py-3 text-right">Stamp Duty</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {monthlyTimbre.length === 0 ? (
                <tr>
                  <td colSpan={3} className="px-4 py-8 text-center text-gray-500">No data for this year.</td>
                </tr>
              ) : (
                monthlyTimbre.map((row) => (
                  <tr key={row.month} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium">{monthNames[row.month - 1]}</td>
                    <td className="px-4 py-3 text-right">{row.oldinvoice_count}</td>
                    <td className="px-4 py-3 text-right font-medium">{formatTND(row.total_timbre)}</td>
                  </tr>
                ))
              )}
            </tbody>
            {monthlyTimbre.length > 0 && (
              <tfoot className="border-t bg-gray-50">
                <tr className="font-semibold">
                  <td className="px-4 py-3">Total</td>
                  <td className="px-4 py-3 text-right">{monthlyTimbre.reduce((s, r) => s + r.oldinvoice_count, 0)}</td>
                  <td className="px-4 py-3 text-right">{formatTND(yearlyTotal)}</td>
                </tr>
              </tfoot>
            )}
          </table>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
