import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
        <div>
          <Link href="/reports" className="text-sm text-indigo-600 hover:underline">← Reports</Link>
          <h1 className="mt-2 text-2xl font-bold text-gray-900">Account Statement</h1>
          <p className="mt-1 text-sm text-gray-500">{customer.name} — {customer.identifier_value}</p>
        </div>

        {/* Summary Cards */}
        <div className="grid gap-4 sm:grid-cols-3">
          <div className="rounded-lg bg-white p-5 shadow">
            <p className="text-sm text-gray-500">Total OldInvoiced</p>
            <p className="mt-1 text-2xl font-bold text-gray-900">{parseFloat(totals?.total_oldinvoiced || '0').toFixed(3)} TND</p>
            <p className="text-xs text-gray-400">{totals?.oldinvoice_count || 0} oldinvoices</p>
          </div>
          <div className="rounded-lg bg-white p-5 shadow">
            <p className="text-sm text-gray-500">Total Paid</p>
            <p className="mt-1 text-2xl font-bold text-green-600">{parseFloat(totalPaid).toFixed(3)} TND</p>
          </div>
          <div className="rounded-lg bg-white p-5 shadow">
            <p className="text-sm text-gray-500">Remaining Balance</p>
            <p className={`mt-1 text-2xl font-bold ${parseFloat(balance) > 0 ? 'text-red-600' : 'text-gray-900'}`}>
              {parseFloat(balance).toFixed(3)} TND
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
                    <td className="px-4 py-3 text-right">{parseFloat(inv.total_ttc).toFixed(3)}</td>
                    <td className="px-4 py-3 text-right text-green-600">{paid.toFixed(3)}</td>
                    <td className={`px-4 py-3 text-right ${remaining > 0 ? 'text-red-600' : ''}`}>
                      {remaining.toFixed(3)}
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
