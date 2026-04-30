// Paste to: resources/js/Pages/Dashboard.tsx
//
// Drop-in replacement for the Dashboard page.
// - Same Inertia props (stats, recentInvoices, revenueChart, statusDistribution, topCustomers, lowStockProducts)
// - Adds: Card-based KPIs, recharts revenue line + status donut, reuses InvoiceStatusBadge
// - No backend / route changes required.
//
// Requires `recharts` (npm install recharts).

import { Head, Link } from '@inertiajs/react';
import {
  ResponsiveContainer,
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  PieChart,
  Pie,
  Cell,
  Legend,
} from 'recharts';
import {
  TrendingUp,
  Wallet,
  Users,
  Package,
  FileText,
  Clock,
  AlertTriangle,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { InvoiceStatusBadge } from '@/Components/ui/Badge';
import KpiCard from '@/Components/Dashboard/KpiCard';

interface Stats {
  monthly_revenue: number;
  outstanding_balance: number;
  total_customers: number;
  total_products: number;
  invoices_this_month: number;
  pending_invoices: number;
}

interface RecentInvoice {
  id: number;
  invoice_number: string;
  customer: { name: string };
  total_ttc: number;
  status: string;
  invoice_date: string;
}

interface Props {
  stats: Stats;
  recentInvoices: RecentInvoice[];
  revenueChart: Record<string, number>;
  statusDistribution: Record<string, number>;
  topCustomers: { id: number; name: string; invoices_sum_total_ttc: number }[];
  lowStockProducts: {
    id: number;
    name: string;
    code: string;
    current_stock: number;
    min_stock_alert: number;
  }[];
}

const STATUS_COLORS: Record<string, string> = {
  draft: '#9ca3af',
  pending_validation: '#f59e0b',
  validated: '#3b82f6',
  signed: '#6366f1',
  submitted: '#a855f7',
  accepted: '#10b981',
  rejected: '#ef4444',
  archived: '#6b7280',
};

function formatTND(amount: number | string | null | undefined): string {
  const n = typeof amount === 'string' ? parseFloat(amount) : amount ?? 0;
  return `${n.toFixed(3)} TND`;
}

function compactTND(amount: number): string {
  if (amount >= 1_000_000) return `${(amount / 1_000_000).toFixed(1)}M`;
  if (amount >= 1_000) return `${(amount / 1_000).toFixed(1)}k`;
  return amount.toFixed(0);
}

export default function DashboardPage({
  stats,
  recentInvoices,
  revenueChart,
  statusDistribution,
  topCustomers,
  lowStockProducts,
}: Props) {
  const revenueData = Object.entries(revenueChart).map(([month, amount]) => ({
    month,
    revenue: Number(amount),
  }));

  const statusData = Object.entries(statusDistribution)
    .filter(([, n]) => Number(n) > 0)
    .map(([status, count]) => ({
      name: status.replace(/_/g, ' '),
      value: Number(count),
      key: status,
    }));

  return (
    <AuthenticatedLayout>
      <Head title="Dashboard" />

      <div className="space-y-6 p-4 sm:p-6">
        {/* Header */}
        <div>
          <h1 className="text-2xl font-semibold text-gray-900">Dashboard</h1>
          <p className="text-sm text-gray-500">
            Overview of revenue, invoices, and inventory health.
          </p>
        </div>

        {/* KPI grid */}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          <KpiCard
            label="Monthly revenue"
            value={formatTND(stats.monthly_revenue)}
            icon={TrendingUp}
            accent="green"
          />
          <KpiCard
            label="Outstanding"
            value={formatTND(stats.outstanding_balance)}
            icon={Wallet}
            accent="amber"
          />
          <KpiCard
            label="Customers"
            value={String(stats.total_customers)}
            icon={Users}
            accent="blue"
          />
          <KpiCard
            label="Products"
            value={String(stats.total_products)}
            icon={Package}
            accent="violet"
          />
          <KpiCard
            label="Invoices this month"
            value={String(stats.invoices_this_month)}
            icon={FileText}
            accent="blue"
          />
          <KpiCard
            label="Pending invoices"
            value={String(stats.pending_invoices)}
            icon={Clock}
            accent="amber"
          />
        </div>

        {/* Charts row */}
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          {/* Revenue chart */}
          <div className="rounded-lg border border-gray-200 bg-white p-5 lg:col-span-2">
            <div className="mb-4 flex items-center justify-between">
              <div>
                <h2 className="text-base font-semibold text-gray-900">Revenue trend</h2>
                <p className="text-xs text-gray-500">Monthly TTC, TND</p>
              </div>
            </div>
            <div className="h-64">
              {revenueData.length === 0 ? (
                <div className="flex h-full items-center justify-center text-sm text-gray-500">
                  No revenue data yet.
                </div>
              ) : (
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={revenueData} margin={{ top: 5, right: 10, left: 0, bottom: 0 }}>
                    <CartesianGrid stroke="#f3f4f6" vertical={false} />
                    <XAxis dataKey="month" tick={{ fontSize: 12, fill: '#6b7280' }} tickLine={false} axisLine={false} />
                    <YAxis
                      tick={{ fontSize: 12, fill: '#6b7280' }}
                      tickLine={false}
                      axisLine={false}
                      tickFormatter={(v: number) => compactTND(v)}
                    />
                    <Tooltip
                      contentStyle={{ borderRadius: 8, border: '1px solid #e5e7eb', fontSize: 12 }}
                      formatter={(v: number) => formatTND(v)}
                    />
                    <Line
                      type="monotone"
                      dataKey="revenue"
                      stroke="#3b82f6"
                      strokeWidth={2.5}
                      dot={{ r: 3, fill: '#3b82f6' }}
                      activeDot={{ r: 5 }}
                    />
                  </LineChart>
                </ResponsiveContainer>
              )}
            </div>
          </div>

          {/* Status donut */}
          <div className="rounded-lg border border-gray-200 bg-white p-5">
            <h2 className="mb-4 text-base font-semibold text-gray-900">Status distribution</h2>
            <div className="h-64">
              {statusData.length === 0 ? (
                <div className="flex h-full items-center justify-center text-sm text-gray-500">
                  No invoices yet.
                </div>
              ) : (
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={statusData}
                      dataKey="value"
                      nameKey="name"
                      cx="50%"
                      cy="50%"
                      innerRadius={50}
                      outerRadius={80}
                      paddingAngle={2}
                    >
                      {statusData.map((entry) => (
                        <Cell key={entry.key} fill={STATUS_COLORS[entry.key] ?? '#9ca3af'} />
                      ))}
                    </Pie>
                    <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #e5e7eb', fontSize: 12 }} />
                    <Legend wrapperStyle={{ fontSize: 12 }} iconType="circle" />
                  </PieChart>
                </ResponsiveContainer>
              )}
            </div>
          </div>
        </div>

        {/* Lower row: recent invoices + side panels */}
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          {/* Recent invoices */}
          <div className="overflow-hidden rounded-lg border border-gray-200 bg-white lg:col-span-2">
            <div className="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-5 py-3">
              <h2 className="text-sm font-semibold text-gray-900">Recent invoices</h2>
              <Link href="/invoices" className="text-xs text-blue-600 hover:underline">
                View all
              </Link>
            </div>
            {recentInvoices.length === 0 ? (
              <div className="p-8 text-center text-sm text-gray-500">No invoices yet.</div>
            ) : (
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-xs uppercase tracking-wider text-gray-600">
                  <tr>
                    <th className="px-4 py-2 text-left">Invoice</th>
                    <th className="px-4 py-2 text-left">Customer</th>
                    <th className="px-4 py-2 text-left">Date</th>
                    <th className="px-4 py-2 text-right">Total</th>
                    <th className="px-4 py-2 text-left">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {recentInvoices.map((inv) => (
                    <tr key={inv.id} className="transition-colors hover:bg-gray-50">
                      <td className="px-4 py-2">
                        <Link
                          href={`/invoices/${inv.id}`}
                          className="font-medium text-blue-600 hover:underline"
                        >
                          {inv.invoice_number}
                        </Link>
                      </td>
                      <td className="px-4 py-2 text-gray-700">{inv.customer?.name ?? '—'}</td>
                      <td className="px-4 py-2 text-gray-500">
                        {new Date(inv.invoice_date).toLocaleDateString()}
                      </td>
                      <td className="px-4 py-2 text-right font-mono">{formatTND(inv.total_ttc)}</td>
                      <td className="px-4 py-2">
                        <InvoiceStatusBadge status={inv.status} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>

          {/* Top customers */}
          <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 bg-gray-50 px-5 py-3">
              <h2 className="text-sm font-semibold text-gray-900">Top customers</h2>
            </div>
            {topCustomers.length === 0 ? (
              <div className="p-6 text-center text-sm text-gray-500">No data.</div>
            ) : (
              <ul className="divide-y divide-gray-100">
                {topCustomers.map((c, idx) => (
                  <li key={c.id} className="flex items-center justify-between px-5 py-3">
                    <div className="flex items-center gap-3">
                      <div className="flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-xs font-semibold text-blue-700">
                        {idx + 1}
                      </div>
                      <Link
                        href={`/customers/${c.id}`}
                        className="text-sm text-gray-900 hover:underline"
                      >
                        {c.name}
                      </Link>
                    </div>
                    <span className="font-mono text-sm text-gray-700">
                      {formatTND(c.invoices_sum_total_ttc)}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>

        {/* Low stock alert */}
        {lowStockProducts.length > 0 && (
          <div className="overflow-hidden rounded-lg border border-amber-200 bg-amber-50">
            <div className="flex items-center gap-2 border-b border-amber-200 px-5 py-3">
              <AlertTriangle className="h-4 w-4 text-amber-700" />
              <h2 className="text-sm font-semibold text-amber-900">Low stock alert</h2>
            </div>
            <table className="w-full text-sm">
              <thead className="bg-amber-100/50 text-xs uppercase tracking-wider text-amber-900">
                <tr>
                  <th className="px-5 py-2 text-left">Product</th>
                  <th className="px-5 py-2 text-left">Code</th>
                  <th className="px-5 py-2 text-right">Current</th>
                  <th className="px-5 py-2 text-right">Min alert</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-amber-200/60">
                {lowStockProducts.map((p) => (
                  <tr key={p.id}>
                    <td className="px-5 py-2 text-gray-900">{p.name}</td>
                    <td className="px-5 py-2 font-mono text-xs text-gray-700">{p.code}</td>
                    <td className="px-5 py-2 text-right font-mono text-red-700">
                      {p.current_stock}
                    </td>
                    <td className="px-5 py-2 text-right font-mono text-gray-700">
                      {p.min_stock_alert}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
