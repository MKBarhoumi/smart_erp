import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/Components/ui/Badge';
import { Pagination } from '@/Components/ui/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Payment, OldInvoice, PaginatedData, PageProps } from '@/types';

interface PaymentWithOldInvoice extends Payment {
  oldinvoice: Pick<OldInvoice, 'id' | 'oldinvoice_number' | 'total_ttc' | 'status'> & {
    customer: { id: string; name: string };
  };
  creator?: { name: string };
}

interface Props extends PageProps {
  payments: PaginatedData<PaymentWithOldInvoice>;
  filters: { search?: string; method?: string; date_from?: string; date_to?: string };
  totalCollected: string;
}

const methodLabels: Record<string, string> = {
  cash: 'Cash',
  bank_transfer: 'Bank Transfer',
  cheque: 'Cheque',
  effect: 'Bill of Exchange',
};

export default function Index({ payments, filters, totalCollected }: Props) {
  return (
    <AuthenticatedLayout>
      <Head title="Payments" />

      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Payments</h1>
            <p className="mt-1 text-sm text-gray-500">
              Total collected: <span className="font-semibold">{parseFloat(totalCollected || '0').toFixed(3)} TND</span>
            </p>
          </div>
        </div>

        {/* Filters */}
        <div className="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow">
          <div className="flex-1">
            <label className="mb-1 block text-xs font-medium text-gray-600">Search</label>
            <input
              type="text"
              placeholder="OldInvoice #, customer, reference..."
              defaultValue={filters.search}
              onChange={(e) => {
                const value = e.target.value;
                setTimeout(() => router.get('/payments', { ...filters, search: value }, { preserveState: true }), 300);
              }}
              className="w-full rounded-lg border-gray-300 text-sm"
            />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">Method</label>
            <select
              defaultValue={filters.method || ''}
              onChange={(e) => router.get('/payments', { ...filters, method: e.target.value }, { preserveState: true })}
              className="rounded-lg border-gray-300 text-sm"
            >
              <option value="">All</option>
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="cheque">Cheque</option>
              <option value="effect">Bill of Exchange</option>
            </select>
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">From</label>
            <input
              type="date"
              defaultValue={filters.date_from}
              onChange={(e) => router.get('/payments', { ...filters, date_from: e.target.value }, { preserveState: true })}
              className="rounded-lg border-gray-300 text-sm"
            />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">To</label>
            <input
              type="date"
              defaultValue={filters.date_to}
              onChange={(e) => router.get('/payments', { ...filters, date_to: e.target.value }, { preserveState: true })}
              className="rounded-lg border-gray-300 text-sm"
            />
          </div>
        </div>

        {/* Table */}
        <div className="overflow-hidden rounded-lg bg-white shadow">
          <table className="min-w-full text-sm">
            <thead className="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
              <tr>
                <th className="px-4 py-3">Date</th>
                <th className="px-4 py-3">OldInvoice</th>
                <th className="px-4 py-3">Customer</th>
                <th className="px-4 py-3">Method</th>
                <th className="px-4 py-3">Reference</th>
                <th className="px-4 py-3 text-right">Amount</th>
                <th className="px-4 py-3">By</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {payments.data.length === 0 ? (
                <tr>
                  <td colSpan={8} className="px-4 py-8 text-center text-gray-500">
                    No payments found.
                  </td>
                </tr>
              ) : (
                payments.data.map((payment) => (
                  <tr key={payment.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 whitespace-nowrap">{payment.payment_date}</td>
                    <td className="px-4 py-3">
                      <Link href={`/oldinvoices/${payment.oldinvoice.id}`} className="font-medium text-indigo-600 hover:underline">
                        {payment.oldinvoice.oldinvoice_number}
                      </Link>
                    </td>
                    <td className="px-4 py-3">{payment.oldinvoice.customer?.name || '—'}</td>
                    <td className="px-4 py-3">
                      <Badge variant="info">{methodLabels[payment.method] || payment.method}</Badge>
                    </td>
                    <td className="px-4 py-3 font-mono text-xs">{payment.reference || '—'}</td>
                    <td className="px-4 py-3 text-right font-medium">{parseFloat(payment.amount).toFixed(3)} TND</td>
                    <td className="px-4 py-3 text-gray-500">{payment.creator?.name || '—'}</td>
                    <td className="px-4 py-3 text-right">
                      <button
                        onClick={() => {
                          if (confirm('Delete this payment?')) {
                            router.delete(`/payments/${payment.id}`);
                          }
                        }}
                        className="text-xs text-red-600 hover:underline"
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>

          {payments.last_page > 1 && (
            <div className="border-t px-4 py-3">
              <Pagination links={payments.links} />
            </div>
          )}
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
