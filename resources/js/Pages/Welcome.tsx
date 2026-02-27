import { Head, Link } from '@inertiajs/react';

const FeatureIcon = ({ d }: { d: string }) => (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d={d} />
    </svg>
);

const features = [
    {
        title: 'TEIF v1.8.8',
        desc: 'XML generation compliant with the Tunisian El Fatoora standard with all regulatory codes.',
        icon: <FeatureIcon d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />,
        color: 'text-blue-600 bg-blue-100',
    },
    {
        title: 'XAdES-BES Signature',
        desc: 'RSA-SHA256 digital signature with X.509 certificate and automatic TTN validation.',
        icon: <FeatureIcon d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />,
        color: 'text-indigo-600 bg-indigo-100',
    },
    {
        title: 'Company Management',
        desc: 'Full company settings, user roles, and secure access management.',
        icon: <FeatureIcon d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 0h.008v.008h-.008v-.008z" />,
        color: 'text-emerald-600 bg-emerald-100',
    },
    {
        title: 'TND 3 Decimals',
        desc: 'Precise monetary calculations with bcmath. Zero float, zero rounding errors.',
        icon: <FeatureIcon d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />,
        color: 'text-amber-600 bg-amber-100',
    },
    {
        title: 'Reports & Analytics',
        desc: 'Dashboards, monthly revenue, quarterly VAT returns, customer balance.',
        icon: <FeatureIcon d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />,
        color: 'text-purple-600 bg-purple-100',
    },
    {
        title: 'PDF & QR CEV',
        desc: 'Professional PDF invoicing with Electronic Verification Certificate QR code.',
        icon: <FeatureIcon d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m0 0a48.159 48.159 0 0110.5 0m-10.5 0V3.375c0-.621.504-1.125 1.125-1.125h9.75c.621 0 1.125.504 1.125 1.125v3.659M9.75 8.25h.008v.008H9.75V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />,
        color: 'text-rose-600 bg-rose-100',
    },
];

export default function Welcome() {
    return (
        <>
            <Head title="Novation ERP - Electronic Invoicing Tunisia" />

            <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
                <header className="flex items-center justify-between px-8 py-4">
                    <div className="flex items-center gap-2">
                        <div className="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-600/20">
                            <span className="text-white font-bold text-lg">N</span>
                        </div>
                        <span className="text-xl font-bold text-gray-900 tracking-tight">Nvo<span className="text-indigo-600">ERP</span></span>
                    </div>
                    <nav className="flex items-center gap-4">
                        <Link
                            href="/login"
                            className="text-sm font-medium text-gray-700 hover:text-indigo-600 transition-colors"
                        >
                            Sign In
                        </Link>
                        <Link
                            href="/register"
                            className="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20"
                        >
                            Free Trial
                        </Link>
                    </nav>
                </header>

                <main className="mx-auto max-w-5xl px-8 py-20 text-center">
                    <h1 className="text-5xl font-extrabold tracking-tight text-gray-900 sm:text-6xl">
                        Electronic Invoicing
                        <br />
                        <span className="bg-gradient-to-r from-indigo-600 to-blue-500 bg-clip-text text-transparent">TEIF Compliant</span>
                    </h1>

                    <p className="mx-auto mt-6 max-w-2xl text-lg text-gray-600 leading-relaxed">
                        Complete SaaS electronic invoicing solution for Tunisian businesses.
                        El Fatoora (TTN) compatible, XAdES-BES signature, and integrated CEV QR code.
                    </p>

                    <div className="mt-10 flex items-center justify-center gap-4">
                        <Link
                            href="/register"
                            className="rounded-xl bg-indigo-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-indigo-600/25 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all"
                        >
                            Get Started
                        </Link>
                        <a
                            href="#features"
                            className="rounded-xl border border-gray-300 px-8 py-3.5 text-base font-semibold text-gray-700 hover:bg-white hover:border-gray-400 transition-all"
                        >
                            Learn More
                        </a>
                    </div>

                    <div id="features" className="mt-24 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {features.map((feature) => (
                            <div
                                key={feature.title}
                                className="rounded-2xl bg-white/80 backdrop-blur-sm p-6 shadow-sm border border-white/60 hover:shadow-lg hover:-translate-y-1 transition-all text-left"
                            >
                                <div className={`inline-flex rounded-xl p-3 ${feature.color}`}>
                                    {feature.icon}
                                </div>
                                <h3 className="mt-4 font-semibold text-gray-900">{feature.title}</h3>
                                <p className="mt-2 text-sm text-gray-600 leading-relaxed">{feature.desc}</p>
                            </div>
                        ))}
                    </div>
                </main>

                <footer className="py-8 text-center text-sm text-gray-500">
                    &copy; {new Date().getFullYear()} Novation ERP. All rights reserved.
                </footer>
            </div>
        </>
    );
}
