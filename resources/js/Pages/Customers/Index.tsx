import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Table } from '@/Components/ui/Table';
import { Pagination } from '@/Components/ui/Pagination';
import type { Customer, PaginatedData } from '@/types';
import { useState } from 'react';

interface Props {
    customers: PaginatedData<Customer>;
    filters: { search?: string };
}

export default function CustomersIndex({ customers, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const handleSearch = () => {
        router.get('/customers', { search }, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Customers" />

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Customers</h1>
                    <Link href="/customers/create">
                        <Button>New Customer</Button>
                    </Link>
                </div>

                <div className="flex gap-2">
                    <Input
                        placeholder="Search by name, identifier, email..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                    />
                    <Button variant="secondary" onClick={handleSearch}>
                        Search
                    </Button>
                </div>

                <Table<Customer>
                    data={customers.data}
                    columns={[
                        { key: 'name', label: 'Name', render: (c) => (
                            <Link href={`/customers/${c.id}`} className="font-medium text-blue-600 hover:underline">
                                {c.name}
                            </Link>
                        )},
                        { key: 'identifier_type', label: 'Type ID' },
                        { key: 'identifier_value', label: 'Identifiant' },
                        { key: 'city', label: 'City' },
                        { key: 'phone', label: 'Phone' },
                        { key: 'email', label: 'Email' },
                    ]}
                    emptyMessage="No customers found."
                />

                <Pagination links={customers.links} />
            </div>
        </AuthenticatedLayout>
    );
}
