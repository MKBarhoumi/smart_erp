import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/Components/ui/Pagination';
import { formatTND, formatDate } from '@/utils/format';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Payment, OldInvoice, PaginatedData, PageProps } from '@/types';

interface PaymentWithOldInvoice extends Payment {
  oldinvoice: Pick<OldInvoice, 'id' | 'oldinvoice_number' | 'total_ttc' | 'status'> & { customer: { id: string; name: string } };
  creator?: { name: string };
}

interface Props extends PageProps {
  payments: PaginatedData<PaymentWithOldInvoice>;
  filters: { search?: string; method?: string; date_from?: string; date_to?: string };
  totalCollected: string;
}

const methodStyles: Record<string, { bg: string; text: string; label: string }> = {
  cash: { bg: 'bg-emerald-100', text: 'text-emerald-700', label: 'Cash' },
  bank_transfer: { bg: 'bg-user-100', text: 'text-user-700', label: 'Bank Transfer' },
  cheque: { bg: 'bg-amber-100', text: 'text-amber-700', label: 'Cheque' },
  effect: { bg: 'bg-violet-100', text: 'text-violet-700', label: 'Bill of Exchange' },
};

export default function Index({ payments, filters, totalCollected }: Props) {
  return (
    <AuthenticatedLayout>
      <Head title="Payments" />

      <div className="space-y-6">
        {/* Page Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-lg shadow-emerald-500/25">
              <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
            </div>
            <div>
              <h1 className="text-3xl font-bold tracking-tight text-gray-900">Payments</h1>
              <p className="text-gray-500">Track received and recorded payments</p>
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl p-4 border border-emerald-200/50">
            <p className="text-sm text-emerald-600">Total Collected</p>
            <p className="text-2xl font-bold text-emerald-700 mt-1">{formatTND(totalCollected)}</p>
          </div>
          <div className="bg-white rounded-xl p-4 border border-gray-100 shadow-soft">
            <p className="text-sm text-gray-500">Total Payments</p>
            <p className="text-2xl font-bold text-gray-900 mt-1">{payments.total}</p>
          </div>
          <div className="bg-gradient-to-r from-user-50 to-indigo-50 rounded-xl p-4 border border-user-200/50">
            <p className="text-sm text-user-600">Bank Transfers</p>
            <p className="text-2xl font-bold text-user-700 mt-1">{payments.data.filter(p => p.method === 'bank_transfer').length}</p>
          </div>
          <div className="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-200/50">
            <p className="text-sm text-amber-600">Cheques</p>
            <p className="text-2xl font-bold text-amber-700 mt-1">{payments.data.filter(p => p.method === 'cheque').length}</p>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-5">
          <div className="flex flex-wrap items-end gap-4">
            <div className="flex-1 min-w-[200px]">
              <label className="block text-xs font-medium text-gray-600 mb-1">Search</label>
              <div className="relative">
                <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                <input type="text" placeholder="Invoice #, customer, reference..." defaultValue={filters.search} onChange={(e) => { const value = e.target.value; setTimeout(() => router.get('/payments', { ...filters, search: value }, { preserveState: true }), 300); }} className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm" />
              </div>
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Method</label>
              <select defaultValue={filters.method || ''} onChange={(e) => router.get('/payments', { ...filters, method: e.target.value }, { preserveState: true })} className="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm">
                <option value="">All Methods</option>
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
                <option value="effect">Bill of Exchange</option>
              </select>
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">From</label>
              <input type="date" defaultValue={filters.date_from} onChange={(e) => router.get('/payments', { ...filters, date_from: e.target.value }, { preserveState: true })} className="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm" />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">To</label>
              <input type="date" defaultValue={filters.date_to} onChange={(e) => router.get('/payments', { ...filters, date_to: e.target.value }, { preserveState: true })} className="px-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm" />
            </div>
          </div>
        </div>

        {/* Table */}
        <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-100">
              <thead>
                <tr className="bg-gradient-to-r from-gray-50 to-slate-50">
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Invoice</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Method</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reference</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">By</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {payments.data.length === 0 ? (
                  <tr><td colSpan={8} className="px-6 py-12 text-center">
                    <div className="mx-auto w-16 h-16 rounded-2xl bg-emerald-100 flex items-center justify-center mb-4"><svg className="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg></div>
                    <h3 className="text-lg font-semibold text-gray-900">No payments found</h3>
                    <p className="mt-1 text-sm text-gray-500">No payments match your current filters.</p>
                  </td></tr>
                ) : (
                  payments.data.map((payment, idx) => {
                    const style = methodStyles[payment.method] || methodStyles.cash;
                    return (
                      <tr key={payment.id} className={`transition-colors hover:bg-user-50/30 ${idx % 2 === 0 ? '' : 'bg-gray-50/30'}`}>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{formatDate(payment.payment_date)}</td>
                        <td className="px-6 py-4">
                          <Link href={`/oldinvoices/${payment.oldinvoice.id}`} className="font-semibold text-user-600 hover:text-user-700 transition-colors">{payment.oldinvoice.oldinvoice_number}</Link>
                        </td>
                        <td className="px-6 py-4 text-gray-700">{payment.oldinvoice.customer?.name || '—'}</td>
                        <td className="px-6 py-4">
                          <span className={`inline-flex px-2.5 py-1 rounded-lg text-xs font-medium ${style.bg} ${style.text}`}>{style.label}</span>
                        </td>
                        <td className="px-6 py-4 font-mono text-xs text-gray-500">{payment.reference || '—'}</td>
                        <td className="px-6 py-4 text-right"><span className="font-bold text-gray-900">{formatTND(payment.amount)}</span></td>
                        <td className="px-6 py-4 text-sm text-gray-500">{payment.creator?.name || '—'}</td>
                        <td className="px-6 py-4 text-right">
                          <button onClick={() => { if (confirm('Delete this payment?')) router.delete(`/payments/${payment.id}`); }} className="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all" title="Delete">
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                          </button>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
          {payments.last_page > 1 && (<div className="border-t border-gray-100 px-4 py-3"><Pagination links={payments.links} /></div>)}
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
