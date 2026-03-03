import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

const reports = [
    { 
        title: 'Revenue', 
        description: 'Monthly and yearly revenue analysis with trends and comparisons', 
        href: '/reports/revenue', 
        gradient: 'from-user-500 to-indigo-600',
        shadow: 'shadow-user-500/25',
        hoverBg: 'group-hover:bg-user-50',
        icon: (
            <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
            </svg>
        )
    },
    { 
        title: 'Tax Summary', 
        description: 'VAT and stamp duty breakdown per quarter', 
        href: '/reports/tax-summary', 
        gradient: 'from-violet-500 to-purple-600',
        shadow: 'shadow-violet-500/25',
        hoverBg: 'group-hover:bg-violet-50',
        icon: (
            <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
        )
    },
    { 
        title: 'Stamp Duty', 
        description: 'Detailed monthly stamp duty reports', 
        href: '/reports/timbre', 
        gradient: 'from-emerald-500 to-green-600',
        shadow: 'shadow-emerald-500/25',
        hoverBg: 'group-hover:bg-emerald-50',
        icon: (
            <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z" />
            </svg>
        )
    },
    { 
        title: 'Customer Statement', 
        description: 'Detailed customer transaction statements', 
        href: '/reports/customer-statement', 
        gradient: 'from-amber-500 to-orange-500',
        shadow: 'shadow-amber-500/25',
        hoverBg: 'group-hover:bg-amber-50',
        icon: (
            <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        )
    },
    { 
        title: 'Customer Aging', 
        description: 'Receivables aging and balance analysis', 
        href: '/reports/customer-aging', 
        gradient: 'from-rose-500 to-red-500',
        shadow: 'shadow-rose-500/25',
        hoverBg: 'group-hover:bg-rose-50',
        icon: (
            <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        )
    },
];

export default function Index() {
    return (
        <AuthenticatedLayout>
            <Head title="Reports" />

            <div className="space-y-8">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-xl bg-gradient-to-br from-user-600 to-indigo-500 text-white shadow-lg shadow-user-500/25">
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" /></svg>
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight text-gray-900">Reports</h1>
                            <p className="text-gray-500">View and analyze your business data</p>
                        </div>
                    </div>
                </div>

                {/* Report Cards */}
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
                    {reports.map((report) => (
                        <Link 
                            key={report.href} 
                            href={report.href} 
                            className={`group relative rounded-2xl bg-white p-6 shadow-soft border border-gray-100 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 overflow-hidden`}
                        >
                            <div className={`absolute inset-0 opacity-0 transition-opacity duration-300 ${report.hoverBg}`} />
                            <div className="relative">
                                <div className={`inline-flex rounded-xl p-3 bg-gradient-to-br ${report.gradient} text-white shadow-lg ${report.shadow}`}>
                                    {report.icon}
                                </div>
                                <h2 className="mt-5 text-lg font-semibold text-gray-900 group-hover:text-gray-900">{report.title}</h2>
                                <p className="mt-2 text-sm text-gray-500 leading-relaxed">{report.description}</p>
                                <div className="mt-4 flex items-center text-sm font-medium text-gray-400 group-hover:text-user-600 transition-colors">
                                    <span>View report</span>
                                    <span className="ml-2 transition-transform group-hover:translate-x-1">
                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>

                {/* Quick Tips */}
                <div className="bg-gradient-to-r from-user-50 to-indigo-50 rounded-2xl border border-user-200/50 p-6">
                    <div className="flex items-start gap-4">
                        <div className="p-2 rounded-xl bg-user-100">
                            <svg className="w-6 h-6 text-user-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" /></svg>
                        </div>
                        <div>
                            <h3 className="font-semibold text-user-800">Pro Tip</h3>
                            <p className="mt-1 text-sm text-user-700">All reports can be exported to PDF for sharing with your accountant or for record keeping. Look for the download button in each report.</p>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
