import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatTND, formatNumber } from '@/utils/format';
import type { Customer, PageProps } from '@/types';

interface StatementInvoice {
  id: string;
  document_identifier: string | null;
  invoice_date: string | null;
  status: string;
  total_ttc: string;
  paid_amount: string;
  remaining_balance: string;
}

interface Props extends PageProps {
  customer: Customer;
  invoices: StatementInvoice[];
  totals: { total_invoiced: string | null; invoice_count: number } | null;
  totalPaid: string;
  balance: string;
}

export default function CustomerStatement({ customer, invoices, totals, totalPaid, balance }: Props) {
  return (
    <AuthenticatedLayout>
      <Head title={`Customer Statement - ${customer.name}`} />

      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <Link href="/reports/customer-statement" className="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Customer Selection
            </Link>
            <h1 className="mt-2 text-2xl font-bold text-gray-900">Account Statement</h1>
            <p className="mt-1 text-sm text-gray-500">{customer.name} - {customer.identifier_value}</p>
          </div>
          <a
            href={`/reports/customer-statement/${customer.id}/pdf`}
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

        {/* Summary Cards */}
        <div className="grid gap-4 sm:grid-cols-3">
          <div className="rounded-lg bg-white p-5 shadow">
            <p className="text-sm text-gray-500">Total Invoiced</p>
            <p className="mt-1 text-2xl font-bold text-gray-900">{formatTND(totals?.total_invoiced)}</p>
            <p className="text-xs text-gray-400">{totals?.invoice_count || 0} invoices</p>
          </div>
          <div className="rounded-lg bg-white p-5 shadow">
            <p className="text-sm text-gray-500">Total Paid</p>
            <p className="mt-1 text-2xl font-bold text-green-600">{formatTND(totalPaid)}</p>
          </div>
          <div className="rounded-lg bg-white p-5 shadow">
            <p className="text-sm text-gray-500">Remaining Balance</p>
            <p className={`mt-1 text-2xl font-bold ${parseFloat(balance) > 0 ? 'text-red-600' : 'text-gray-900'}`}>
              {formatTND(balance)}
            </p>
          </div>
        </div>

        {/* Invoice List */}
        <div className="overflow-hidden rounded-lg bg-white shadow">
          <table className="min-w-full text-sm">
            <thead className="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
              <tr>
                <th className="px-4 py-3">Invoice #</th>
                <th className="px-4 py-3">Date</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3 text-right">Total incl. tax</th>
                <th className="px-4 py-3 text-right">Paid</th>
                <th className="px-4 py-3 text-right">Remaining</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {invoices.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-gray-500">No transactions found for this customer.</td>
                </tr>
              ) : (
                invoices.map((inv) => {
                  const remaining = parseFloat(inv.remaining_balance || '0');

                  return (
                    <tr key={inv.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3">
                        <Link href={`/invoices/${inv.id}`} className="font-medium text-indigo-600 hover:underline">
                          {inv.document_identifier || '-'}
                        </Link>
                      </td>
                      <td className="px-4 py-3">{inv.invoice_date || '-'}</td>
                      <td className="px-4 py-3 capitalize">{inv.status}</td>
                      <td className="px-4 py-3 text-right">{formatNumber(inv.total_ttc)}</td>
                      <td className="px-4 py-3 text-right text-green-600">{formatNumber(inv.paid_amount)}</td>
                      <td className={`px-4 py-3 text-right ${remaining > 0 ? 'text-red-600' : ''}`}>
                        {formatNumber(inv.remaining_balance)}
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
