import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import type { Product } from '@/types';

interface Props {
    product: Product & {
        stock_movements: Array<{
            id: string;
            type: string;
            quantity: string;
            stock_before: string;
            stock_after: string;
            reason: string | null;
            created_at: string;
        }>;
    };
}

export default function Show({ product }: Props) {
    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this product?')) {
            router.delete(`/products/${product.id}`);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Product: ${product.name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">{product.name}</h1>
                    <div className="flex gap-2">
                        <Link href={`/products/${product.id}/edit`}>
                            <Button variant="secondary">Edit</Button>
                        </Link>
                        <Button variant="danger" onClick={handleDelete}>Delete</Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">Details</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between"><dt className="text-gray-500">Code</dt><dd className="font-mono">{product.code}</dd></div>
                            <div className="flex justify-between"><dt className="text-gray-500">Unit Price excl. tax</dt><dd>{Number(product.unit_price).toFixed(3)} TND</dd></div>
                            <div className="flex justify-between"><dt className="text-gray-500">TVA</dt><dd>{product.tva_rate}%</dd></div>
                            <div className="flex justify-between"><dt className="text-gray-500">Unit</dt><dd>{product.unit_of_measure}</dd></div>
                            <div className="flex justify-between"><dt className="text-gray-500">Stamp duty</dt><dd>{product.is_subject_to_timbre ? 'Yes' : 'No'}</dd></div>
                        </dl>
                    </div>

                    {product.track_inventory && (
                        <div className="rounded-lg bg-white p-6 shadow">
                            <h2 className="mb-4 text-lg font-semibold">Stock</h2>
                            <dl className="space-y-2 text-sm">
                                <div className="flex justify-between"><dt className="text-gray-500">Current Stock</dt><dd className="font-semibold">{Number(product.current_stock).toFixed(3)}</dd></div>
                                <div className="flex justify-between"><dt className="text-gray-500">Alert Threshold</dt><dd>{Number(product.min_stock_alert).toFixed(3)}</dd></div>
                            </dl>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
