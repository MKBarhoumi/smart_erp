import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';

interface Props {
  token: string;
  email: string;
}

export default function ResetPassword({ token, email }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    token,
    email,
    password: '',
    password_confirmation: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    post('/reset-password');
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 py-12">
      <Head title="Reset Password" />

      <div className="w-full max-w-md space-y-6 rounded-xl bg-white p-8 shadow-lg">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Reset Password</h2>
          <p className="mt-2 text-sm text-gray-600">Set your new password below.</p>
        </div>

        <form onSubmit={submit} className="space-y-4">
          <Input
            label="Email address"
            type="email"
            value={data.email}
            onChange={(e) => setData('email', e.target.value)}
            error={errors.email}
            required
          />

          <Input
            label="New password"
            type="password"
            value={data.password}
            onChange={(e) => setData('password', e.target.value)}
            error={errors.password}
            required
            autoFocus
          />

          <Input
            label="Confirm password"
            type="password"
            value={data.password_confirmation}
            onChange={(e) => setData('password_confirmation', e.target.value)}
            error={errors.password_confirmation}
            required
          />

          <Button type="submit" loading={processing} className="w-full">
            Reset Password
          </Button>
        </form>
      </div>
    </div>
  );
}
