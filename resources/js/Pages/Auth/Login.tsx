import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <GuestLayout>
            <Head title="Sign In" />

            <div className="mb-6">
                <h2 className="text-2xl font-bold text-gray-900">Sign In</h2>
                <p className="mt-1 text-sm text-gray-600">
                    Access your Smart ERP Lite invoicing workspace
                </p>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <Input
                    label="Email address"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    required
                    autoFocus
                />

                <Input
                    label="Password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    required
                />

                <div className="flex items-center">
                    <label className="flex items-center">
                        <input
                            type="checkbox"
                            className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                        />
                        <span className="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <Button type="submit" loading={processing} className="w-full">
                    Sign In
                </Button>

                <p className="text-center text-sm text-gray-600">
                    Don't have an account?{' '}
                    <Link href="/register" className="text-blue-600 hover:text-blue-500">
                        Create an account
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}
