import { Head } from '@inertiajs/react';

export default function Error503() {
    return (
        <>
            <Head title="503 - Service Unavailable" />
            <style>{`
                @keyframes spin-slow {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                @keyframes pulse-slow {
                    0%, 100% { opacity: 0.3; }
                    50% { opacity: 0.6; }
                }
                @keyframes bounce-dots {
                    0%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-15px); }
                }
                @keyframes gear-spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                .animate-spin-slow { animation: spin-slow 10s linear infinite; }
                .animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }
                .animate-gear { animation: gear-spin 3s linear infinite; }
                .animate-bounce-1 { animation: bounce-dots 1.4s ease-in-out infinite; animation-delay: 0s; }
                .animate-bounce-2 { animation: bounce-dots 1.4s ease-in-out infinite; animation-delay: 0.2s; }
                .animate-bounce-3 { animation: bounce-dots 1.4s ease-in-out infinite; animation-delay: 0.4s; }
            `}</style>
            <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 px-4 relative overflow-hidden">
                {/* Animated background elements */}
                <div className="absolute inset-0 overflow-hidden">
                    <div className="absolute -top-40 -right-40 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
                    <div className="absolute -bottom-40 -left-40 w-80 h-80 bg-cyan-500/20 rounded-full blur-3xl animate-pulse-slow" style={{ animationDelay: '2s' }}></div>
                    <div className="absolute top-1/3 right-1/3 w-60 h-60 bg-indigo-500/10 rounded-full blur-3xl animate-pulse-slow" style={{ animationDelay: '1s' }}></div>
                </div>

                {/* Rotating gear pattern */}
                <div className="absolute inset-0 flex items-center justify-center pointer-events-none opacity-10">
                    <div className="absolute animate-gear" style={{ animationDirection: 'reverse' }}>
                        <svg className="w-96 h-96 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 15.5A3.5 3.5 0 0 1 8.5 12 3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5 3.5 3.5 0 0 1-3.5 3.5m7.43-2.53c.04-.32.07-.64.07-.97 0-.33-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.31-.61-.22l-2.49 1c-.52-.39-1.06-.73-1.69-.98l-.37-2.65A.506.506 0 0 0 14 2h-4c-.25 0-.46.18-.5.42l-.37 2.65c-.63.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.34-.07.67-.07 1 0 .33.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.06.74 1.69.99l.37 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.37-2.65c.63-.26 1.17-.59 1.69-.99l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.66z"/>
                        </svg>
                    </div>
                </div>

                <div className="relative z-10 text-center">
                    {/* Animated Gear Icon */}
                    <div className="mb-8 inline-flex items-center justify-center">
                        <div className="relative w-32 h-32 rounded-full bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-sm border border-blue-500/30 flex items-center justify-center">
                            <div className="absolute inset-3 rounded-full bg-gradient-to-br from-blue-600 to-cyan-600 shadow-lg shadow-blue-500/30"></div>
                            <svg className="relative h-14 w-14 text-white animate-gear" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>

                    {/* Error code */}
                    <h1 className="text-8xl md:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400 select-none">
                        503
                    </h1>

                    {/* Glassmorphism card */}
                    <div className="mt-8 backdrop-blur-xl bg-white/5 border border-white/10 rounded-3xl p-8 max-w-md mx-auto shadow-2xl">
                        <h2 className="text-2xl md:text-3xl font-bold text-white mb-3">Maintenance in Progress</h2>
                        <p className="text-gray-400 leading-relaxed">
                            The service is temporarily unavailable for maintenance. We're working to improve your experience.
                        </p>
                        
                        {/* Loading dots */}
                        <div className="mt-6 flex items-center justify-center gap-2">
                            <div className="w-4 h-4 rounded-full bg-gradient-to-r from-blue-400 to-cyan-400 animate-bounce-1"></div>
                            <div className="w-4 h-4 rounded-full bg-gradient-to-r from-blue-400 to-cyan-400 animate-bounce-2"></div>
                            <div className="w-4 h-4 rounded-full bg-gradient-to-r from-blue-400 to-cyan-400 animate-bounce-3"></div>
                        </div>

                        <div className="mt-6 p-4 rounded-2xl bg-blue-500/10 border border-blue-500/20">
                            <div className="flex items-center gap-3 text-left">
                                <div className="flex-shrink-0 w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center">
                                    <svg className="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-blue-300">Work in Progress</p>
                                    <p className="text-xs text-blue-400/70">Estimated time: a few minutes</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8">
                        <button
                            onClick={() => window.location.reload()}
                            className="group inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 hover:scale-105 transition-all duration-300"
                        >
                            <svg className="h-5 w-5 transition-transform group-hover:rotate-180 duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Refresh Page
                        </button>
                    </div>

                    <div className="mt-8 text-sm text-gray-500">
                        <p>For emergencies, contact:</p>
                        <p className="mt-1 font-medium text-blue-400">support@novationerp.tn</p>
                    </div>
                </div>

                <div className="absolute bottom-8 text-center text-sm text-gray-500">
                    <p>Novation ERP - ERP Solution for Tunisian Businesses</p>
                </div>
            </div>
        </>
    );
}
