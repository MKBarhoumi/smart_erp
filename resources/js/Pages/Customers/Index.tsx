import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Pagination } from '@/Components/ui/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { usePermissions } from '@/hooks/usePermissions';
import type { Customer, PaginatedData } from '@/types';

interface Props {
    customers: PaginatedData<Customer>;
    filters: { search?: string; identifier_type?: string; city?: string };
}

export default function CustomersIndex({ customers, filters }: Props) {
    const { canCreate, canEdit } = usePermissions();
    
    const [search, setSearch] = useState(filters.search ?? '');
    const [identifierType, setIdentifierType] = useState(filters.identifier_type ?? '');
    const [city, setCity] = useState(filters.city ?? '');

    const applyFilters = () => {
        router.get('/customers', { 
            search: search || undefined, 
            identifier_type: identifierType || undefined,
            city: city || undefined 
        }, { preserveState: true, replace: true });
    };

    const clearFilters = () => {
        setSearch('');
        setIdentifierType('');
        setCity('');
        router.get('/customers', {}, { preserveState: true, replace: true });
    };

    const hasActiveFilters = search || identifierType || city;

    return (
        <AuthenticatedLayout>
            <Head title="Customers" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900">Customers</h1>
                        <p className="mt-1 text-gray-500">Manage your customer relationships and contacts</p>
                    </div>
                    {canCreate('customers') && (
                        <Link href="/customers/create">
                            <Button icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>}>
                                Add Customer
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Search & Filters */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-5">
                    <div className="flex flex-wrap items-end gap-4">
                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Search</label>
                            <div className="relative">
                                <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                                <input
                                    type="text"
                                    placeholder="Search by name, identifier, email, phone..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                    className="w-full pl-12 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm"
                                />
                            </div>
                        </div>
                        <div className="min-w-[150px]">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Identifier Type</label>
                            <select
                                value={identifierType}
                                onChange={(e) => setIdentifierType(e.target.value)}
                                className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm"
                            >
                                <option value="">All Types</option>
                                <option value="TN">TN (Tax Number)</option>
                                <option value="CIN">CIN (National ID)</option>
                                <option value="PASSPORT">Passport</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>
                        <div className="min-w-[150px]">
                            <label className="block text-xs font-medium text-gray-600 mb-1">City</label>
                            <input
                                type="text"
                                placeholder="Filter by city..."
                                value={city}
                                onChange={(e) => setCity(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm"
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button onClick={applyFilters}>Apply Filters</Button>
                            {hasActiveFilters && (
                                <Button variant="ghost" onClick={clearFilters}>Clear</Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Customers Grid/Table */}
                {customers.data.length === 0 ? (
                    <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-12 text-center">
                        <div className="mx-auto w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                            <svg className="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900">No customers found</h3>
                        <p className="mt-1 text-sm text-gray-500">Get started by adding your first customer.</p>
                        <Link href="/customers/create" className="mt-4 inline-block">
                            <Button size="sm">Add Customer</Button>
                        </Link>
                    </div>
                ) : (
                    <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-100">
                                <thead>
                                    <tr className="bg-gradient-to-r from-gray-50 to-gray-50/50">
                                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Identifier</th>
                                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Location</th>
                                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                                        <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {customers.data.map((customer, idx) => (
                                        <tr key={customer.id} className={`transition-colors hover:bg-user-50/50 ${idx % 2 === 0 ? '' : 'bg-gray-50/30'}`}>
                                            <td className="px-6 py-4">
                                                <Link href={`/customers/${customer.id}`} className="flex items-center gap-3 group">
                                                    <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-user-500 to-purple-500 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                                        {customer.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <span className="font-semibold text-gray-900 group-hover:text-user-600 transition-colors">{customer.name}</span>
                                                </Link>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm">
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-xs font-medium text-gray-600">{customer.identifier_type}</span>
                                                    <p className="mt-1 font-mono text-gray-700">{customer.identifier_value}</p>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{customer.city || '-'}</td>
                                            <td className="px-6 py-4 text-sm">
                                                {customer.phone && <p className="text-gray-600">{customer.phone}</p>}
                                                {customer.email && <p className="text-gray-500 text-xs">{customer.email}</p>}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/customers/${customer.id}`} className="p-2 rounded-lg text-gray-500 hover:text-user-600 hover:bg-user-50 transition-all" title="View">
                                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    </Link>
                                                    {canEdit('customers') && (
                                                        <Link href={`/customers/${customer.id}/edit`} className="p-2 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 transition-all" title="Edit">
                                                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                                        </Link>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Pagination */}
                <Pagination links={customers.links} />
            </div>
        </AuthenticatedLayout>
    );
}
