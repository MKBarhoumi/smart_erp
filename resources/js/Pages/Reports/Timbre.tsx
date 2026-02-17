import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { PageProps } from '@/types';

interface Props extends PageProps {
  year: number;
  monthlyTimbre: Array<{ month: number; total_timbre: string; invoice_count: number }>;
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
            <Link href="/reports" className="text-sm text-indigo-600 hover:underline">← Reports</Link>
            <h1 className="mt-2 text-2xl font-bold text-gray-900">Stamp Duty Report</h1>
            <p className="mt-1 text-sm text-gray-500">Monthly summary of stamp duty collected.</p>
          </div>
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

        {/* Total Card */}
        <div className="rounded-lg bg-white p-6 shadow">
          <p className="text-sm text-gray-500">Total stamp duty {year}</p>
          <p className="mt-1 text-3xl font-bold text-gray-900">{parseFloat(yearlyTotal).toFixed(3)} TND</p>
        </div>

        {/* Monthly Breakdown */}
        <div className="overflow-hidden rounded-lg bg-white shadow">
          <table className="min-w-full text-sm">
            <thead className="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
              <tr>
                <th className="px-4 py-3">Month</th>
                <th className="px-4 py-3 text-right">Invoices</th>
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
                    <td className="px-4 py-3 text-right">{row.invoice_count}</td>
                    <td className="px-4 py-3 text-right font-medium">{parseFloat(row.total_timbre).toFixed(3)} TND</td>
                  </tr>
                ))
              )}
            </tbody>
            {monthlyTimbre.length > 0 && (
              <tfoot className="border-t bg-gray-50">
                <tr className="font-semibold">
                  <td className="px-4 py-3">Total</td>
                  <td className="px-4 py-3 text-right">{monthlyTimbre.reduce((s, r) => s + r.invoice_count, 0)}</td>
                  <td className="px-4 py-3 text-right">{parseFloat(yearlyTotal).toFixed(3)} TND</td>
                </tr>
              </tfoot>
            )}
          </table>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
