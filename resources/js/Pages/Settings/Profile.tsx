import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useState } from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    mustVerifyEmail: boolean;
    status?: string;
}

export default function Profile({ mustVerifyEmail, status }: Props) {
    const user = usePage<PageProps>().props.auth.user;

    const { data, setData, patch, processing, errors } = useForm({
        name: user.name,
        email: user.email,
    });

    const { data: passwordData, setData: setPasswordData, put: putPassword, processing: passwordProcessing, errors: passwordErrors, reset: resetPassword } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [deletePassword, setDeletePassword] = useState('');
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const updateProfile = (e: FormEvent) => {
        e.preventDefault();
        patch('/settings/profile', {
            preserveScroll: true,
        });
    };

    const updatePassword = (e: FormEvent) => {
        e.preventDefault();
        putPassword('/password', {
            preserveScroll: true,
            onSuccess: () => resetPassword(),
        });
    };

    const deleteAccount = () => {
        if (!deletePassword) return;
        setDeleteProcessing(true);
        // Manual post since we need to use delete method
        window.location.href = '/settings/profile';
    };

    return (
        <AuthenticatedLayout>
            <Head title="Profile" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Profile Settings</h1>
                    <p className="mt-1 text-sm text-gray-600">
                        Manage your account settings and update your profile information.
                    </p>
                </div>

                {status && (
                    <div className="rounded-md bg-green-50 p-4">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm font-medium text-green-800">{status}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Profile Information */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-1 text-lg font-semibold text-gray-900">Profile Information</h2>
                    <p className="mb-4 text-sm text-gray-600">
                        Update your account's profile information and email address.
                    </p>

                    <form onSubmit={updateProfile} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Input
                                label="Name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                                required
                            />
                            <Input
                                label="Email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                                required
                            />
                        </div>

                        {mustVerifyEmail && user.email_verified_at === null && (
                            <div className="text-sm text-yellow-600">
                                Your email address is unverified.{' '}
                                <button
                                    type="button"
                                    className="underline hover:text-yellow-500"
                                    onClick={() => {
                                        // Send verification email
                                    }}
                                >
                                    Click here to re-send the verification email.
                                </button>
                            </div>
                        )}

                        <div className="flex justify-end">
                            <Button type="submit" loading={processing}>
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </div>

                {/* Update Password */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-1 text-lg font-semibold text-gray-900">Update Password</h2>
                    <p className="mb-4 text-sm text-gray-600">
                        Ensure your account is using a long, random password to stay secure.
                    </p>

                    <form onSubmit={updatePassword} className="space-y-4">
                        <Input
                            label="Current Password"
                            type="password"
                            value={passwordData.current_password}
                            onChange={(e) => setPasswordData('current_password', e.target.value)}
                            error={passwordErrors.current_password}
                            required
                        />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Input
                                label="New Password"
                                type="password"
                                value={passwordData.password}
                                onChange={(e) => setPasswordData('password', e.target.value)}
                                error={passwordErrors.password}
                                required
                            />
                            <Input
                                label="Confirm Password"
                                type="password"
                                value={passwordData.password_confirmation}
                                onChange={(e) => setPasswordData('password_confirmation', e.target.value)}
                                error={passwordErrors.password_confirmation}
                                required
                            />
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" loading={passwordProcessing}>
                                Update Password
                            </Button>
                        </div>
                    </form>
                </div>

                {/* Account Info */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h2 className="mb-1 text-lg font-semibold text-gray-900">Account Information</h2>
                    <p className="mb-4 text-sm text-gray-600">
                        View your account details.
                    </p>
                    
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Role</dt>
                            <dd className="mt-1 text-sm text-gray-900 capitalize">{user.role.replace('_', ' ')}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Account Status</dt>
                            <dd className="mt-1">
                                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                    {user.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Email Verified</dt>
                            <dd className="mt-1">
                                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${user.email_verified_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                                    {user.email_verified_at ? 'Verified' : 'Not Verified'}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Member Since</dt>
                            <dd className="mt-1 text-sm text-gray-900">
                                {new Date(user.created_at).toLocaleDateString('fr-TN', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                })}
                            </dd>
                        </div>
                    </div>
                </div>

                {/* Delete Account */}
                <div className="rounded-lg border border-red-200 bg-red-50 p-6">
                    <h2 className="mb-1 text-lg font-semibold text-red-800">Delete Account</h2>
                    <p className="mb-4 text-sm text-red-600">
                        Once your account is deleted, all of its resources and data will be permanently deleted.
                        Before deleting your account, please download any data or information that you wish to retain.
                    </p>

                    <Button
                        type="button"
                        variant="danger"
                        onClick={() => setShowDeleteModal(true)}
                    >
                        Delete Account
                    </Button>
                </div>
            </div>

            {/* Delete Confirmation Modal */}
            {showDeleteModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={() => setShowDeleteModal(false)} />
                        
                        <div className="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                            <div className="sm:flex sm:items-start">
                                <div className="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg className="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div className="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                    <h3 className="text-base font-semibold leading-6 text-gray-900">
                                        Delete account
                                    </h3>
                                    <div className="mt-2">
                                        <p className="text-sm text-gray-500">
                                            Are you sure you want to delete your account? All of your data will be permanently removed.
                                            This action cannot be undone.
                                        </p>
                                    </div>
                                    <div className="mt-4">
                                        <Input
                                            label="Password"
                                            type="password"
                                            value={deletePassword}
                                            onChange={(e) => setDeletePassword(e.target.value)}
                                            placeholder="Enter your password to confirm"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                                <Button
                                    type="button"
                                    variant="danger"
                                    loading={deleteProcessing}
                                    onClick={deleteAccount}
                                    disabled={!deletePassword}
                                >
                                    Delete Account
                                </Button>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => setShowDeleteModal(false)}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
