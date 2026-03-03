import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatTND, formatNumber } from '@/utils/format';
import type { Customer, OldInvoice, PaginatedData, PageProps, Payment } from '@/types';

interface OldInvoiceWithPayments extends OldInvoice {
  payments: Payment[];
}

interface Props extends PageProps {
  customer: Customer;
  oldinvoices: PaginatedData<OldInvoiceWithPayments>;
  totals: { total_oldinvoiced: string | null; oldinvoice_count: number } | null;
  totalPaid: string;
  balance: string;
}

export default function CustomerStatement({ customer, oldinvoices, totals, totalPaid, balance }: Props) {
  return (
    <AuthenticatedLayout>
      <Head title={`Customer Statement — ${customer.name}`} />

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
            <p className="mt-1 text-sm text-gray-500">{customer.name} — {customer.identifier_value}</p>
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
            <p className="text-sm text-gray-500">Total OldInvoiced</p>
            <p className="mt-1 text-2xl font-bold text-gray-900">{formatTND(totals?.total_oldinvoiced)}</p>
            <p className="text-xs text-gray-400">{totals?.oldinvoice_count || 0} oldinvoices</p>
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

        {/* OldInvoice List */}
        <div className="overflow-hidden rounded-lg bg-white shadow">
          <table className="min-w-full text-sm">
            <thead className="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
              <tr>
                <th className="px-4 py-3">OldInvoice #</th>
                <th className="px-4 py-3">Date</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3 text-right">Total incl. tax</th>
                <th className="px-4 py-3 text-right">Paid</th>
                <th className="px-4 py-3 text-right">Remaining</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {oldinvoices.data.map((inv) => {
                const paid = inv.payments?.reduce((sum, p) => sum + parseFloat(p.amount), 0) || 0;
                const remaining = parseFloat(inv.total_ttc) - paid;
                return (
                  <tr key={inv.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <Link href={`/oldinvoices/${inv.id}`} className="font-medium text-indigo-600 hover:underline">
                        {inv.oldinvoice_number}
                      </Link>
                    </td>
                    <td className="px-4 py-3">{inv.oldinvoice_date}</td>
                    <td className="px-4 py-3 capitalize">{inv.status}</td>
                    <td className="px-4 py-3 text-right">{formatNumber(inv.total_ttc)}</td>
                    <td className="px-4 py-3 text-right text-green-600">{formatNumber(paid)}</td>
                    <td className={`px-4 py-3 text-right ${remaining > 0 ? 'text-red-600' : ''}`}>
                      {formatNumber(remaining)}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
