import { Head, Link } from '@inertiajs/react';

export default function Error419() {
    return (
        <>
            <Head title="419 - Session Expired" />
            <style>{`
                @keyframes spin-slow {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                @keyframes pulse-ring {
                    0% { transform: scale(0.8); opacity: 1; }
                    100% { transform: scale(2); opacity: 0; }
                }
                @keyframes pulse-slow {
                    0%, 100% { opacity: 0.3; }
                    50% { opacity: 0.6; }
                }
                @keyframes countdown {
                    0%, 100% { stroke-dashoffset: 0; }
                    50% { stroke-dashoffset: 283; }
                }
                .animate-spin-slow { animation: spin-slow 20s linear infinite; }
                .animate-pulse-ring { animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
                .animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }
            `}</style>
            <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-amber-950 to-slate-900 px-4 relative overflow-hidden">
                {/* Animated background elements */}
                <div className="absolute inset-0 overflow-hidden">
                    <div className="absolute -top-40 -right-40 w-80 h-80 bg-yellow-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
                    <div className="absolute -bottom-40 -left-40 w-80 h-80 bg-amber-500/20 rounded-full blur-3xl animate-pulse-slow" style={{ animationDelay: '2s' }}></div>
                    <div className="absolute top-1/4 left-1/4 w-60 h-60 bg-orange-500/10 rounded-full blur-3xl animate-pulse-slow" style={{ animationDelay: '1s' }}></div>
                </div>

                {/* Rotating clock pattern */}
                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div className="w-[600px] h-[600px] rounded-full border border-amber-500/10 animate-spin-slow"></div>
                    <div className="absolute w-[500px] h-[500px] rounded-full border border-amber-500/10 animate-spin-slow" style={{ animationDirection: 'reverse', animationDuration: '30s' }}></div>
                </div>

                <div className="relative z-10 text-center">
                    {/* Animated Clock Icon */}
                    <div className="relative inline-flex items-center justify-center mb-8">
                        <div className="absolute w-40 h-40 rounded-full bg-amber-500/20 animate-pulse-ring"></div>
                        <div className="relative w-32 h-32 rounded-full bg-gradient-to-br from-amber-500/20 to-yellow-500/20 backdrop-blur-sm border border-amber-500/30 flex items-center justify-center">
                            <div className="absolute inset-3 rounded-full bg-gradient-to-br from-amber-600 to-yellow-600 shadow-lg shadow-amber-500/30"></div>
                            <svg className="relative h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    {/* Error code */}
                    <h1 className="text-8xl md:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-yellow-400 select-none">
                        419
                    </h1>

                    {/* Glassmorphism card */}
                    <div className="mt-8 backdrop-blur-xl bg-white/5 border border-white/10 rounded-3xl p-8 max-w-md mx-auto shadow-2xl">
                        <h2 className="text-2xl md:text-3xl font-bold text-white mb-3">Session Expired</h2>
                        <p className="text-gray-400 leading-relaxed">
                            Your session has expired for security reasons. Please log in again to continue safely.
                        </p>
                        
                        <div className="mt-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20">
                            <div className="flex items-center gap-3 text-left">
                                <div className="flex-shrink-0 w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                                    <svg className="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-amber-300">Why does this happen?</p>
                                    <p className="text-xs text-amber-400/70">Sessions expire after inactivity for your security.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            href="/login"
                            className="group inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 hover:shadow-xl hover:shadow-amber-500/40 hover:scale-105 transition-all duration-300"
                        >
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Log In Again
                        </Link>
                        <button
                            onClick={() => window.location.reload()}
                            className="group inline-flex items-center gap-2 rounded-xl backdrop-blur-sm bg-white/10 border border-white/20 px-8 py-4 text-sm font-semibold text-white hover:bg-white/20 hover:scale-105 transition-all duration-300"
                        >
                            <svg className="h-5 w-5 transition-transform group-hover:rotate-180 duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>

                <div className="absolute bottom-8 text-center text-sm text-gray-500">
                    <p>Smart ERP Lite - ERP Solution for Tunisian Businesses</p>
                </div>
            </div>
        </>
    );
}
