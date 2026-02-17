import { Head, useForm, Link } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';

export default function ForgotPassword({ status }: { status?: string }) {
  const { data, setData, post, processing, errors } = useForm({
    email: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    post('/forgot-password');
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 py-12">
      <Head title="Forgot Password" />

      <div className="w-full max-w-md space-y-6 rounded-xl bg-white p-8 shadow-lg">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Forgot Password</h2>
          <p className="mt-2 text-sm text-gray-600">
            Enter your email address and we'll send you a link to reset your password.
          </p>
        </div>

        {status && (
          <div className="rounded-lg bg-green-50 p-4 text-sm text-green-700">
            {status}
          </div>
        )}

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

          <Button type="submit" loading={processing} className="w-full">
            Send Reset Link
          </Button>
        </form>

        <div className="text-center text-sm">
          <Link href="/login" className="text-indigo-600 hover:underline">
            Back to Sign In
          </Link>
        </div>
      </div>
    </div>
  );
}
