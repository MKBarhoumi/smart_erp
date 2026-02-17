import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import type { Customer, Invoice } from '@/types';

interface Props {
    customer: Customer & {
        invoices: Invoice[];
    };
}

export default function Show({ customer }: Props) {
    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this customer?')) {
            router.delete(`/customers/${customer.id}`);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Customer: ${customer.name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">{customer.name}</h1>
                    <div className="flex gap-2">
                        <Link href={`/customers/${customer.id}/edit`}>
                            <Button variant="secondary">Edit</Button>
                        </Link>
                        <Button variant="danger" onClick={handleDelete}>Delete</Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Identity */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">Identity</h2>
                        <dl className="space-y-2">
                            <div className="flex justify-between"><dt className="text-gray-500">ID Type</dt><dd>{customer.identifier_type}</dd></div>
                            <div className="flex justify-between"><dt className="text-gray-500">Identifier</dt><dd>{customer.identifier_value}</dd></div>
                            {customer.matricule_fiscal && <div className="flex justify-between"><dt className="text-gray-500">Matricule Fiscal</dt><dd>{customer.matricule_fiscal}</dd></div>}
                        </dl>
                    </div>

                    {/* Contact */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h2 className="mb-4 text-lg font-semibold">Contact</h2>
                        <dl className="space-y-2">
                            {customer.email && <div className="flex justify-between"><dt className="text-gray-500">Email</dt><dd>{customer.email}</dd></div>}
                            {customer.phone && <div className="flex justify-between"><dt className="text-gray-500">Phone</dt><dd>{customer.phone}</dd></div>}
                            {customer.city && <div className="flex justify-between"><dt className="text-gray-500">City</dt><dd>{customer.city}</dd></div>}
                        </dl>
                    </div>
                </div>

                {/* Invoices */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-4 text-lg font-semibold">Recent Invoices</h2>
                    {customer.invoices.length === 0 ? (
                        <p className="text-sm text-gray-500">No invoices.</p>
                    ) : (
                        <div className="space-y-2">
                            {customer.invoices.map((inv) => (
                                <Link
                                    key={inv.id}
                                    href={`/invoices/${inv.id}`}
                                    className="flex items-center justify-between rounded-lg border p-3 hover:bg-gray-50"
                                >
                                    <span className="font-medium">{inv.invoice_number}</span>
                                    <span>{Number(inv.total_ttc).toFixed(3)} TND</span>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
