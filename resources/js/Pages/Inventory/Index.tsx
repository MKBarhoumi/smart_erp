import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Modal } from '@/Components/ui/Modal';
import { Pagination } from '@/Components/ui/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Product, PaginatedData, PageProps } from '@/types';

interface StockMovement {
    id: string;
    product: { id: string; code: string; name: string };
    type: string;
    quantity: string;
    quantity_before: string;
    quantity_after: string;
    reference: string;
    notes: string;
    created_at: string;
}

interface Props extends PageProps {
    products: PaginatedData<Product>;
    recentMovements: StockMovement[];
    lowStockCount: number;
}

export default function Index({ products, recentMovements, lowStockCount }: Props) {
    const [showAdjust, setShowAdjust] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);

    const form = useForm({ product_id: '', type: 'adjustment', quantity: '', notes: '' });

    const openAdjust = (product: Product) => {
        setSelectedProduct(product);
        form.setData('product_id', product.id);
        form.setData('quantity', '');
        form.setData('notes', '');
        setShowAdjust(true);
    };

    const submitAdjust = (e: FormEvent) => {
        e.preventDefault();
        form.post('/inventory/adjustment', { onSuccess: () => { setShowAdjust(false); form.reset(); } });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Inventory" />

            <div className="space-y-6">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-xl bg-gradient-to-br from-user-600 to-indigo-500 text-white shadow-lg shadow-user-500/25">
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight text-gray-900">Inventory Management</h1>
                            <p className="text-gray-500">Track and manage your product stock levels</p>
                        </div>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white rounded-xl p-4 border border-gray-100 shadow-soft">
                        <p className="text-sm text-gray-500">Tracked Products</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{products.total}</p>
                    </div>
                    <div className="bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl p-4 border border-emerald-200/50">
                        <p className="text-sm text-emerald-600">In Stock</p>
                        <p className="text-2xl font-bold text-emerald-700 mt-1">{products.data.filter(p => parseFloat(p.current_stock) > parseFloat(p.min_stock_alert)).length}</p>
                    </div>
                    <div className="bg-gradient-to-r from-red-50 to-rose-50 rounded-xl p-4 border border-red-200/50">
                        <div className="flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span><p className="text-sm text-red-600">Low Stock</p></div>
                        <p className="text-2xl font-bold text-red-700 mt-1">{lowStockCount}</p>
                    </div>
                    <div className="bg-gradient-to-r from-user-50 to-indigo-50 rounded-xl p-4 border border-user-200/50">
                        <p className="text-sm text-user-600">Recent Movements</p>
                        <p className="text-2xl font-bold text-user-700 mt-1">{recentMovements.length}</p>
                    </div>
                </div>

                {/* Low Stock Alert */}
                {lowStockCount > 0 && (
                    <div className="bg-gradient-to-r from-red-50 to-rose-50 rounded-2xl border border-red-200/50 p-5">
                        <div className="flex items-center gap-4">
                            <div className="p-2 rounded-xl bg-red-100"><svg className="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg></div>
                            <div><p className="font-semibold text-red-800">Low Stock Alert</p><p className="text-sm text-red-600">{lowStockCount} product(s) have fallen below their minimum stock level. Consider restocking soon.</p></div>
                        </div>
                    </div>
                )}

                {/* Stock Table */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100"><h2 className="text-lg font-semibold text-gray-900">Stock Levels</h2></div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr className="bg-gradient-to-r from-gray-50 to-slate-50">
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Product</th>
                                    <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Current Stock</th>
                                    <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Min. Stock</th>
                                    <th className="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {products.data.map((product, idx) => {
                                    const current = parseFloat(product.current_stock);
                                    const minimum = parseFloat(product.min_stock_alert);
                                    const isLow = current <= minimum;
                                    return (
                                        <tr key={product.id} className={`transition-colors hover:bg-user-50/30 ${isLow ? 'bg-red-50/50' : idx % 2 === 0 ? '' : 'bg-gray-50/30'}`}>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-white font-semibold text-sm shadow-sm ${isLow ? 'bg-gradient-to-br from-red-500 to-rose-500' : 'bg-gradient-to-br from-user-500 to-indigo-500'}`}>{product.name.charAt(0).toUpperCase()}</div>
                                                    <div><p className="font-semibold text-gray-900">{product.name}</p><p className="text-xs text-gray-500 font-mono">{product.code}</p></div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-right"><span className={`font-bold text-lg ${isLow ? 'text-red-600' : 'text-gray-900'}`}>{current.toFixed(3)}</span></td>
                                            <td className="px-6 py-4 text-right text-gray-500">{minimum.toFixed(3)}</td>
                                            <td className="px-6 py-4 text-center">
                                                <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ${isLow ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}`}>
                                                    <span className={`w-1.5 h-1.5 rounded-full ${isLow ? 'bg-red-500 animate-pulse' : 'bg-emerald-500'}`} />
                                                    {isLow ? 'Low Stock' : 'OK'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <button onClick={() => openAdjust(product)} className="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-user-100 hover:text-user-700 transition-all">
                                                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
                                                    Adjust
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {products.data.length === 0 && (
                                    <tr><td colSpan={5} className="px-6 py-12 text-center">
                                        <div className="mx-auto w-16 h-16 rounded-2xl bg-user-100 flex items-center justify-center mb-4"><svg className="w-8 h-8 text-user-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg></div>
                                        <h3 className="text-lg font-semibold text-gray-900">No inventory products</h3>
                                        <p className="mt-1 text-sm text-gray-500">No products with inventory tracking enabled.</p>
                                    </td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="border-t border-gray-100 px-4 py-3"><Pagination links={products.links} /></div>
                </div>

                {/* Recent Movements */}
                {recentMovements.length > 0 && (
                    <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-100"><h2 className="text-lg font-semibold text-gray-900">Recent Movements</h2></div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-100">
                                <thead>
                                    <tr className="bg-gradient-to-r from-gray-50 to-slate-50">
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Product</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Type</th>
                                        <th className="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Qty</th>
                                        <th className="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Before</th>
                                        <th className="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">After</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Notes</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {recentMovements.map((m) => {
                                        const typeStyles: Record<string, { bg: string; text: string }> = { in: { bg: 'bg-emerald-100', text: 'text-emerald-700' }, out: { bg: 'bg-amber-100', text: 'text-amber-700' }, adjustment: { bg: 'bg-gray-100', text: 'text-gray-700' } };
                                        const style = typeStyles[m.type] ?? typeStyles.adjustment;
                                        return (
                                            <tr key={m.id} className="hover:bg-gray-50/50">
                                                <td className="px-6 py-3 text-sm text-gray-600">{new Date(m.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                                                <td className="px-6 py-3 text-sm font-medium text-gray-900">{m.product?.code}</td>
                                                <td className="px-6 py-3"><span className={`inline-flex px-2.5 py-1 rounded-lg text-xs font-medium ${style.bg} ${style.text}`}>{m.type === 'in' ? 'Stock In' : m.type === 'out' ? 'Stock Out' : 'Adjustment'}</span></td>
                                                <td className="px-6 py-3 text-right font-semibold text-gray-900">{parseFloat(m.quantity).toFixed(3)}</td>
                                                <td className="px-6 py-3 text-right text-gray-500">{parseFloat(m.quantity_before).toFixed(3)}</td>
                                                <td className="px-6 py-3 text-right text-gray-900">{parseFloat(m.quantity_after).toFixed(3)}</td>
                                                <td className="px-6 py-3 text-sm text-gray-500">{m.notes || '—'}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>

            <Modal show={showAdjust} onClose={() => setShowAdjust(false)} title={`Adjust stock: ${selectedProduct?.name ?? ''}`}>
                <form onSubmit={submitAdjust} className="space-y-5 p-2">
                    {selectedProduct && (
                        <div className="bg-gray-50 rounded-xl p-4"><p className="text-sm text-gray-600">Current stock: <strong className="text-gray-900">{parseFloat(selectedProduct.current_stock).toFixed(3)}</strong></p></div>
                    )}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">New Quantity</label>
                        <input type="number" step="0.001" value={form.data.quantity} onChange={(e) => form.setData('quantity', e.target.value)} className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all" required />
                        {form.errors.quantity && <p className="mt-1 text-sm text-red-600">{form.errors.quantity}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                        <input type="text" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all" />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowAdjust(false)} className="px-4 py-2 text-gray-600 font-medium rounded-lg hover:bg-gray-100 transition-colors">Cancel</button>
                        <button type="submit" disabled={form.processing} className="px-5 py-2 bg-gradient-to-r from-user-600 to-user-500 text-white font-semibold rounded-lg hover:from-user-700 hover:to-user-600 transition-all shadow-lg shadow-user-500/25 disabled:opacity-50">{form.processing ? 'Saving...' : 'Save'}</button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
