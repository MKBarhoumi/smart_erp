import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Pagination } from '@/Components/ui/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Product, PageProps, PaginatedData } from '@/types';

interface StockMovement {
  id: string;
  product_id: string;
  product: Pick<Product, 'id' | 'code' | 'name'>;
  type: 'in' | 'out' | 'adjustment';
  quantity: string;
  stock_before: string;
  stock_after: string;
  reason: string | null;
  oldinvoice_id: string | null;
  performed_by: string | null;
  performer?: { name: string } | null;
  created_at: string;
}

interface Props extends PageProps {
  movements: PaginatedData<StockMovement>;
  filters: { search?: string; type?: string; product_id?: string; date_from?: string; date_to?: string };
  products: Array<{ id: string; code: string; name: string }>;
}

const typeLabels: Record<string, { label: string; variant: 'success' | 'danger' | 'warning' }> = {
  in: { label: 'In', variant: 'success' },
  out: { label: 'Out', variant: 'danger' },
  adjustment: { label: 'Adjustment', variant: 'warning' },
};

export default function History({ movements, filters, products }: Props) {
  return (
    <AuthenticatedLayout>
      <Head title="Stock Movement History" />

      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Movement History</h1>
            <p className="mt-1 text-sm text-gray-500">Complete history of stock entries, exits, and adjustments.</p>
          </div>
          <Link href="/inventory">
            <Button variant="secondary">Back to Inventory</Button>
          </Link>
        </div>

        {/* Filters */}
        <div className="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow">
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">Product</label>
            <select
              defaultValue={filters.product_id || ''}
              onChange={(e) => router.get('/inventory/history', { ...filters, product_id: e.target.value }, { preserveState: true })}
              className="rounded-lg border-gray-300 text-sm"
            >
              <option value="">All products</option>
              {products.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.code} — {p.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">Type</label>
            <select
              defaultValue={filters.type || ''}
              onChange={(e) => router.get('/inventory/history', { ...filters, type: e.target.value }, { preserveState: true })}
              className="rounded-lg border-gray-300 text-sm"
            >
              <option value="">All</option>
              <option value="in">In</option>
              <option value="out">Out</option>
              <option value="adjustment">Adjustment</option>
            </select>
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">From</label>
            <input
              type="date"
              defaultValue={filters.date_from}
              onChange={(e) => router.get('/inventory/history', { ...filters, date_from: e.target.value }, { preserveState: true })}
              className="rounded-lg border-gray-300 text-sm"
            />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">To</label>
            <input
              type="date"
              defaultValue={filters.date_to}
              onChange={(e) => router.get('/inventory/history', { ...filters, date_to: e.target.value }, { preserveState: true })}
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
                <th className="px-4 py-3">Product</th>
                <th className="px-4 py-3">Type</th>
                <th className="px-4 py-3 text-right">Quantity</th>
                <th className="px-4 py-3 text-right">Stock Before</th>
                <th className="px-4 py-3 text-right">Stock After</th>
                <th className="px-4 py-3">Reason</th>
                <th className="px-4 py-3">By</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {movements.data.length === 0 ? (
                <tr>
                  <td colSpan={8} className="px-4 py-8 text-center text-gray-500">
                    No movements found.
                  </td>
                </tr>
              ) : (
                movements.data.map((m) => {
                  const typeInfo = typeLabels[m.type] || { label: m.type, variant: 'default' as const };
                  return (
                    <tr key={m.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 whitespace-nowrap">
                        {new Date(m.created_at).toLocaleString('en-US', {
                          day: '2-digit',
                          month: '2-digit',
                          year: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit',
                        })}
                      </td>
                      <td className="px-4 py-3">
                        <span className="font-mono text-xs text-gray-500">{m.product.code}</span>
                        <span className="ml-2">{m.product.name}</span>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={typeInfo.variant}>{typeInfo.label}</Badge>
                      </td>
                      <td className="px-4 py-3 text-right font-medium">{parseFloat(m.quantity).toFixed(3)}</td>
                      <td className="px-4 py-3 text-right text-gray-500">{parseFloat(m.stock_before).toFixed(3)}</td>
                      <td className="px-4 py-3 text-right text-gray-500">{parseFloat(m.stock_after).toFixed(3)}</td>
                      <td className="px-4 py-3 text-gray-600">{m.reason || '—'}</td>
                      <td className="px-4 py-3 text-gray-500">{m.performer?.name || '—'}</td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>

          {movements.last_page > 1 && (
            <div className="border-t px-4 py-3">
              <Pagination links={movements.links} />
            </div>
          )}
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
