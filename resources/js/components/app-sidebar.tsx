import { Link } from '@inertiajs/react';
import {
    Banknote,
    Bell,
    CreditCard,
    ExternalLink,
    FileStack,
    FileText,
    FolderKanban,
    History,
    LayoutGrid,
    LayoutList,
    Link2,
    MessagesSquare,
    Target,
    UserCog,
    Users,
} from 'lucide-react';
import { NavMain, navButtonClasses } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import {
    activity,
    clients,
    consultations,
    experts,
    leads,
    notifications,
    invoices,
    paymentLinks,
    payments,
    payouts,
    plans,
    projects,
    quotationTemplates,
} from '@/routes/admin';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        tone: 'gold',
    },
    {
        title: 'Consultations',
        href: consultations(),
        icon: MessagesSquare,
        tone: 'blue',
    },
    {
        title: 'Leads',
        href: leads(),
        icon: Target,
        tone: 'teal',
    },
    {
        title: 'Projects',
        href: projects(),
        icon: FolderKanban,
        tone: 'indigo',
    },
    {
        title: 'Plans',
        href: plans(),
        icon: LayoutList,
        tone: 'violet',
    },
    {
        title: 'Payments',
        href: payments(),
        icon: CreditCard,
        tone: 'emerald',
    },
    {
        title: 'Payment Links',
        href: paymentLinks(),
        icon: Link2,
        tone: 'teal',
    },
    {
        title: 'Quotation Templates',
        href: quotationTemplates(),
        icon: FileStack,
        tone: 'gold',
    },
    {
        title: 'Invoices',
        href: invoices(),
        icon: FileText,
        tone: 'gold',
    },
    {
        title: 'Clients',
        href: clients(),
        icon: Users,
        tone: 'cyan',
    },
    {
        title: 'Experts',
        href: experts(),
        icon: UserCog,
        tone: 'rose',
    },
    {
        title: 'Payouts',
        href: payouts(),
        icon: Banknote,
        tone: 'teal',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Activity',
        href: activity(),
        icon: History,
        tone: 'violet',
    },
    {
        title: 'Notifications',
        href: notifications(),
        icon: Bell,
        tone: 'orange',
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link
                                href={dashboard()}
                                prefetch
                                className="group-data-[collapsible=icon]:justify-center"
                            >
                                <img
                                    src="/assets/images/logoBA-ink.png"
                                    alt="BlackDesck Agency"
                                    className="h-12 w-auto group-data-[collapsible=icon]:hidden"
                                />
                                <span className="hidden size-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 font-serif text-sm font-bold text-white shadow-[0_4px_12px_-2px_oklch(0.78_0.15_73/0.55)] group-data-[collapsible=icon]:flex">
                                    B
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter className="border-t border-sidebar-border/60 pt-2">
                <NavMain
                    items={footerNavItems}
                    label={null}
                    extra={
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                asChild
                                tooltip={{ children: 'Visit site' }}
                                className={navButtonClasses}
                            >
                                <a
                                    href="/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <span className="flex size-7 shrink-0 items-center justify-center rounded-lg border border-border/70 bg-muted/60 text-muted-foreground transition-all duration-200 group-hover/nav:-translate-y-px group-hover/nav:scale-105">
                                        <ExternalLink className="size-4" />
                                    </span>
                                    <span>Visit site</span>
                                </a>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    }
                />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
