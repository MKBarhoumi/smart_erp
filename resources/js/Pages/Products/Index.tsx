import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Table } from '@/Components/ui/Table';
import { Pagination } from '@/Components/ui/Pagination';
import type { Product, PaginatedData } from '@/types';
import { useState } from 'react';

interface Props {
    products: PaginatedData<Product>;
    filters: { search?: string };
}

export default function ProductsIndex({ products, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const handleSearch = () => {
        router.get('/products', { search }, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Products" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Products & Services</h1>
                    <Link href="/products/create">
                        <Button>New Product</Button>
                    </Link>
                </div>

                <div className="flex gap-2">
                    <Input
                        placeholder="Search by name or code..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                    />
                    <Button variant="secondary" onClick={handleSearch}>Search</Button>
                </div>

                <Table<Product>
                    data={products.data}
                    columns={[
                        { key: 'code', label: 'Code', render: (p) => (
                            <Link href={`/products/${p.id}`} className="font-medium text-blue-600 hover:underline">
                                {p.code}
                            </Link>
                        )},
                        { key: 'name', label: 'Name' },
                        { key: 'unit_price', label: 'Price excl. tax', render: (p) => `${Number(p.unit_price).toFixed(3)} TND` },
                        { key: 'tva_rate', label: 'TVA %', render: (p) => `${p.tva_rate}%` },
                        { key: 'unit_of_measure', label: 'Unit' },
                        { key: 'current_stock', label: 'Stock', render: (p) => (
                            p.track_inventory ? (
                                <span className={Number(p.current_stock) <= Number(p.min_stock_alert) ? 'text-red-600 font-semibold' : ''}>
                                    {Number(p.current_stock).toFixed(3)}
                                </span>
                            ) : <span className="text-gray-400">—</span>
                        )},
                    ]}
                    emptyMessage="No products found."
                />

                <Pagination links={products.links} />
            </div>
        </AuthenticatedLayout>
    );
}
