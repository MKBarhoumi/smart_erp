import { Head, Link } from '@inertiajs/react';

const FeatureIcon = ({ d }: { d: string }) => (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d={d} />
    </svg>
);

const features = [
    {
        title: 'TEIF v1.8.8 Compliant',
        desc: 'Full XML generation compliant with the Tunisian El Fatoora standard including all regulatory codes and validations.',
        icon: <FeatureIcon d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />,
        gradient: 'from-blue-500 to-cyan-500',
        bg: 'bg-blue-500/10',
    },
    {
        title: 'XAdES-BES Signature',
        desc: 'RSA-SHA256 digital signature with X.509 certificate integration and automatic TTN validation.',
        icon: <FeatureIcon d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />,
        gradient: 'from-indigo-500 to-purple-500',
        bg: 'bg-indigo-500/10',
    },
    {
        title: 'Multi-Tenant',
        desc: 'Full company settings, user roles, team management, and secure multi-tenant access control.',
        icon: <FeatureIcon d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 0h.008v.008h-.008v-.008z" />,
        gradient: 'from-emerald-500 to-teal-500',
        bg: 'bg-emerald-500/10',
    },
    {
        title: 'TND Precision',
        desc: 'Precise monetary calculations with bcmath library. Zero floating point, zero rounding errors - 3 decimal precision.',
        icon: <FeatureIcon d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />,
        gradient: 'from-amber-500 to-orange-500',
        bg: 'bg-amber-500/10',
    },
    {
        title: 'Reports & Analytics',
        desc: 'Interactive dashboards, monthly revenue reports, quarterly VAT returns, and customer balance tracking.',
        icon: <FeatureIcon d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />,
        gradient: 'from-purple-500 to-pink-500',
        bg: 'bg-purple-500/10',
    },
    {
        title: 'PDF & QR CEV',
        desc: 'Professional PDF invoicing with Electronic Verification Certificate QR code for instant validation.',
        icon: <FeatureIcon d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m0 0a48.159 48.159 0 0110.5 0m-10.5 0V3.375c0-.621.504-1.125 1.125-1.125h9.75c.621 0 1.125.504 1.125 1.125v3.659M9.75 8.25h.008v.008H9.75V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />,
        gradient: 'from-rose-500 to-red-500',
        bg: 'bg-rose-500/10',
    },
];

const stats = [
    { value: '99.9%', label: 'Uptime SLA' },
    { value: 'TEIF', label: 'v1.8.8 Compliant' },
    { value: '3', label: 'Decimal Precision' },
    { value: '24/7', label: 'Support' },
];

export default function Welcome() {
    return (
        <>
            <Head title="Novation ERP - Electronic Invoicing Tunisia" />

            <div className="min-h-screen bg-slate-950 text-white overflow-hidden">
                {/* Animated Background */}
                <div className="fixed inset-0 z-0">
                    <div className="absolute inset-0 bg-gradient-to-br from-slate-950 via-indigo-950/50 to-slate-950" />
                    <div className="absolute top-0 -left-40 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl animate-pulse" />
                    <div className="absolute top-1/3 -right-40 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl animate-pulse" style={{ animationDelay: '1s' }} />
                    <div className="absolute bottom-0 left-1/3 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl animate-pulse" style={{ animationDelay: '2s' }} />
                    {/* Grid Pattern */}
                    <div className="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.02)_1px,transparent_1px)] bg-[size:64px_64px]" />
                </div>

                {/* Header */}
                <header className="relative z-10 flex items-center justify-between px-6 lg:px-12 py-5">
                    <div className="flex items-center gap-3">
                        <div className="h-11 w-11 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-xl shadow-indigo-500/30">
                            <span className="text-white font-bold text-xl">N</span>
                        </div>
                        <span className="text-2xl font-bold tracking-tight">Nvo<span className="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">ERP</span></span>
                    </div>
                    <nav className="flex items-center gap-3">
                        <Link
                            href="/login"
                            className="px-5 py-2.5 text-sm font-medium text-gray-300 hover:text-white transition-colors"
                        >
                            Sign In
                        </Link>
                        <Link
                            href="/register"
                            className="group relative px-6 py-2.5 text-sm font-semibold text-white overflow-hidden rounded-xl"
                        >
                            <div className="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-600 transition-transform group-hover:scale-105" />
                            <div className="absolute inset-0 bg-gradient-to-r from-indigo-400 to-purple-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                            <span className="relative">Get Started</span>
                        </Link>
                    </nav>
                </header>

                {/* Hero Section */}
                <main className="relative z-10">
                    <section className="mx-auto max-w-6xl px-6 lg:px-12 pt-20 pb-32 text-center">
                        {/* Badge */}
                        <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 backdrop-blur-sm mb-8">
                            <span className="w-2 h-2 rounded-full bg-green-400 animate-pulse" />
                            <span className="text-sm font-medium text-gray-300">TEIF v1.8.8 Certified</span>
                        </div>

                        <h1 className="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight leading-[1.1]">
                            Electronic Invoicing
                            <br />
                            <span className="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">Made Simple</span>
                        </h1>

                        <p className="mx-auto mt-8 max-w-2xl text-lg lg:text-xl text-gray-400 leading-relaxed">
                            The complete SaaS solution for Tunisian businesses. El Fatoora (TTN) compatible with XAdES-BES digital signature and integrated CEV QR code verification.
                        </p>

                        {/* CTA Buttons */}
                        <div className="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link
                                href="/register"
                                className="group relative w-full sm:w-auto px-8 py-4 text-base font-semibold text-white overflow-hidden rounded-2xl"
                            >
                                <div className="absolute inset-0 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 transition-transform group-hover:scale-105" />
                                <div className="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400" />
                                <span className="relative flex items-center justify-center gap-2">
                                    Start Free Trial
                                    <svg className="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </span>
                            </Link>
                            <a
                                href="#features"
                                className="w-full sm:w-auto px-8 py-4 text-base font-semibold rounded-2xl border border-white/20 text-gray-300 hover:text-white hover:bg-white/5 hover:border-white/30 transition-all"
                            >
                                Explore Features
                            </a>
                        </div>

                        {/* Stats */}
                        <div className="mt-20 grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-8">
                            {stats.map((stat, idx) => (
                                <div key={idx} className="px-6 py-5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                                    <div className="text-3xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">{stat.value}</div>
                                    <div className="mt-1 text-sm text-gray-400">{stat.label}</div>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* Features Section */}
                    <section id="features" className="mx-auto max-w-6xl px-6 lg:px-12 py-24">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl sm:text-4xl font-bold">
                                Everything you need for{' '}
                                <span className="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">
                                    e-invoicing
                                </span>
                            </h2>
                            <p className="mt-4 text-lg text-gray-400 max-w-2xl mx-auto">
                                A complete platform built for Tunisian regulatory compliance with modern features for your business.
                            </p>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature, idx) => (
                                <div
                                    key={feature.title}
                                    className="group relative p-8 rounded-3xl bg-white/[0.03] border border-white/10 hover:border-white/20 transition-all duration-300 hover:-translate-y-1"
                                    style={{ animationDelay: `${idx * 100}ms` }}
                                >
                                    {/* Gradient glow on hover */}
                                    <div className={`absolute inset-0 rounded-3xl bg-gradient-to-br ${feature.gradient} opacity-0 group-hover:opacity-5 transition-opacity`} />
                                    
                                    <div className={`inline-flex rounded-2xl p-4 ${feature.bg}`}>
                                        <div className={`bg-gradient-to-br ${feature.gradient} bg-clip-text text-transparent`}>
                                            {feature.icon}
                                        </div>
                                    </div>
                                    <h3 className="mt-6 text-xl font-semibold text-white">{feature.title}</h3>
                                    <p className="mt-3 text-gray-400 leading-relaxed">{feature.desc}</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* CTA Section */}
                    <section className="mx-auto max-w-6xl px-6 lg:px-12 py-24">
                        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 p-12 lg:p-16 text-center">
                            {/* Background decoration */}
                            <div className="absolute top-0 left-0 w-64 h-64 bg-white/10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl" />
                            <div className="absolute bottom-0 right-0 w-64 h-64 bg-white/10 rounded-full translate-x-1/2 translate-y-1/2 blur-3xl" />
                            
                            <div className="relative">
                                <h2 className="text-3xl sm:text-4xl font-bold text-white">
                                    Ready to get started?
                                </h2>
                                <p className="mt-4 text-lg text-indigo-100 max-w-xl mx-auto">
                                    Join hundreds of Tunisian businesses using NvoERP for their electronic invoicing needs.
                                </p>
                                <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                                    <Link
                                        href="/register"
                                        className="w-full sm:w-auto px-8 py-4 text-base font-semibold rounded-2xl bg-white text-indigo-600 hover:bg-gray-100 transition-all shadow-xl"
                                    >
                                        Create Free Account
                                    </Link>
                                    <Link
                                        href="/login"
                                        className="w-full sm:w-auto px-8 py-4 text-base font-semibold rounded-2xl border-2 border-white/30 text-white hover:bg-white/10 transition-all"
                                    >
                                        Sign In
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                {/* Footer */}
                <footer className="relative z-10 border-t border-white/10">
                    <div className="mx-auto max-w-6xl px-6 lg:px-12 py-12">
                        <div className="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div className="flex items-center gap-3">
                                <div className="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                    <span className="text-white font-bold text-lg">N</span>
                                </div>
                                <span className="text-xl font-bold">NvoERP</span>
                            </div>
                            <div className="flex items-center gap-8 text-sm text-gray-400">
                                <a href="#" className="hover:text-white transition-colors">Privacy</a>
                                <a href="#" className="hover:text-white transition-colors">Terms</a>
                                <a href="#" className="hover:text-white transition-colors">Contact</a>
                            </div>
                            <p className="text-sm text-gray-500">
                                &copy; {new Date().getFullYear()} Novation ERP. All rights reserved.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
