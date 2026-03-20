import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Pagination } from '@/Components/ui/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatTND } from '@/utils/format';
import { usePermissions } from '@/hooks/usePermissions';
import type { Service, PaginatedData, User } from '@/types';

interface Props {
    services: PaginatedData<Service>;
    filters: { search?: string; category?: string; status?: string };
    categories: string[];
}

export default function ServicesIndex({ services, filters, categories }: Props) {
    const { canCreate, canEdit, canDelete, isViewer } = usePermissions();
    
    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const applyFilters = (e?: React.FormEvent) => {
        e?.preventDefault();
        router.get('/services', { 
            search: search || undefined, 
            category: category || undefined,
            status: status || undefined
        }, { preserveState: true, replace: true });
    };

    const clearFilters = () => {
        setSearch('');
        setCategory('');
        setStatus('');
        router.get('/services', {}, { preserveState: true, replace: true });
    };

    const hasActiveFilters = search || category || status;

    const handleDelete = (service: Service) => {
        if (confirm(`Are you sure you want to delete "${service.name}"?`)) {
            router.delete(`/services/${service.id}`);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Services" />

            <div className="space-y-6">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-gradient-to-br from-user-600 to-purple-500 text-white shadow-lg shadow-user-500/25">
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                                </svg>
                            </div>
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-gray-900">Services</h1>
                                <p className="text-gray-500">Manage your service catalog</p>
                            </div>
                        </div>
                    </div>
                    {canCreate('services') && (
                        <Link href="/services/create">
                            <button className="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-user-600 to-user-500 text-white text-sm font-semibold rounded-xl hover:from-user-700 hover:to-user-600 transition-all shadow-lg shadow-user-500/25 hover:shadow-xl hover:shadow-user-500/30 hover:-translate-y-0.5">
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                New Service
                            </button>
                        </Link>
                    )}
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white rounded-xl p-4 border border-gray-100 shadow-soft">
                        <p className="text-sm text-gray-500">Total Services</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{services.total}</p>
                    </div>
                    <div className="bg-gradient-to-r from-user-50 to-purple-50 rounded-xl p-4 border border-user-200/50">
                        <p className="text-sm text-user-600">Active</p>
                        <p className="text-2xl font-bold text-user-700 mt-1">{services.data.filter(s => s.is_active).length}</p>
                    </div>
                    <div className="bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl p-4 border border-emerald-200/50">
                        <p className="text-sm text-emerald-600">Categories</p>
                        <p className="text-2xl font-bold text-emerald-700 mt-1">{new Set(services.data.map(s => s.category)).size}</p>
                    </div>
                    <div className="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-200/50">
                        <p className="text-sm text-amber-600">Inactive</p>
                        <p className="text-2xl font-bold text-amber-700 mt-1">{services.data.filter(s => !s.is_active).length}</p>
                    </div>
                </div>

                {/* Search & Filters */}
                <form onSubmit={applyFilters} className="bg-white rounded-2xl border border-gray-100 shadow-soft p-5">
                    <div className="flex flex-wrap items-end gap-4">
                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Search</label>
                            <div className="relative">
                                <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                                <input type="text" placeholder="Search by name, code, or category..." value={search} onChange={(e) => setSearch(e.target.value)} className="w-full pl-12 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm" />
                            </div>
                        </div>
                        <div className="min-w-[140px]">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Category</label>
                            <select value={category} onChange={(e) => setCategory(e.target.value)} className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm">
                                <option value="">All Categories</option>
                                {categories.map(cat => (
                                    <option key={cat} value={cat}>{cat}</option>
                                ))}
                            </select>
                        </div>
                        <div className="min-w-[120px]">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm">
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div className="flex gap-2">
                            <button type="submit" className="px-5 py-2.5 bg-user-600 text-white font-semibold rounded-xl hover:bg-user-700 transition-all">Apply</button>
                            {hasActiveFilters && (
                                <button type="button" onClick={clearFilters} className="px-5 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all">Clear</button>
                            )}
                        </div>
                    </div>
                </form>

                {/* Services Table */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr className="bg-gradient-to-r from-gray-50 to-slate-50">
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Service Code</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Category</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit</th>
                                    <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit Price (TND)</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tax Rate</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {services.data.map((service, idx) => (
                                    <tr key={service.id} className={`transition-colors hover:bg-user-50/30 ${idx % 2 === 0 ? '' : 'bg-gray-50/30'}`}>
                                        <td className="px-6 py-4">
                                            <span className="font-mono text-sm text-gray-700">{service.code}</span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-user-500 to-purple-500 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                                    {service.name.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <Link href={`/services/${service.id}`} className="font-semibold text-gray-900 hover:text-user-600 transition-colors">{service.name}</Link>
                                                    {service.description && (
                                                        <p className="text-xs text-gray-500 truncate max-w-[200px]">{service.description}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className="inline-flex items-center px-2.5 py-1 rounded-lg bg-user-100 text-user-700 text-xs font-medium">{service.category}</span>
                                        </td>
                                        <td className="px-6 py-4 text-gray-600">{service.unit}</td>
                                        <td className="px-6 py-4 text-right">
                                            <span className="font-semibold text-gray-900">{formatTND(service.unit_price)}</span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">{service.tax_rate}%</span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium ${service.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'}`}>
                                                <span className={`w-1.5 h-1.5 rounded-full ${service.is_active ? 'bg-emerald-500' : 'bg-gray-400'}`} />
                                                {service.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link href={`/services/${service.id}`} className="p-2 rounded-lg text-gray-500 hover:text-user-600 hover:bg-user-50 transition-all" title="View">
                                                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.577 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.007-9.963-7.178z" /><path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                </Link>
                                                {canEdit('services') && (
                                                    <Link href={`/services/${service.id}/edit`} className="p-2 rounded-lg text-gray-500 hover:text-user-600 hover:bg-user-50 transition-all" title="Edit">
                                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                                    </Link>
                                                )}
                                                {canDelete('services') && (
                                                    <button onClick={() => handleDelete(service)} className="p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 transition-all" title="Delete">
                                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {services.data.length === 0 && (
                                    <tr><td colSpan={8} className="px-6 py-12 text-center">
                                        <div className="mx-auto w-16 h-16 rounded-2xl bg-user-100 flex items-center justify-center mb-4">
                                            <svg className="w-8 h-8 text-user-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                                            </svg>
                                        </div>
                                        <h3 className="text-lg font-semibold text-gray-900">No services found</h3>
                                        <p className="mt-1 text-sm text-gray-500">Create your first service to get started.</p>
                                    </td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="border-t border-gray-100 px-4 py-3"><Pagination links={services.links} /></div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
