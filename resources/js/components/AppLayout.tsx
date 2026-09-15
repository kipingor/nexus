import { Link, usePage } from '@inertiajs/react';
import { ReactNode, useState } from 'react';
import { type PageProps } from '@/types';
import {
    LayoutDashboard, Users, FolderKanban, Receipt, Wallet,
    FileText, Settings, Menu, X, ChevronDown, LogOut, Bell, CheckSquare,
    Building2,
} from 'lucide-react';

interface NavItem {
    label: string;
    href: string;
    icon: ReactNode;
    permission?: string;
}

interface UserWithPermissions {
    name: string;
    email: string;
    permissions?: string[];
}

const nav: NavItem[] = [
    { label: 'Dashboard', href: '/dashboard',       icon: <LayoutDashboard size={18} /> },
    { label: 'Contacts',  href: '/crm/contacts',    icon: <Users size={18} />, permission: 'contact.view' },
    { label: 'Companies', href: '/crm/companies',   icon: <Building2 size={18} />, permission: 'company.view' },
    { label: 'Deals',     href: '/crm/deals',       icon: <FolderKanban size={18} />, permission: 'deal.view' },
    { label: 'Projects',  href: '/projects',        icon: <FolderKanban size={18} />, permission: 'project.view' },
    { label: 'Tasks',     href: '/tasks',           icon: <CheckSquare size={18} />, permission: 'task.view' },
    { label: 'Expenses',  href: '/expenses',        icon: <Receipt size={18} /> },
    { label: 'Payroll',   href: '/payroll',         icon: <Wallet size={18} /> },
    { label: 'Documents', href: '/documents',       icon: <FileText size={18} /> },
    { label: 'Team',      href: '/users',            icon: <Settings size={18} />, permission: 'user.view' },
    { label: 'Settings',  href: '/settings',         icon: <Settings size={18} /> },
];

export default function AppLayout({ children }: { children: ReactNode }) {
    const { auth, tenant, flash } = usePage<PageProps>().props;
    const currentUser = auth.user as UserWithPermissions | null;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);

    return (
        <div className="flex h-screen bg-stone-950 text-stone-100 font-sans">
            {/* Sidebar */}
            <aside
                className={`fixed inset-y-0 left-0 z-50 w-64 bg-stone-900 border-r border-stone-800 flex flex-col
                    transition-transform duration-200 lg:relative lg:translate-x-0
                    ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}
            >
                {/* Logo */}
                <div className="flex items-center gap-3 px-5 py-4 border-b border-stone-800">
                    <div className="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center">
                        <Building2 size={16} className="text-stone-950" />
                    </div>
                    <div>
                        <div className="text-sm font-semibold text-stone-100 leading-none">
                            {tenant?.name ?? 'Nexus'}
                        </div>
                        {tenant?.plan && (
                            <div className="text-xs text-stone-400 mt-0.5 capitalize">{tenant.plan}</div>
                        )}
                    </div>
                </div>

                {/* Nav */}
                <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                    {nav.filter(item => !item.permission || currentUser?.permissions?.includes(item.permission)).map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-stone-400
                                hover:text-stone-100 hover:bg-stone-800 transition-colors group"
                        >
                            <span className="text-stone-500 group-hover:text-amber-400 transition-colors">
                                {item.icon}
                            </span>
                            {item.label}
                        </Link>
                    ))}
                </nav>

                {/* User */}
                <div className="border-t border-stone-800 p-3">
                    <div className="relative">
                        <button
                            onClick={() => setUserMenuOpen(!userMenuOpen)}
                            className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-stone-800
                                transition-colors text-left"
                        >
                            <div className="w-8 h-8 bg-amber-500/20 rounded-full flex items-center justify-center
                                text-amber-400 text-sm font-semibold flex-shrink-0">
                                {auth.user?.name.charAt(0).toUpperCase()}
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="text-sm text-stone-200 truncate">{auth.user?.name}</div>
                                <div className="text-xs text-stone-500 truncate">{auth.user?.email}</div>
                            </div>
                            <ChevronDown size={14} className="text-stone-500 flex-shrink-0" />
                        </button>

                        {userMenuOpen && (
                            <div className="absolute bottom-full left-0 right-0 mb-1 bg-stone-800 border border-stone-700
                                rounded-lg shadow-xl overflow-hidden z-50">
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-stone-300
                                        hover:bg-stone-700 hover:text-red-400 transition-colors"
                                >
                                    <LogOut size={14} />
                                    Sign out
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </aside>

            {/* Mobile overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/60 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Main */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                {/* Topbar */}
                <header className="flex items-center gap-4 px-6 py-4 border-b border-stone-800 bg-stone-950">
                    <button
                        className="lg:hidden text-stone-400 hover:text-stone-100"
                        onClick={() => setSidebarOpen(true)}
                    >
                        <Menu size={20} />
                    </button>
                    <div className="flex-1" />
                    <button className="text-stone-400 hover:text-stone-100">
                        <Bell size={18} />
                    </button>
                </header>

                {/* Flash messages */}
                {flash?.success && (
                    <div className="mx-6 mt-4 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20
                        text-emerald-400 text-sm rounded-lg">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mx-6 mt-4 px-4 py-3 bg-red-500/10 border border-red-500/20
                        text-red-400 text-sm rounded-lg">
                        {flash.error}
                    </div>
                )}

                {/* Content */}
                <main className="flex-1 overflow-auto">
                    {children}
                </main>
            </div>
        </div>
    );
}
