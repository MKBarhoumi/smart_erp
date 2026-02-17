import { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }: PropsWithChildren) {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="mb-8">
        <Link href="/" className="text-3xl font-bold text-indigo-600">
          Smart ERP Lite
        </Link>
        <p className="mt-1 text-center text-sm text-gray-500">
          SaaS ERP for Tunisian Businesses
        </p>
      </div>
      <div className="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        {children}
      </div>
    </div>
  );
}
