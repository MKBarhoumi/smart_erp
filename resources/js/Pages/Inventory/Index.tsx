import { Head, useForm } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useState } from 'react';
import { Badge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
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

    const form = useForm({
        product_id: '',
        type: 'adjustment',
        quantity: '',
        notes: '',
    });

    const openAdjust = (product: Product) => {
        setSelectedProduct(product);
        form.setData('product_id', product.id);
        form.setData('quantity', '');
        form.setData('notes', '');
        setShowAdjust(true);
    };

    const submitAdjust = (e: FormEvent) => {
        e.preventDefault();
        form.post('/inventory/adjustment', {
            onSuccess: () => {
                setShowAdjust(false);
                form.reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Inventory" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Inventory</h1>
                        {lowStockCount > 0 && (
                            <p className="mt-1 text-sm text-red-600">{lowStockCount} product(s) with low stock</p>
                        )}
                    </div>
                </div>

                {/* Stock Table */}
                <div className="rounded-lg bg-white shadow">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Code</th>
                                    <th className="px-4 py-3">Product</th>
                                    <th className="px-4 py-3 text-right">Current Stock</th>
                                    <th className="px-4 py-3 text-right">Min. Stock</th>
                                    <th className="px-4 py-3 text-center">Status</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {products.data.map((product) => {
                                    const current = parseFloat(product.current_stock);
                                    const minimum = parseFloat(product.min_stock_alert);
                                    const isLow = current <= minimum;
                                    return (
                                        <tr key={product.id} className={isLow ? 'bg-red-50' : ''}>
                                            <td className="px-4 py-3 font-mono text-xs">{product.code}</td>
                                            <td className="px-4 py-3 font-medium">{product.name}</td>
                                            <td className="px-4 py-3 text-right font-semibold">{current.toFixed(3)}</td>
                                            <td className="px-4 py-3 text-right text-gray-500">{minimum.toFixed(3)}</td>
                                            <td className="px-4 py-3 text-center">
                                                {isLow ? <Badge variant="danger">Low Stock</Badge> : <Badge variant="success">OK</Badge>}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button size="sm" variant="secondary" onClick={() => openAdjust(product)}>Adjust</Button>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {products.data.length === 0 && (
                                    <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-500">No products with inventory tracking.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={products.links} />
                </div>

                {/* Recent Movements */}
                {recentMovements.length > 0 && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">Recent Movements</h2>
                        <table className="min-w-full text-sm">
                            <thead className="border-b text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="px-3 py-2">Date</th>
                                    <th className="px-3 py-2">Product</th>
                                    <th className="px-3 py-2">Type</th>
                                    <th className="px-3 py-2 text-right">Quantity</th>
                                    <th className="px-3 py-2 text-right">Before</th>
                                    <th className="px-3 py-2 text-right">After</th>
                                    <th className="px-3 py-2">Notes</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {recentMovements.map((m) => (
                                    <tr key={m.id}>
                                        <td className="px-3 py-2">{new Date(m.created_at).toLocaleString('en-US')}</td>
                                        <td className="px-3 py-2">{m.product?.code}</td>
                                        <td className="px-3 py-2">
                                            <Badge variant={m.type === 'in' ? 'success' : m.type === 'out' ? 'warning' : 'default'}>
                                                {m.type === 'in' ? 'In' : m.type === 'out' ? 'Out' : 'Adjustment'}
                                            </Badge>
                                        </td>
                                        <td className="px-3 py-2 text-right">{parseFloat(m.quantity).toFixed(3)}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(m.quantity_before).toFixed(3)}</td>
                                        <td className="px-3 py-2 text-right">{parseFloat(m.quantity_after).toFixed(3)}</td>
                                        <td className="px-3 py-2 text-gray-500">{m.notes || '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <Modal show={showAdjust} onClose={() => setShowAdjust(false)} title={`Adjust stock: ${selectedProduct?.name ?? ''}`}>
                <form onSubmit={submitAdjust} className="space-y-4">
                    {selectedProduct && (
                        <p className="text-sm text-gray-600">Current stock: <strong>{parseFloat(selectedProduct.current_stock).toFixed(3)}</strong></p>
                    )}
                    <Input label="New Quantity" type="number" step="0.001" value={form.data.quantity} onChange={(e) => form.setData('quantity', e.target.value)} error={form.errors.quantity} required />
                    <Input label="Notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="secondary" onClick={() => setShowAdjust(false)}>Cancel</Button>
                        <Button type="submit" loading={form.processing}>Save</Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
