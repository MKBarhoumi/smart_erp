import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/Components/ui/Badge';
import { Button } from '@/Components/ui/Button';
import { Modal } from '@/Components/ui/Modal';
import { Pagination } from '@/Components/ui/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { User, PaginatedData, PageProps } from '@/types';

interface Props extends PageProps {
    users: PaginatedData<User>;
    filters: { search?: string };
}

const roleColors: Record<string, { bg: string; text: string; dot: string }> = {
    super_admin: { bg: 'bg-gradient-to-r from-admin-100 to-red-100', text: 'text-admin-700', dot: 'bg-admin-500' },
    admin: { bg: 'bg-gradient-to-r from-admin-100 to-orange-100', text: 'text-admin-700', dot: 'bg-admin-400' },
    accountant: { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    viewer: { bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-400' },
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
                {/* Admin Page Header - Orange Theme */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-gradient-to-br from-admin-500 to-red-500 text-white shadow-lg shadow-admin-500/25">
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                            </div>
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-gray-900">User Management</h1>
                                <p className="text-admin-600 text-sm font-medium">Admin Panel • Manage system users and permissions</p>
                            </div>
                        </div>
                    </div>
                    <Link href="/admin/users/create">
                        <button className="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-admin-600 to-admin-500 text-white text-sm font-semibold rounded-xl hover:from-admin-700 hover:to-admin-600 transition-all shadow-lg shadow-admin-500/25 hover:shadow-xl hover:shadow-admin-500/30 hover:-translate-y-0.5">
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
                            Add User
                        </button>
                    </Link>
                </div>

                {/* Search - Admin themed */}
                <form onSubmit={handleSearch} className="bg-gradient-to-r from-admin-50 to-orange-50 rounded-2xl border border-admin-200/50 p-5">
                    <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1 relative">
                            <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-admin-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                            <input type="text" placeholder="Search by name or email..." value={search} onChange={(e) => setSearch(e.target.value)} className="w-full pl-12 pr-4 py-3 rounded-xl border-0 bg-white ring-1 ring-admin-200 focus:ring-2 focus:ring-admin-500 transition-all text-sm" />
                        </div>
                        <button type="submit" className="px-5 py-3 bg-white text-admin-700 font-semibold rounded-xl ring-1 ring-admin-200 hover:bg-admin-50 transition-all">Search</button>
                    </div>
                </form>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white rounded-xl p-4 border border-gray-100 shadow-soft">
                        <p className="text-sm text-gray-500">Total Users</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{users.total}</p>
                    </div>
                    <div className="bg-gradient-to-r from-admin-50 to-orange-50 rounded-xl p-4 border border-admin-200/50">
                        <p className="text-sm text-admin-600">Admins</p>
                        <p className="text-2xl font-bold text-admin-700 mt-1">{users.data.filter(u => u.role === 'admin' || u.role === 'super_admin').length}</p>
                    </div>
                    <div className="bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl p-4 border border-emerald-200/50">
                        <p className="text-sm text-emerald-600">Accountants</p>
                        <p className="text-2xl font-bold text-emerald-700 mt-1">{users.data.filter(u => u.role === 'accountant').length}</p>
                    </div>
                    <div className="bg-gradient-to-r from-gray-50 to-slate-50 rounded-xl p-4 border border-gray-200/50">
                        <p className="text-sm text-gray-600">Viewers</p>
                        <p className="text-2xl font-bold text-gray-700 mt-1">{users.data.filter(u => u.role === 'viewer').length}</p>
                    </div>
                </div>

                {/* Users Table */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr className="bg-gradient-to-r from-admin-50 to-orange-50/50">
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-admin-700 uppercase tracking-wider">User</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-admin-700 uppercase tracking-wider">Email</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-admin-700 uppercase tracking-wider">Role</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-admin-700 uppercase tracking-wider">Joined</th>
                                    <th className="px-6 py-4 text-right text-xs font-semibold text-admin-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {users.data.map((user, idx) => {
                                    const role = roleColors[user.role] ?? roleColors.viewer;
                                    return (
                                        <tr key={user.id} className={`transition-colors hover:bg-admin-50/30 ${idx % 2 === 0 ? '' : 'bg-gray-50/30'}`}>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-white font-semibold text-sm shadow-sm ${user.role === 'admin' || user.role === 'super_admin' ? 'bg-gradient-to-br from-admin-500 to-red-500' : 'bg-gradient-to-br from-gray-400 to-gray-500'}`}>
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <span className="font-semibold text-gray-900">{user.name}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-gray-600">{user.email}</td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ${role.bg} ${role.text}`}>
                                                    <span className={`w-1.5 h-1.5 rounded-full ${role.dot}`} />
                                                    {user.role.replace('_', ' ')}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-500">{new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link href={`/admin/users/${user.id}/edit`} className="p-2 rounded-lg text-gray-500 hover:text-admin-600 hover:bg-admin-50 transition-all" title="Edit"><svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg></Link>
                                                    <button onClick={() => setDeleteUser(user)} className="p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 transition-all" title="Delete"><svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {users.data.length === 0 && (
                                    <tr><td colSpan={5} className="px-6 py-12 text-center">
                                        <div className="mx-auto w-16 h-16 rounded-2xl bg-admin-100 flex items-center justify-center mb-4"><svg className="w-8 h-8 text-admin-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></div>
                                        <h3 className="text-lg font-semibold text-gray-900">No users found</h3>
                                        <p className="mt-1 text-sm text-gray-500">Create your first user to get started.</p>
                                    </td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="border-t border-gray-100 px-4 py-3"><Pagination links={users.links} /></div>
                </div>
            </div>

            <Modal show={!!deleteUser} onClose={() => setDeleteUser(null)} title="Confirm Deletion">
                <div className="p-4">
                    <div className="flex items-center gap-4 p-4 bg-red-50 rounded-xl mb-4">
                        <div className="p-2 rounded-lg bg-red-100"><svg className="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg></div>
                        <div><p className="font-semibold text-red-800">This action cannot be undone</p><p className="text-sm text-red-600">The user will be permanently removed.</p></div>
                    </div>
                    <p className="text-gray-600">Are you sure you want to delete <strong className="text-gray-900">{deleteUser?.name}</strong> ({deleteUser?.email})?</p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setDeleteUser(null)}>Cancel</Button>
                        <Button variant="danger" onClick={() => { router.delete(`/admin/users/${deleteUser?.id}`); setDeleteUser(null); }}>Delete User</Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
