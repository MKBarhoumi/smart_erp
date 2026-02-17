import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

const SvgIcon = ({ d }: { d: string }) => (
    <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d={d} />
    </svg>
);

const reports = [
    { title: 'Revenue', description: 'Monthly and yearly revenue analysis', href: '/reports/revenue', icon: <SvgIcon d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />, color: 'text-blue-600 bg-blue-50' },
    { title: 'Tax Summary', description: 'VAT and stamp duty per quarter', href: '/reports/tax-summary', icon: <SvgIcon d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />, color: 'text-purple-600 bg-purple-50' },
    { title: 'Stamp Duty', description: 'Monthly stamp duty detail', href: '/reports/timbre', icon: <SvgIcon d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />, color: 'text-orange-600 bg-orange-50' },
    { title: 'Customer Aging', description: 'Customer receivables and aging balance', href: '/reports/customer-aging', icon: <SvgIcon d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />, color: 'text-red-600 bg-red-50' },
];

export default function Index() {
    return (
        <AuthenticatedLayout>
            <Head title="Reports" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Reports</h1>

                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {reports.map((report) => (
                        <Link key={report.href} href={report.href} className="group rounded-xl bg-white p-6 shadow-sm border border-gray-100 transition-all hover:shadow-lg hover:border-indigo-200 hover:-translate-y-0.5">
                            <div className={`inline-flex rounded-lg p-3 ${report.color}`}>
                                {report.icon}
                            </div>
                            <h2 className="mt-4 text-lg font-semibold text-gray-900 group-hover:text-indigo-600">{report.title}</h2>
                            <p className="mt-1 text-sm text-gray-500">{report.description}</p>
                        </Link>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
