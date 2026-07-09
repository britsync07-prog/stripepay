import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    CreditCard,
    FileText,
    FolderKanban,
    Home,
    LayoutDashboard,
    LayoutGrid,
    LogOut,
    User as UserIcon,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';

import { ChatWidget } from '@/components/chat/chat-widget';
import { NotificationsList } from '@/components/notifications-list';
import { RealtimeNotifications } from '@/components/realtime-notifications';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAiEnabled } from '@/hooks/use-ai-enabled';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';
import { home, logout } from '@/routes';
import {
    consultations,
    dashboard,
    invoices,
    notifications as clientNotifications,
    payments,
    profile,
    projects,
} from '@/routes/portal';
import { ask as assistantAsk } from '@/routes/portal/assistant';

const navItems = [
    {
        label: 'Overview',
        href: dashboard(),
        match: '/portal/dashboard',
        icon: LayoutDashboard,
    },
    {
        label: 'Consultations',
        href: consultations(),
        match: '/portal/consultations',
        icon: LayoutGrid,
    },
    {
        label: 'Projects',
        href: projects(),
        match: '/portal/projects',
        icon: FolderKanban,
    },
    {
        label: 'Invoices',
        href: invoices(),
        match: '/portal/invoices',
        icon: FileText,
    },
    {
        label: 'Payments',
        href: payments(),
        match: '/portal/payments',
        icon: CreditCard,
    },
];

export default function ClientLayout({ children }: PropsWithChildren) {
    const page = usePage();
    const { auth } = page.props;
    const url = page.url;
    const aiEnabled = useAiEnabled();
    const unread = page.props.notifications?.unread ?? 0;

    useFlashToast();

    return (
        <div className="flex min-h-screen flex-col bg-background bg-app-ambient text-foreground">
            {auth.user && <RealtimeNotifications userId={auth.user.id} />}
            <header className="sticky top-0 z-20 overflow-hidden border-b border-border/60 bg-brand text-brand-foreground">
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_140%_at_0%_-25%,var(--primary),transparent_55%),radial-gradient(55%_140%_at_100%_-30%,var(--brand-accent),transparent_55%)] opacity-[0.12]"
                />
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 bg-grain opacity-[0.08] mix-blend-overlay"
                />
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent"
                />

                <div className="relative mx-auto flex w-full max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <Link
                        href={dashboard()}
                        aria-label="BlackDesck Agency — dashboard"
                        className="shrink-0 transition-transform duration-200 hover:scale-[1.03]"
                    >
                        <img
                            src="/assets/images/logoBA-ink.png"
                            alt="BlackDesck Agency"
                            className="h-9 w-auto drop-shadow-sm sm:h-11"
                        />
                    </Link>

                    {/* Primary nav — inline on tablet/desktop, moves to a bottom
                        tab bar on mobile so the wide wordmark never overflows. */}
                    <nav
                        aria-label="Primary"
                        className="hidden items-center gap-1 sm:flex"
                    >
                        {navItems.map((item, i) => {
                            const active = url.startsWith(item.match);

                            return (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'group flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-all duration-200',
                                        active
                                            ? 'bg-primary/15 font-medium text-brand-accent shadow-[inset_0_0_0_1px_oklch(from_var(--primary)_l_c_h/0.35)]'
                                            : 'text-brand-foreground/70 hover:bg-accent hover:text-brand-foreground',
                                    )}
                                >
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'text-microcaps transition-colors',
                                            active
                                                ? 'text-primary'
                                                : 'text-brand-foreground/40 group-hover:text-primary/70',
                                        )}
                                    >
                                        0{i + 1}
                                    </span>
                                    <span>{item.label}</span>
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="flex items-center gap-2 sm:gap-3">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button
                                    aria-label="Notifications"
                                    className="relative flex size-9 items-center justify-center rounded-lg text-brand-foreground/70 transition-colors hover:bg-accent hover:text-brand-foreground"
                                >
                                    <Bell className="size-4" />
                                    {unread > 0 && (
                                        <span className="absolute top-1.5 right-1.5 flex h-3.5 min-w-3.5 items-center justify-center rounded-full bg-primary px-1 text-[9px] font-semibold text-primary-foreground">
                                            {unread > 9 ? '9+' : unread}
                                        </span>
                                    )}
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                className="w-96 max-w-[calc(100vw-2rem)] p-2"
                            >
                                <NotificationsList />
                                <Link
                                    href={clientNotifications()}
                                    className="mt-1 block rounded-md px-2 py-2 text-center text-xs font-medium text-primary transition-colors hover:bg-accent"
                                >
                                    View all notifications
                                </Link>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button
                                    aria-label="Account menu"
                                    className="flex items-center gap-2 rounded-full p-0.5 pr-2 transition-colors hover:bg-accent"
                                >
                                    <span className="flex size-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground shadow-[inset_0_1px_0_0_oklch(1_0_0/0.25)]">
                                        {auth.user?.name
                                            ?.charAt(0)
                                            ?.toUpperCase() ?? 'U'}
                                    </span>
                                    <span className="hidden max-w-[12ch] truncate text-sm text-brand-foreground/80 sm:inline">
                                        {auth.user?.name}
                                    </span>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-64">
                                <div className="flex items-center gap-3 px-2 py-2">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                                        {auth.user?.name
                                            ?.charAt(0)
                                            ?.toUpperCase() ?? 'U'}
                                    </span>
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {auth.user?.name}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {auth.user?.email}
                                        </p>
                                    </div>
                                </div>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={home()}
                                        className="w-full cursor-pointer"
                                    >
                                        <Home className="size-4" />
                                        Home
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={profile()}
                                        className="w-full cursor-pointer"
                                    >
                                        <UserIcon className="size-4" />
                                        Profile
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link
                                        as="button"
                                        method="post"
                                        href={logout()}
                                        onClick={() => router.flushAll()}
                                        className="w-full cursor-pointer font-medium text-destructive focus:bg-destructive/10 focus:text-destructive"
                                    >
                                        <LogOut className="size-4 text-destructive" />
                                        Log out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8 sm:px-6">
                {children}
            </main>

            <footer className="mx-auto w-full max-w-5xl px-4 pb-28 sm:px-6 sm:pb-10">
                <div className="flex flex-col items-center justify-between gap-2 border-t border-border/60 pt-5 sm:flex-row">
                    <p className="text-microcaps text-muted-foreground">
                        © {new Date().getFullYear()} BlackDesck Agency
                    </p>
                    <p className="flex items-center gap-3 text-microcaps text-muted-foreground">
                        Client portal
                        <span aria-hidden className="text-[9px] text-primary">
                            ✦
                        </span>
                        Secured access
                    </p>
                </div>
            </footer>

            {/* Mobile bottom navigation — primary nav for small screens */}
            <nav
                aria-label="Primary"
                className="fixed inset-x-0 bottom-0 z-30 border-t border-border/60 bg-background/95 backdrop-blur-md sm:hidden"
            >
                <div className="mx-auto flex max-w-5xl items-stretch justify-around px-2 pb-[env(safe-area-inset-bottom)]">
                    {navItems.map((item) => {
                        const active = url.startsWith(item.match);

                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'flex min-w-0 flex-1 flex-col items-center gap-1 rounded-lg py-2.5 text-[10px] font-medium transition-colors',
                                    active
                                        ? 'text-primary'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                <item.icon className="size-5" />
                                <span className="max-w-full truncate px-1">
                                    {item.label}
                                </span>
                            </Link>
                        );
                    })}
                </div>
            </nav>

            {aiEnabled && (
                <ChatWidget
                    endpoint={assistantAsk().url}
                    triggerClass="bottom-20 sm:bottom-5"
                    panelClass="bottom-[9.5rem] max-h-[calc(100vh-12rem)] sm:bottom-24 sm:max-h-[calc(100vh-8rem)]"
                    title="Ask a question"
                    greeting="Hi! Ask me about your engagements, payments, invoices or projects."
                    suggestions={[
                        'What is the status of my engagement?',
                        'How much have I paid so far?',
                        'Do I have anything outstanding to pay?',
                    ]}
                />
            )}
        </div>
    );
}
