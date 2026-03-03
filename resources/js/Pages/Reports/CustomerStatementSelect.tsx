import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';
import type { PageProps } from '@/types';

interface Customer {
  id: string;
  name: string;
  identifier_value: string;
}

interface Props extends PageProps {
  customers: Customer[];
  search: string;
}

export default function CustomerStatementSelect({ customers, search: initialSearch }: Props) {
  const [search, setSearch] = useState(initialSearch);
  const [loading, setLoading] = useState(false);

  const handleSearch = (value: string) => {
    setSearch(value);
    setLoading(true);
    router.get('/reports/customer-statement', { search: value }, {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => setLoading(false),
    });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Customer Statement" />

      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <Link href="/reports" className="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
              <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Reports
            </Link>
            <h1 className="mt-2 text-2xl font-bold text-gray-900">Customer Statement</h1>
            <p className="mt-1 text-sm text-gray-500">Select a customer to view their account statement</p>
          </div>
        </div>

        {/* Search */}
        <div className="max-w-md">
          <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
            Search Customer
          </label>
          <div className="relative">
            <input
              type="text"
              id="search"
              value={search}
              onChange={(e) => handleSearch(e.target.value)}
              placeholder="Search by name or tax ID..."
              className="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            {loading && (
              <div className="absolute right-3 top-1/2 -translate-y-1/2">
                <svg className="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
              </div>
            )}
            {!loading && search && (
              <button
                onClick={() => handleSearch('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            )}
          </div>
        </div>

        {/* Customer List */}
        <div className="overflow-hidden rounded-lg bg-white shadow">
          {customers.length === 0 ? (
            <div className="p-8 text-center text-gray-500">
              {search ? 'No customers found matching your search.' : 'No customers available.'}
            </div>
          ) : (
            <ul className="divide-y divide-gray-100">
              {customers.map((customer) => (
                <li key={customer.id}>
                  <Link
                    href={`/reports/customer-statement/${customer.id}`}
                    className="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors"
                  >
                    <div>
                      <p className="font-medium text-gray-900">{customer.name}</p>
                      <p className="text-sm text-gray-500">{customer.identifier_value}</p>
                    </div>
                    <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                  </Link>
                </li>
              ))}
            </ul>
          )}
        </div>

        {customers.length >= 50 && (
          <p className="text-sm text-gray-500 text-center">
            Showing first 50 results. Use search to find specific customers.
          </p>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
