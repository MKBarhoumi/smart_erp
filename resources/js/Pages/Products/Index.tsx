import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Pagination } from '@/Components/ui/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatTND, formatNumber } from '@/utils/format';
import type { Product, PaginatedData } from '@/types';

interface Props {
    products: PaginatedData<Product>;
    filters: { search?: string };
}

export default function ProductsIndex({ products, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/products', { search }, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Products" />

            <div className="space-y-6">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-gradient-to-br from-user-600 to-indigo-500 text-white shadow-lg shadow-user-500/25">
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                            </div>
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-gray-900">Products & Services</h1>
                                <p className="text-gray-500">Manage your inventory and service offerings</p>
                            </div>
                        </div>
                    </div>
                    <Link href="/products/create">
                        <button className="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-user-600 to-user-500 text-white text-sm font-semibold rounded-xl hover:from-user-700 hover:to-user-600 transition-all shadow-lg shadow-user-500/25 hover:shadow-xl hover:shadow-user-500/30 hover:-translate-y-0.5">
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            New Product
                        </button>
                    </Link>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white rounded-xl p-4 border border-gray-100 shadow-soft">
                        <p className="text-sm text-gray-500">Total Products</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{products.total}</p>
                    </div>
                    <div className="bg-gradient-to-r from-user-50 to-indigo-50 rounded-xl p-4 border border-user-200/50">
                        <p className="text-sm text-user-600">With Stock</p>
                        <p className="text-2xl font-bold text-user-700 mt-1">{products.data.filter(p => p.track_inventory).length}</p>
                    </div>
                    <div className="bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl p-4 border border-emerald-200/50">
                        <p className="text-sm text-emerald-600">In Stock</p>
                        <p className="text-2xl font-bold text-emerald-700 mt-1">{products.data.filter(p => p.track_inventory && Number(p.current_stock) > Number(p.min_stock_alert)).length}</p>
                    </div>
                    <div className="bg-gradient-to-r from-red-50 to-rose-50 rounded-xl p-4 border border-red-200/50">
                        <p className="text-sm text-red-600">Low Stock</p>
                        <p className="text-2xl font-bold text-red-700 mt-1">{products.data.filter(p => p.track_inventory && Number(p.current_stock) <= Number(p.min_stock_alert)).length}</p>
                    </div>
                </div>

                {/* Search */}
                <form onSubmit={handleSearch} className="bg-white rounded-2xl border border-gray-100 shadow-soft p-5">
                    <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1 relative">
                            <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                            <input type="text" placeholder="Search by name or code..." value={search} onChange={(e) => setSearch(e.target.value)} className="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all text-sm" />
                        </div>
                        <button type="submit" className="px-5 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all">Search</button>
                    </div>
                </form>

                {/* Products Table */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr className="bg-gradient-to-r from-gray-50 to-slate-50">
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Product</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Price HT</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">TVA</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stock</th>
                                    <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {products.data.map((product, idx) => {
                                    const isLowStock = product.track_inventory && Number(product.current_stock) <= Number(product.min_stock_alert);
                                    return (
                                        <tr key={product.id} className={`transition-colors hover:bg-user-50/30 ${idx % 2 === 0 ? '' : 'bg-gray-50/30'}`}>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-user-500 to-indigo-500 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                                        {product.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <Link href={`/products/${product.id}`} className="font-semibold text-gray-900 hover:text-user-600 transition-colors">{product.name}</Link>
                                                        <p className="text-xs text-gray-500 font-mono">{product.code}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="font-semibold text-gray-900">{formatTND(product.unit_price)}</span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">{product.tva_rate}%</span>
                                            </td>
                                            <td className="px-6 py-4 text-gray-600">{product.unit_of_measure}</td>
                                            <td className="px-6 py-4">
                                                {product.track_inventory ? (
                                                    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium ${isLowStock ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}`}>
                                                        <span className={`w-1.5 h-1.5 rounded-full ${isLowStock ? 'bg-red-500 animate-pulse' : 'bg-emerald-500'}`} />
                                                        {formatNumber(product.current_stock)}
                                                    </span>
                                                ) : (<span className="text-gray-400 text-sm">Not tracked</span>)}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/products/${product.id}`} className="p-2 rounded-lg text-gray-500 hover:text-user-600 hover:bg-user-50 transition-all" title="View"><svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.577 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.007-9.963-7.178z" /><path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg></Link>
                                                    <Link href={`/products/${product.id}/edit`} className="p-2 rounded-lg text-gray-500 hover:text-user-600 hover:bg-user-50 transition-all" title="Edit"><svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg></Link>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {products.data.length === 0 && (
                                    <tr><td colSpan={6} className="px-6 py-12 text-center">
                                        <div className="mx-auto w-16 h-16 rounded-2xl bg-user-100 flex items-center justify-center mb-4"><svg className="w-8 h-8 text-user-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg></div>
                                        <h3 className="text-lg font-semibold text-gray-900">No products found</h3>
                                        <p className="mt-1 text-sm text-gray-500">Create your first product to get started.</p>
                                    </td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="border-t border-gray-100 px-4 py-3"><Pagination links={products.links} /></div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
