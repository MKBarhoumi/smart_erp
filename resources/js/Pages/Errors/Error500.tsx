import { Head, Link } from '@inertiajs/react';

export default function Error500() {
    return (
        <>
            <Head title="500 - Server Error" />
            <style>{`
                @keyframes glitch {
                    0%, 100% { text-shadow: 0 0 0 transparent; }
                    20% { text-shadow: -2px 0 #ff0040, 2px 0 #00ffff; }
                    40% { text-shadow: 2px 0 #ff0040, -2px 0 #00ffff; }
                    60% { text-shadow: -1px 0 #ff0040, 1px 0 #00ffff; }
                    80% { text-shadow: 1px 0 #ff0040, -1px 0 #00ffff; }
                }
                @keyframes pulse-slow {
                    0%, 100% { opacity: 0.3; }
                    50% { opacity: 0.6; }
                }
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-5px); }
                    75% { transform: translateX(5px); }
                }
                @keyframes warning-pulse {
                    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
                    50% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
                }
                .animate-glitch { animation: glitch 1s ease-in-out infinite; }
                .animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }
                .animate-shake { animation: shake 0.5s ease-in-out infinite; }
                .animate-warning-pulse { animation: warning-pulse 2s ease-in-out infinite; }
            `}</style>
            <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-red-950 to-slate-900 px-4 relative overflow-hidden">
                {/* Animated background elements */}
                <div className="absolute inset-0 overflow-hidden">
                    <div className="absolute -top-40 -right-40 w-80 h-80 bg-red-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
                    <div className="absolute -bottom-40 -left-40 w-80 h-80 bg-rose-500/20 rounded-full blur-3xl animate-pulse-slow" style={{ animationDelay: '2s' }}></div>
                    <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-red-500/10 rounded-full blur-3xl animate-pulse-slow" style={{ animationDelay: '1s' }}></div>
                </div>

                {/* Grid pattern with red tint */}
                <div className="absolute inset-0 bg-[linear-gradient(rgba(239,68,68,.03)_1px,transparent_1px),linear-gradient(90deg,rgba(239,68,68,.03)_1px,transparent_1px)] bg-[size:50px_50px]"></div>

                <div className="relative z-10 text-center">
                    {/* Animated Warning Icon */}
                    <div className="mb-8 inline-flex items-center justify-center">
                        <div className="relative w-32 h-32 rounded-full bg-gradient-to-br from-red-500/20 to-rose-500/20 backdrop-blur-sm border border-red-500/30 flex items-center justify-center animate-warning-pulse">
                            <div className="absolute inset-3 rounded-full bg-gradient-to-br from-red-600 to-rose-600 shadow-lg shadow-red-500/30"></div>
                            <svg className="relative h-14 w-14 text-white animate-shake" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>

                    {/* Glitching error code */}
                    <h1 className="text-8xl md:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-rose-400 select-none animate-glitch">
                        500
                    </h1>

                    {/* Glassmorphism card */}
                    <div className="mt-8 backdrop-blur-xl bg-white/5 border border-white/10 rounded-3xl p-8 max-w-md mx-auto shadow-2xl">
                        <h2 className="text-2xl md:text-3xl font-bold text-white mb-3">Server Error</h2>
                        <p className="text-gray-400 leading-relaxed">
                            An internal error has occurred. Our technical team has been notified and is working to resolve the issue.
                        </p>
                        
                        <div className="mt-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20">
                            <div className="flex items-center gap-3 text-left">
                                <div className="flex-shrink-0 w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center">
                                    <svg className="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-red-300">Error Code: 500</p>
                                    <p className="text-xs text-red-400/70">Internal Server Error</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            href="/dashboard"
                            className="group inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-red-500/30 hover:shadow-xl hover:shadow-red-500/40 hover:scale-105 transition-all duration-300"
                        >
                            <svg className="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Back to Dashboard
                        </Link>
                        <button
                            onClick={() => window.location.reload()}
                            className="group inline-flex items-center gap-2 rounded-xl backdrop-blur-sm bg-white/10 border border-white/20 px-8 py-4 text-sm font-semibold text-white hover:bg-white/20 hover:scale-105 transition-all duration-300"
                        >
                            <svg className="h-5 w-5 transition-transform group-hover:rotate-180 duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Try Again
                        </button>
                    </div>

                    <div className="mt-8 text-sm text-gray-500">
                        <p>If the problem persists, contact:</p>
                        <p className="mt-1 font-medium text-red-400">support@novationerp.tn</p>
                    </div>
                </div>

                <div className="absolute bottom-8 text-center text-sm text-gray-500">
                    <p>Novation ERP - ERP Solution for Tunisian Businesses</p>
                </div>
            </div>
        </>
    );
}
