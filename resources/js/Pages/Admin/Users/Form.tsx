import { Head, useForm, Link } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { User } from '@/types';

interface Props {
    user?: User;
    roles: string[];
    isEdit?: boolean;
}

export default function Form({ user, roles, isEdit = false }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        role: user?.role ?? 'accountant',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit && user?.id) {
            put(`/admin/users/${user.id}`);
        } else {
            post('/admin/users');
        }
    };

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
                    <Select
                        label="Role"
                        options={roles.map((r) => ({ value: r, label: r.charAt(0).toUpperCase() + r.slice(1) }))}
                        value={data.role}
                        onChange={(e) => setData('role', e.target.value as User['role'])}
                        error={errors.role}
                    />
                </div>

                <div className="flex justify-end gap-3">
                    <Link href="/admin/users"><Button type="button" variant="secondary">Cancel</Button></Link>
                    <Button type="submit" loading={processing}>{isEdit ? 'Update' : 'Create'}</Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
