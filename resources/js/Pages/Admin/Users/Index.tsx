import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Badge } from '@/Components/ui/Badge';
import { Pagination } from '@/Components/ui/Pagination';
import { Modal } from '@/Components/ui/Modal';
import type { User, PaginatedData, PageProps } from '@/types';
import { useState } from 'react';

interface Props extends PageProps {
    users: PaginatedData<User>;
    filters: { search?: string };
}

const roleBadge: Record<string, 'info' | 'success' | 'default'> = {
    admin: 'info',
    accountant: 'success',
    viewer: 'default',
};

export default function Index({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [deleteUser, setDeleteUser] = useState<User | null>(null);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/users', { search }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="User Management" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Users</h1>
                    <Link href="/admin/users/create"><Button>+ New User</Button></Link>
                </div>

                <form onSubmit={handleSearch} className="flex gap-3">
                    <div className="w-80">
                        <Input placeholder="Search by name or email..." value={search} onChange={(e) => setSearch(e.target.value)} />
                    </div>
                    <Button type="submit" variant="secondary">Search</Button>
                </form>

                <div className="rounded-lg bg-white shadow">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Role</th>
                                    <th className="px-4 py-3">Created</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {users.data.map((user) => (
                                    <tr key={user.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 font-medium">{user.name}</td>
                                        <td className="px-4 py-3">{user.email}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant={roleBadge[user.role] ?? 'default'}>{user.role}</Badge>
                                        </td>
                                        <td className="px-4 py-3 text-gray-500">{new Date(user.created_at).toLocaleDateString('en-US')}</td>
                                        <td className="px-4 py-3 text-right space-x-2">
                                            <Link href={`/admin/users/${user.id}/edit`}><Button size="sm" variant="secondary">Edit</Button></Link>
                                            <Button size="sm" variant="danger" onClick={() => setDeleteUser(user)}>Delete</Button>
                                        </td>
                                    </tr>
                                ))}
                                {users.data.length === 0 && (
                                    <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-500">No users found.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={users.links} />
                </div>
            </div>

            <Modal show={!!deleteUser} onClose={() => setDeleteUser(null)} title="Confirm Deletion">
                <p className="text-sm text-gray-600">
                    Delete user <strong>{deleteUser?.name}</strong> ({deleteUser?.email})? This action is irreversible.
                </p>
                <div className="mt-4 flex justify-end gap-3">
                    <Button variant="secondary" onClick={() => setDeleteUser(null)}>Cancel</Button>
                    <Button variant="danger" onClick={() => { router.delete(`/admin/users/${deleteUser?.id}`); setDeleteUser(null); }}>Delete</Button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
