import { Head, useForm, Link } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { User } from '@/types';

interface RoleOption {
    slug: string;
    name: string;
    color: string;
    is_system: boolean;
    description: string | null;
}

interface Props {
    user?: User;
    roles: RoleOption[];
    isEdit?: boolean;
}

const colorClasses: Record<string, { bg: string; text: string; ring: string }> = {
    purple: { bg: 'bg-purple-50', text: 'text-purple-700', ring: 'ring-purple-200' },
    blue: { bg: 'bg-blue-50', text: 'text-blue-700', ring: 'ring-blue-200' },
    emerald: { bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-200' },
    amber: { bg: 'bg-amber-50', text: 'text-amber-700', ring: 'ring-amber-200' },
    indigo: { bg: 'bg-indigo-50', text: 'text-indigo-700', ring: 'ring-indigo-200' },
    gray: { bg: 'bg-gray-50', text: 'text-gray-700', ring: 'ring-gray-200' },
    red: { bg: 'bg-red-50', text: 'text-red-700', ring: 'ring-red-200' },
    pink: { bg: 'bg-pink-50', text: 'text-pink-700', ring: 'ring-pink-200' },
    cyan: { bg: 'bg-cyan-50', text: 'text-cyan-700', ring: 'ring-cyan-200' },
    teal: { bg: 'bg-teal-50', text: 'text-teal-700', ring: 'ring-teal-200' },
};

export default function Form({ user, roles, isEdit = false }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        role: user?.role ?? (roles[0]?.slug ?? 'accountant'),
        is_active: user?.is_active ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit && user?.id) {
            put(`/admin/users/${user.id}`);
        } else {
            post('/admin/users');
        }
    };

    const selectedRole = roles.find(r => r.slug === data.role);

    return (
        <AuthenticatedLayout>
            <Head title={isEdit ? `Edit: ${user?.name}` : 'New User'} />

            <form onSubmit={submit} className="mx-auto max-w-2xl space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">
                        {isEdit ? `Edit: ${user?.name}` : 'New User'}
                    </h1>
                    <Link href="/admin/users"><Button type="button" variant="ghost">Back</Button></Link>
                </div>

                <div className="rounded-lg bg-white p-6 shadow space-y-4">
                    <Input label="Name" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />
                    <Input label="Email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} required />
                    <Input
                        label={isEdit ? 'New password (leave blank to keep current)' : 'Password'}
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={errors.password}
                        required={!isEdit}
                    />
                    <Input label="Confirm Password" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />

                    {/* Role Selection */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">Role</label>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {roles.map((role) => {
                                const colors = colorClasses[role.color] || colorClasses.gray;
                                const isSelected = data.role === role.slug;
                                return (
                                    <button
                                        key={role.slug}
                                        type="button"
                                        onClick={() => setData('role', role.slug)}
                                        className={`relative rounded-lg border p-3 text-left transition-all ${
                                            isSelected
                                                ? `${colors.bg} border-2 ${colors.ring.replace('ring', 'border')} ring-2 ${colors.ring}`
                                                : 'border-gray-200 hover:border-gray-300 bg-white'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2">
                                            <div className={`h-2.5 w-2.5 rounded-full ${colors.bg.replace('50', '500')}`} />
                                            <span className={`font-medium ${isSelected ? colors.text : 'text-gray-900'}`}>
                                                {role.name}
                                            </span>
                                            {role.is_system && (
                                                <span className="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">
                                                    System
                                                </span>
                                            )}
                                        </div>
                                        {role.description && (
                                            <p className="mt-1 text-xs text-gray-500 line-clamp-2">{role.description}</p>
                                        )}
                                        {isSelected && (
                                            <div className="absolute top-2 right-2">
                                                <svg className={`h-5 w-5 ${colors.text}`} fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                </svg>
                                            </div>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                        {errors.role && <p className="mt-1 text-sm text-red-600">{errors.role}</p>}
                    </div>

                    {/* Active Status Toggle */}
                    <div className="pt-4 border-t border-gray-100">
                        <label className="flex items-center justify-between cursor-pointer">
                            <div>
                                <span className="text-sm font-medium text-gray-900">Account Status</span>
                                <p className="text-xs text-gray-500 mt-0.5">
                                    {data.is_active
                                        ? 'User can access the system'
                                        : 'User cannot log in or access any features'
                                    }
                                </p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                aria-checked={data.is_active}
                                onClick={() => setData('is_active', !data.is_active)}
                                className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 ${
                                    data.is_active ? 'bg-emerald-500' : 'bg-gray-200'
                                }`}
                            >
                                <span
                                    className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                                        data.is_active ? 'translate-x-5' : 'translate-x-0'
                                    }`}
                                />
                            </button>
                        </label>
                        <div className={`mt-2 inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium ${
                            data.is_active
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-red-100 text-red-700'
                        }`}>
                            <span className={`w-1.5 h-1.5 rounded-full ${data.is_active ? 'bg-emerald-500' : 'bg-red-500'}`} />
                            {data.is_active ? 'Active' : 'Inactive'}
                        </div>
                    </div>
                </div>

                <div className="flex justify-end gap-3">
                    <Link href="/admin/users"><Button type="button" variant="secondary">Cancel</Button></Link>
                    <Button type="submit" loading={processing}>{isEdit ? 'Update' : 'Create'}</Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
