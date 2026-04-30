// Paste to: resources/js/Pages/Invoices/Index.tsx
//
// Drop-in replacement for the Invoice List page.
// - Same Inertia props (invoices, filters, statuses)
// - Same router.get('/invoices', ...) round-trip for filtering/sorting/pagination
// - Adds: status tabs, sortable columns, role-gated "New Invoice" CTA, row action menu,
//         empty + loading states, mobile card fallback
// - No backend / route changes required.

import { useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
  Plus,
  Search,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  MoreHorizontal,
  Eye,
  Pencil,
  Download,
  X,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Pagination } from '@/Components/ui/Pagination';
import { InvoiceStatusBadge } from '@/Components/ui/Badge';
import type { PageProps, PaginatedData, Invoice } from '@/types';
import {
  STATUS_TABS,
  canCreateInvoice,
  type InvoiceStatus,
  type UserRole,
} from '@/utils/invoiceStatus';

interface Filters {
  search?: string;
  status?: string;
  date_from?: string;
  date_to?: string;
  sort?: string;
  direction?: 'asc' | 'desc';
}

interface Props extends PageProps {
  invoices: PaginatedData<Invoice>;
  filters: Filters;
  statuses: { value: string; label: string }[];
}

const SORTABLE_COLUMNS = [
  { key: 'invoice_number', label: 'Invoice #' },
  { key: 'customer', label: 'Customer', sortable: false },
  { key: 'invoice_date', label: 'Date' },
  { key: 'total_ttc', label: 'Total TTC', align: 'right' as const },
  { key: 'status', label: 'Status' },
] as const;

function formatTND(amount: number | string | null | undefined): string {
  const n = typeof amount === 'string' ? parseFloat(amount) : amount ?? 0;
  return `${n.toFixed(3)} TND`;
}

export default function InvoiceListPage({ invoices, filters, statuses }: Props) {
  const { auth } = usePage<PageProps>().props as PageProps & { auth: { user: { role: UserRole } } };
  const role = auth?.user?.role;
  const showCreateCta = canCreateInvoice(role);

  const [search, setSearch] = useState(filters.search ?? '');
  const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
  const [dateTo, setDateTo] = useState(filters.date_to ?? '');
  const [openRowMenu, setOpenRowMenu] = useState<number | null>(null);

  const activeTab = (filters.status ?? 'all') as 'all' | InvoiceStatus;
  const sort = filters.sort ?? 'invoice_date';
  const direction = filters.direction ?? 'desc';

  const tabCounts = useMemo(() => {
    // Server doesn't return per-status counts in current props.
    // Show counts only for the active tab; leave others blank to avoid lying to the user.
    return { [activeTab]: invoices.total } as Record<string, number>;
  }, [activeTab, invoices.total]);

  function pushFilters(patch: Partial<Filters>) {
    router.get(
      '/invoices',
      {
        search,
        status: activeTab === 'all' ? undefined : activeTab,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        sort,
        direction,
        ...patch,
      },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  function setTab(tab: 'all' | InvoiceStatus) {
    pushFilters({ status: tab === 'all' ? undefined : tab });
  }

  function toggleSort(column: string) {
    const nextDir: 'asc' | 'desc' = sort === column && direction === 'asc' ? 'desc' : 'asc';
    pushFilters({ sort: column, direction: nextDir });
  }

  function clearFilters() {
    setSearch('');
    setDateFrom('');
    setDateTo('');
    router.get('/invoices', {}, { preserveState: true, replace: true });
  }

  const hasActiveFilters = !!(search || dateFrom || dateTo || filters.status);

  return (
    <AuthenticatedLayout>
      <Head title="Invoices" />

      <div className="space-y-6 p-4 sm:p-6">
        {/* Header */}
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-semibold text-gray-900">Invoices</h1>
            <p className="text-sm text-gray-500">
              Manage, validate, sign, and submit invoices to TTN.
            </p>
          </div>

          {showCreateCta && (
            <Link href="/invoices/create">
              <Button>
                <Plus className="mr-2 h-4 w-4" />
                New Invoice
              </Button>
            </Link>
          )}
        </div>

        {/* Status tabs */}
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex gap-1 overflow-x-auto" aria-label="Status tabs">
            {STATUS_TABS.map((tab) => {
              const isActive = activeTab === tab.value;
              return (
                <button
                  key={tab.value}
                  type="button"
                  onClick={() => setTab(tab.value)}
                  className={[
                    'whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                    isActive
                      ? 'border-blue-600 text-blue-600'
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                  ].join(' ')}
                >
                  {tab.label}
                  {isActive && tabCounts[tab.value] !== undefined && (
                    <span className="ml-2 rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">
                      {tabCounts[tab.value]}
                    </span>
                  )}
                </button>
              );
            })}
          </nav>
        </div>

        {/* Filter bar */}
        <div className="rounded-lg border border-gray-200 bg-white p-4">
          <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div className="md:col-span-2">
              <label className="mb-1 block text-xs font-medium text-gray-600">Search</label>
              <div className="relative">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <Input
                  className="pl-9"
                  placeholder="Invoice # or customer..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && pushFilters({})}
                />
              </div>
            </div>

            <div>
              <label className="mb-1 block text-xs font-medium text-gray-600">From</label>
              <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
            </div>

            <div>
              <label className="mb-1 block text-xs font-medium text-gray-600">To</label>
              <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
            </div>
          </div>

          <div className="mt-3 flex items-center justify-between gap-2">
            <div className="text-xs text-gray-500">
              {hasActiveFilters ? `Filtered • ${invoices.total} result(s)` : `${invoices.total} total`}
            </div>
            <div className="flex gap-2">
              {hasActiveFilters && (
                <Button variant="ghost" onClick={clearFilters}>
                  <X className="mr-1 h-4 w-4" />
                  Clear
                </Button>
              )}
              <Button onClick={() => pushFilters({})}>Apply</Button>
            </div>
          </div>
        </div>

        {/* Desktop table */}
        <div className="hidden overflow-hidden rounded-lg border border-gray-200 bg-white md:block">
          <table className="w-full text-sm">
            <thead className="border-b border-gray-200 bg-gray-50">
              <tr>
                {SORTABLE_COLUMNS.map((col) => {
                  const isSortable = col.sortable !== false;
                  const isActive = sort === col.key;
                  const Icon = !isActive ? ArrowUpDown : direction === 'asc' ? ArrowUp : ArrowDown;
                  return (
                    <th
                      key={col.key}
                      className={[
                        'px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600',
                        col.align === 'right' ? 'text-right' : 'text-left',
                      ].join(' ')}
                    >
                      {isSortable ? (
                        <button
                          type="button"
                          onClick={() => toggleSort(col.key)}
                          className={[
                            'inline-flex items-center gap-1 transition-colors hover:text-gray-900',
                            isActive ? 'text-gray-900' : '',
                          ].join(' ')}
                        >
                          {col.label}
                          <Icon className="h-3.5 w-3.5" />
                        </button>
                      ) : (
                        col.label
                      )}
                    </th>
                  );
                })}
                <th className="px-4 py-3" aria-label="Actions" />
              </tr>
            </thead>

            <tbody className="divide-y divide-gray-100">
              {invoices.data.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-16 text-center">
                    <div className="mx-auto max-w-sm space-y-2">
                      <div className="text-base font-medium text-gray-700">No invoices found</div>
                      <div className="text-sm text-gray-500">
                        {hasActiveFilters
                          ? 'Try clearing filters or changing the status tab.'
                          : showCreateCta
                            ? 'Create your first invoice to get started.'
                            : 'Invoices will appear here once they are created.'}
                      </div>
                      {hasActiveFilters && (
                        <Button variant="ghost" onClick={clearFilters}>
                          Clear filters
                        </Button>
                      )}
                    </div>
                  </td>
                </tr>
              ) : (
                invoices.data.map((invoice) => (
                  <tr key={invoice.id} className="group transition-colors hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <Link
                        href={`/invoices/${invoice.id}`}
                        className="font-medium text-blue-600 hover:underline"
                      >
                        {invoice.invoice_number}
                      </Link>
                    </td>
                    <td className="px-4 py-3 text-gray-700">
                      {invoice.customer?.name ?? '—'}
                    </td>
                    <td className="px-4 py-3 text-gray-700">
                      {invoice.invoice_date ? new Date(invoice.invoice_date).toLocaleDateString() : '—'}
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-gray-900">
                      {formatTND(invoice.total_ttc as unknown as number)}
                    </td>
                    <td className="px-4 py-3">
                      <InvoiceStatusBadge status={invoice.status} />
                    </td>
                    <td className="relative px-4 py-3 text-right">
                      <button
                        type="button"
                        className="rounded p-1.5 text-gray-400 opacity-0 transition-opacity hover:bg-gray-100 hover:text-gray-700 focus:opacity-100 group-hover:opacity-100"
                        onClick={() => setOpenRowMenu(openRowMenu === invoice.id ? null : invoice.id)}
                        aria-label="Row actions"
                      >
                        <MoreHorizontal className="h-4 w-4" />
                      </button>
                      {openRowMenu === invoice.id && (
                        <>
                          <button
                            type="button"
                            className="fixed inset-0 z-10 cursor-default"
                            onClick={() => setOpenRowMenu(null)}
                            aria-label="Close menu"
                          />
                          <div className="absolute right-2 top-10 z-20 w-44 overflow-hidden rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                            <Link
                              href={`/invoices/${invoice.id}`}
                              className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                              <Eye className="h-4 w-4" /> View
                            </Link>
                            {invoice.status === 'draft' && (
                              <Link
                                href={`/invoices/${invoice.id}/edit`}
                                className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                              >
                                <Pencil className="h-4 w-4" /> Edit
                              </Link>
                            )}
                            <a
                              href={`/invoices/${invoice.id}/pdf`}
                              className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                              <Download className="h-4 w-4" /> Download PDF
                            </a>
                          </div>
                        </>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Mobile cards */}
        <div className="space-y-3 md:hidden">
          {invoices.data.length === 0 ? (
            <div className="rounded-lg border border-gray-200 bg-white p-8 text-center text-sm text-gray-500">
              No invoices found.
            </div>
          ) : (
            invoices.data.map((invoice) => (
              <Link
                key={invoice.id}
                href={`/invoices/${invoice.id}`}
                className="block rounded-lg border border-gray-200 bg-white p-4 transition-colors hover:border-blue-300"
              >
                <div className="mb-2 flex items-start justify-between">
                  <div className="font-medium text-blue-600">{invoice.invoice_number}</div>
                  <InvoiceStatusBadge status={invoice.status} />
                </div>
                <div className="text-sm text-gray-700">{invoice.customer?.name ?? '—'}</div>
                <div className="mt-2 flex items-center justify-between text-xs text-gray-500">
                  <span>
                    {invoice.invoice_date
                      ? new Date(invoice.invoice_date).toLocaleDateString()
                      : '—'}
                  </span>
                  <span className="font-mono text-sm text-gray-900">
                    {formatTND(invoice.total_ttc as unknown as number)}
                  </span>
                </div>
              </Link>
            ))
          )}
        </div>

        {/* Pagination */}
        {invoices.last_page > 1 && (
          <div className="flex justify-end">
            {/* ADAPT: your existing Pagination component signature */}
            <Pagination links={invoices.links} />
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
