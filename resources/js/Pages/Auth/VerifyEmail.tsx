import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/Button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { PageProps } from '@/types';

interface Props extends PageProps {
  status?: string;
}

export default function VerifyEmail({ status }: Props) {
  const { post, processing } = useForm({});

  const resend = () => {
    post('/email/verification-notification');
  };

  return (
    <AuthenticatedLayout>
      <Head title="Email Verification" />

      <div className="mx-auto max-w-lg space-y-6 rounded-xl bg-white p-8 shadow-lg">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Email Verification</h2>
          <p className="mt-2 text-sm text-gray-600">
            Thank you for signing up! Before getting started, please verify your email address by clicking on the link
            we just sent you. If you didn't receive the email, we'll send you another one.
          </p>
        </div>

        {status === 'verification-link-sent' && (
          <div className="rounded-lg bg-green-50 p-4 text-sm text-green-700">
            A new verification link has been sent to the email address you provided during registration.
          </div>
        )}

        <div className="flex items-center justify-between">
          <Button onClick={resend} loading={processing}>
            Resend Verification Email
          </Button>

          <Link
            href="/logout"
            method="post"
            as="button"
            className="text-sm text-gray-500 hover:text-gray-700"
          >
            Sign Out
          </Link>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
