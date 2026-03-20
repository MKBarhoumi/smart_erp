import { Link, router, usePage } from '@inertiajs/react';
import type { PropsWithChildren, ReactNode } from 'react';
import { useState, useEffect } from 'react';
import { FlashMessages } from '@/Components/ui/Toast';
import NotificationDropdown from '@/Components/NotificationDropdown';
import type { PageProps } from '@/types';

interface NavItem {
  name: string;
  href: string;
  icon: ReactNode;
  roles?: string[];
  adminOnly?: boolean;
}

const Icon = ({ d, className = 'w-5 h-5' }: { d: string; className?: string }) => (
  <svg className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
    <path strokeLinecap="round" strokeLinejoin="round" d={d} />
  </svg>
);

const navigation: NavItem[] = [
  { name: 'Dashboard', href: '/dashboard', icon: <Icon d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" /> },
  { name: 'Invoices', href: '/invoices', icon: <Icon d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />, roles: ['super_admin', 'admin', 'accountant', 'sales'] },
  { name: 'OldInvoices', href: '/oldinvoices', icon: <Icon d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />, roles: ['super_admin', 'admin', 'accountant', 'sales'] },
  { name: 'Customers', href: '/customers', icon: <Icon d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />, roles: ['super_admin', 'admin', 'accountant', 'sales'] },
  { name: 'Products', href: '/products', icon: <Icon d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />, roles: ['super_admin', 'admin', 'accountant', 'inventory_manager'] },
  { name: 'Services', href: '/services', icon: <Icon d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />, roles: ['super_admin', 'admin', 'accountant', 'sales'] },
  { name: 'Payments', href: '/payments', icon: <Icon d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />, roles: ['super_admin', 'admin', 'accountant', 'sales'] },
  { name: 'Inventory', href: '/inventory', icon: <Icon d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />, roles: ['super_admin', 'admin', 'inventory_manager'] },
  { name: 'Reports', href: '/reports', icon: <Icon d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />, roles: ['super_admin', 'admin', 'accountant', 'viewer'] },
  { name: 'My Account', href: '/settings/profile', icon: <Icon d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /> },
  { name: 'Settings', href: '/settings', icon: <Icon d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />, roles: ['admin', 'super_admin'] },
  { name: 'Users', href: '/admin/users', icon: <Icon d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />, roles: ['admin', 'super_admin'], adminOnly: true },
  { name: 'Audit Log', href: '/admin/audit-log', icon: <Icon d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />, roles: ['admin', 'super_admin'], adminOnly: true },
  { name: 'Profile Manager', href: '/admin/profiles', icon: <Icon d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />, roles: ['admin', 'super_admin'], adminOnly: true },
];

export default function AuthenticatedLayout({ children }: PropsWithChildren) {
  const { auth, flash } = usePage<PageProps>().props;
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
  const isAdmin = auth.user.role === 'admin' || auth.user.role === 'super_admin';
  const isAdminPage = currentPath.startsWith('/admin');

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 10);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  // Theme - STRONG differentiation: Admin=Orange, User=Blue
  const theme = isAdmin && isAdminPage ? {
    sidebar: 'bg-gradient-to-b from-admin-950 via-admin-900 to-slate-950',
    sidebarBorder: 'border-admin-800/50',
    logoGradient: 'from-admin-500 to-red-500',
    logoAccent: 'text-admin-400',
    activeNav: 'bg-gradient-to-r from-admin-600 to-admin-500 text-white shadow-lg shadow-admin-500/30',
    hoverNav: 'hover:bg-admin-800/60 hover:text-white',
    headerBg: scrolled ? 'bg-admin-50/95 backdrop-blur-lg shadow-lg shadow-admin-200/20' : 'bg-gradient-to-r from-admin-50 to-orange-50/50',
    badge: 'bg-gradient-to-r from-admin-500 to-red-500 text-white',
    searchFocus: 'focus:border-admin-500 focus:ring-admin-500/30',
    searchBg: 'bg-admin-50/50 focus:bg-white',
    contentBg: 'bg-gradient-to-br from-orange-50/30 via-white to-red-50/20',
    textMuted: 'text-admin-600/70',
  } : {
    sidebar: 'bg-gradient-to-b from-slate-950 via-user-950 to-slate-950',
    sidebarBorder: 'border-slate-800',
    logoGradient: 'from-user-500 to-purple-500',
    logoAccent: 'text-user-400',
    activeNav: 'bg-gradient-to-r from-user-600 to-user-500 text-white shadow-lg shadow-user-500/30',
    hoverNav: 'hover:bg-slate-800/60 hover:text-white',
    headerBg: scrolled ? 'bg-white/95 backdrop-blur-lg shadow-lg' : 'bg-white',
    badge: 'bg-user-100 text-user-700',
    searchFocus: 'focus:border-user-500 focus:ring-user-500/30',
    searchBg: 'bg-gray-50/50 focus:bg-white',
    contentBg: 'bg-gradient-to-br from-slate-50/50 via-white to-user-50/20',
    textMuted: 'text-gray-500',
  };

  return (
    <div className={`min-h-screen flex ${theme.contentBg}`}>
      {/* Sidebar */}
      <aside className={`fixed inset-y-0 left-0 z-50 w-72 ${theme.sidebar} transform transition-all duration-300 ease-out lg:translate-x-0 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
        <div className={`flex items-center h-20 px-6 border-b ${theme.sidebarBorder}`}>
          <Link href="/dashboard" className="flex items-center gap-4 group">
            <div className={`w-11 h-11 rounded-2xl bg-gradient-to-br ${theme.logoGradient} flex items-center justify-center shadow-xl group-hover:shadow-2xl transition-all duration-300 group-hover:scale-105`}>
              <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
              </svg>
            </div>
            <div>
              <span className="text-2xl font-bold text-white tracking-tight">Nov<span className={theme.logoAccent}>ERP</span></span>
              {isAdmin && isAdminPage && (
                <div className="flex items-center gap-1.5 mt-0.5">
                  <span className="relative flex h-2 w-2">
                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-admin-400 opacity-75"></span>
                    <span className="relative inline-flex rounded-full h-2 w-2 bg-admin-500"></span>
                  </span>
                  <span className="text-[11px] font-semibold text-admin-400 uppercase tracking-widest">Admin Mode</span>
                </div>
              )}
            </div>
          </Link>
        </div>

        {isAdmin && (
          <div className={`mx-4 mt-4 px-4 py-3 rounded-xl ${isAdminPage ? 'bg-gradient-to-r from-admin-500/20 to-red-500/20 border border-admin-500/30' : 'bg-slate-800/50 border border-slate-700/50'}`}>
            <div className="flex items-center gap-3">
              <div className={`p-2 rounded-lg ${isAdminPage ? 'bg-admin-500/20' : 'bg-slate-700/50'}`}>
                <svg className={`w-5 h-5 ${isAdminPage ? 'text-admin-400' : 'text-slate-400'}`} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
              </div>
              <div>
                <span className={`text-xs font-bold ${isAdminPage ? 'text-admin-300' : 'text-slate-300'}`}>{auth.user.role.replace('_', ' ').toUpperCase()}</span>
                <p className="text-[11px] text-slate-400">Full access granted</p>
              </div>
            </div>
          </div>
        )}

        <nav className="mt-6 px-4 space-y-1.5 overflow-y-auto scrollbar-thin max-h-[calc(100vh-280px)]">
          {navigation.filter((item) => !item.roles || item.roles.includes(auth.user.role)).map((item, idx) => {
            const isActive = currentPath === item.href || (item.href !== '/dashboard' && currentPath.startsWith(item.href));
            const isAdminItem = item.adminOnly;
            return (
              <Link key={item.name} href={item.href} className={`flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ease-out group ${isActive ? isAdminItem ? 'bg-gradient-to-r from-admin-600 to-admin-500 text-white shadow-lg shadow-admin-500/30' : theme.activeNav : `text-slate-300 ${isAdminItem ? 'hover:bg-admin-800/50 hover:text-white' : theme.hoverNav}`}`}>
                <span className={`shrink-0 transition-transform duration-200 group-hover:scale-110 ${isActive ? 'text-white' : ''}`}>{item.icon}</span>
                <span className="flex-1">{item.name}</span>
                {isAdminItem && <span className={`px-2 py-0.5 rounded-md text-[10px] font-bold ${isActive ? 'bg-white/20 text-white' : 'bg-admin-500/20 text-admin-400'}`}>ADMIN</span>}
                {isActive && <span className="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />}
              </Link>
            );
          })}
        </nav>

        <div className={`absolute bottom-0 left-0 right-0 p-4 border-t ${theme.sidebarBorder} bg-slate-950/50 backdrop-blur-sm`}>
          <div className="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 hover:bg-slate-800/80 transition-colors">
            <div className={`w-11 h-11 rounded-xl flex items-center justify-center text-sm font-bold shadow-lg ${isAdmin ? 'bg-gradient-to-br from-admin-500 to-red-500 text-white' : 'bg-gradient-to-br from-user-500 to-purple-500 text-white'}`}>{auth.user.name.charAt(0).toUpperCase()}</div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-white truncate">{auth.user.name}</p>
              <p className="text-xs text-slate-400 truncate">{auth.user.email}</p>
            </div>
            <Link href="/logout" method="post" as="button" className="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/50 transition-colors" title="Sign Out">
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
            </Link>
          </div>
        </div>
      </aside>

      {sidebarOpen && <div className="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden animate-fade-in" onClick={() => setSidebarOpen(false)} />}

      <div className="flex-1 flex flex-col min-w-0 lg:ml-72">
        <header className={`sticky top-0 z-30 h-20 ${theme.headerBg} border-b border-gray-200/50 flex items-center justify-between px-4 lg:px-8 transition-all duration-300`}>
          <button onClick={() => setSidebarOpen(!sidebarOpen)} className="lg:hidden p-2.5 rounded-xl text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-all active:scale-95">
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" /></svg>
          </button>

          <div className="hidden md:flex flex-1 max-w-lg mx-4">
            <div className="relative w-full group">
              <svg className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 group-focus-within:text-user-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              <input type="text" placeholder="Search invoices, customers, products..." className={`w-full rounded-xl border-0 ${theme.searchBg} pl-12 pr-4 py-3 text-sm ring-1 ring-gray-200 ${theme.searchFocus} focus:ring-2 transition-all duration-200`} onKeyDown={(e) => { if (e.key === 'Enter') { const q = (e.target as HTMLInputElement).value; if (q.trim()) { if (currentPath.startsWith('/oldinvoices')) router.get('/oldinvoices', { search: q }); else if (currentPath.startsWith('/customers')) router.get('/customers', { search: q }); else if (currentPath.startsWith('/products')) router.get('/products', { search: q }); else if (currentPath.startsWith('/payments')) router.get('/payments', { search: q }); else if (currentPath.startsWith('/inventory')) router.get('/inventory', { search: q }); else router.get('/customers', { search: q }); }}}} />
              <kbd className="absolute right-4 top-1/2 -translate-y-1/2 hidden sm:inline-flex items-center gap-1 px-2 py-1 text-xs text-gray-400 bg-gray-100 rounded-md font-mono">Ctrl+K</kbd>
            </div>
          </div>

          <div className="flex items-center gap-3 ml-auto">
            <NotificationDropdown />
            <div className="hidden sm:block h-8 w-px bg-gray-200" />
            <div className="flex items-center gap-4">
              <div className="text-right hidden sm:block">
                <p className="text-sm font-semibold text-gray-900">{auth.user.name}</p>
                <p className={`text-xs ${theme.textMuted}`}>{auth.user.email}</p>
              </div>
              <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold shadow-sm ${theme.badge}`}>
                {isAdmin && <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>}
                {auth.user.role.replace('_', ' ')}
              </span>
            </div>
          </div>
        </header>

        <FlashMessages flash={flash} />
        <main className="flex-1 p-4 lg:p-8 animate-fade-in">{children}</main>
        <footer className="px-8 py-4 border-t border-gray-100 bg-white/50">
          <div className="flex items-center justify-between text-xs text-gray-400">
            <span>© 2026 NovERP. All rights reserved.</span>
            <span>v2.0.0</span>
          </div>
        </footer>
      </div>
    </div>
  );
}
