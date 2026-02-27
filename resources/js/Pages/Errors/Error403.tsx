import { Head, Link } from '@inertiajs/react';

export default function Error403() {
    return (
        <>
            <Head title="403 - Access Denied" />
            <style>{`
                @keyframes shake {
                    0%, 100% { transform: translateX(0) rotate(0deg); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px) rotate(-1deg); }
                    20%, 40%, 60%, 80% { transform: translateX(5px) rotate(1deg); }
                }
                @keyframes pulse-slow {
                    0%, 100% { opacity: 0.3; }
                    50% { opacity: 0.6; }
                }
                @keyframes lock-bounce {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                }
                .animate-shake { animation: shake 0.8s ease-in-out; }
                .animate-lock-bounce { animation: lock-bounce 2s ease-in-out infinite; }
                .animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }
            `}</style>
            <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-orange-950 to-slate-900 px-4 relative overflow-hidden">
                {/* Animated background elements */}
                <div className="absolute inset-0 overflow-hidden">
                    <div className="absolute -top-40 -right-40 w-80 h-80 bg-orange-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
                    <div className="absolute -bottom-40 -left-40 w-80 h-80 bg-red-500/20 rounded-full blur-3xl animate-pulse-slow" style={{ animationDelay: '2s' }}></div>
                    <div className="absolute top-1/3 right-1/4 w-60 h-60 bg-amber-500/10 rounded-full blur-3xl animate-pulse-slow" style={{ animationDelay: '1s' }}></div>
                </div>

                {/* Security pattern overlay */}
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,146,60,0.05)_0%,transparent_50%)]"></div>
                <div className="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.02)_1px,transparent_1px)] bg-[size:50px_50px]"></div>

                <div className="relative z-10 text-center">
                    {/* Animated Lock Icon */}
                    <div className="animate-lock-bounce mb-8">
                        <div className="relative inline-flex items-center justify-center w-32 h-32 rounded-full bg-gradient-to-br from-orange-500/20 to-red-500/20 backdrop-blur-sm border border-orange-500/30">
                            <div className="absolute inset-2 rounded-full bg-gradient-to-br from-orange-600 to-red-600 shadow-lg shadow-orange-500/30"></div>
                            <svg className="relative h-14 w-14 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>

                    {/* Error code */}
                    <h1 className="text-8xl md:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-red-400 select-none">
                        403
                    </h1>

                    {/* Glassmorphism card */}
                    <div className="mt-8 backdrop-blur-xl bg-white/5 border border-white/10 rounded-3xl p-8 max-w-md mx-auto shadow-2xl">
                        <h2 className="text-2xl md:text-3xl font-bold text-white mb-3">Access Denied</h2>
                        <p className="text-gray-400 leading-relaxed">
                            You don't have the necessary permissions to access this resource. This area is protected.
                        </p>
                        
                        <div className="mt-6 p-4 rounded-2xl bg-orange-500/10 border border-orange-500/20">
                            <div className="flex items-center gap-3 text-left">
                                <div className="flex-shrink-0 w-10 h-10 rounded-xl bg-orange-500/20 flex items-center justify-center">
                                    <svg className="h-5 w-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-orange-300">Need access?</p>
                                    <p className="text-xs text-orange-400/70">Contact your system administrator.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            href="/dashboard"
                            className="group inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-orange-600 to-red-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-orange-500/30 hover:shadow-xl hover:shadow-orange-500/40 hover:scale-105 transition-all duration-300"
                        >
                            <svg className="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Back to Dashboard
                        </Link>
                        <button
                            onClick={() => window.history.back()}
                            className="group inline-flex items-center gap-2 rounded-xl backdrop-blur-sm bg-white/10 border border-white/20 px-8 py-4 text-sm font-semibold text-white hover:bg-white/20 hover:scale-105 transition-all duration-300"
                        >
                            <svg className="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Go Back
                        </button>
                    </div>
                </div>

                <div className="absolute bottom-8 text-center text-sm text-gray-500">
                    <p>Novation ERP - ERP Solution for Tunisian Businesses</p>
                </div>
            </div>
        </>
    );
}
