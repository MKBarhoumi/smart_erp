import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

const RevenueIcon = () => (
    <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
    </svg>
);

const TaxIcon = () => (
    <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
    </svg>
);

const StampIcon = () => (
    <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z" />
    </svg>
);

const ClockIcon = () => (
    <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
);

const ArrowIcon = () => (
    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
    </svg>
);

const reports = [
    { 
        title: 'Revenue', 
        description: 'Monthly and yearly revenue analysis with trends and comparisons', 
        href: '/reports/revenue', 
        icon: <RevenueIcon />, 
        gradient: 'from-blue-500 to-blue-600',
        shadow: 'shadow-blue-500/25',
        hoverBg: 'group-hover:bg-blue-50'
    },
    { 
        title: 'Tax Summary', 
        description: 'VAT and stamp duty breakdown per quarter', 
        href: '/reports/tax-summary', 
        icon: <TaxIcon />, 
        gradient: 'from-violet-500 to-purple-600',
        shadow: 'shadow-violet-500/25',
        hoverBg: 'group-hover:bg-violet-50'
    },
    { 
        title: 'Stamp Duty', 
        description: 'Detailed monthly stamp duty reports', 
        href: '/reports/timbre', 
        icon: <StampIcon />, 
        gradient: 'from-orange-500 to-amber-500',
        shadow: 'shadow-orange-500/25',
        hoverBg: 'group-hover:bg-orange-50'
    },
    { 
        title: 'Customer Aging', 
        description: 'Receivables aging and balance analysis', 
        href: '/reports/customer-aging', 
        icon: <ClockIcon />, 
        gradient: 'from-rose-500 to-red-500',
        shadow: 'shadow-rose-500/25',
        hoverBg: 'group-hover:bg-rose-50'
    },
];

export default function Index() {
    return (
        <AuthenticatedLayout>
            <Head title="Reports" />

            <div className="space-y-8">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Reports</h1>
                    <p className="mt-1 text-sm text-gray-500">View and analyze your business data</p>
                </div>

                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4">
                    {reports.map((report) => (
                        <Link 
                            key={report.href} 
                            href={report.href} 
                            className={`group relative rounded-2xl bg-white p-6 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 overflow-hidden`}
                        >
                            <div className={`absolute inset-0 opacity-0 transition-opacity duration-300 ${report.hoverBg}`} />
                            <div className="relative">
                                <div className={`inline-flex rounded-xl p-3 bg-gradient-to-br ${report.gradient} text-white shadow-lg ${report.shadow}`}>
                                    {report.icon}
                                </div>
                                <h2 className="mt-5 text-lg font-semibold text-gray-900 group-hover:text-gray-900">{report.title}</h2>
                                <p className="mt-2 text-sm text-gray-500 leading-relaxed">{report.description}</p>
                                <div className="mt-4 flex items-center text-sm font-medium text-gray-400 group-hover:text-indigo-600 transition-colors">
                                    <span>View report</span>
                                    <span className="ml-2 transition-transform group-hover:translate-x-1">
                                        <ArrowIcon />
                                    </span>
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
