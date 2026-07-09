import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavItem, NavTone } from '@/types';

export const navButtonClasses = cn(
    'group/nav relative h-9 rounded-xl font-serif text-sm transition-all duration-200',
    'group-data-[collapsible=icon]:p-1!',
    'data-[active=true]:bg-primary/12 data-[active=true]:font-semibold',
    'data-[active=true]:text-sidebar-accent-foreground',
    'data-[active=true]:ring-1 data-[active=true]:ring-primary/25',
);

// Static class strings per tone so Tailwind keeps them in the build.
// Sanctioned wayfinding palette: each nav section gets a distinct hue for
// scannability. This map is the only place these raw palette classes may
// live — status colours elsewhere use the semantic tokens.
type ToneStyle = { idle: string; active: string };

const toneStyles: Record<NavTone, ToneStyle> = {
    gold: {
        idle: 'border-amber-200/80 bg-gradient-to-br from-amber-50 to-amber-100 text-amber-600 shadow-[inset_0_1px_0_0_rgb(255_255_255/0.65)] group-hover/nav:border-amber-300 group-hover/nav:shadow-[0_4px_10px_-3px_oklch(0.78_0.15_73/0.45)]',
        active: 'border-transparent bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.35),0_4px_12px_-2px_oklch(0.78_0.15_73/0.55)]',
    },
    blue: {
        idle: 'border-blue-200/80 bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600 shadow-[inset_0_1px_0_0_rgb(255_255_255/0.65)] group-hover/nav:border-blue-300 group-hover/nav:shadow-[0_4px_10px_-3px_oklch(0.6_0.18_255/0.45)]',
        active: 'border-transparent bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.35),0_4px_12px_-2px_oklch(0.6_0.18_255/0.55)]',
    },
    violet: {
        idle: 'border-violet-200/80 bg-gradient-to-br from-violet-50 to-violet-100 text-violet-600 shadow-[inset_0_1px_0_0_rgb(255_255_255/0.65)] group-hover/nav:border-violet-300 group-hover/nav:shadow-[0_4px_10px_-3px_oklch(0.55_0.22_300/0.45)]',
        active: 'border-transparent bg-gradient-to-br from-violet-400 to-violet-600 text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.35),0_4px_12px_-2px_oklch(0.55_0.22_300/0.55)]',
    },
    emerald: {
        idle: 'border-emerald-200/80 bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-600 shadow-[inset_0_1px_0_0_rgb(255_255_255/0.65)] group-hover/nav:border-emerald-300 group-hover/nav:shadow-[0_4px_10px_-3px_oklch(0.6_0.17_160/0.45)]',
        active: 'border-transparent bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.35),0_4px_12px_-2px_oklch(0.6_0.17_160/0.55)]',
    },
    cyan: {
        idle: 'border-cyan-200/80 bg-gradient-to-br from-cyan-50 to-cyan-100 text-cyan-600 shadow-[inset_0_1px_0_0_rgb(255_255_255/0.65)] group-hover/nav:border-cyan-300 group-hover/nav:shadow-[0_4px_10px_-3px_oklch(0.65_0.13_215/0.45)]',
        active: 'border-transparent bg-gradient-to-br from-cyan-400 to-cyan-600 text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.35),0_4px_12px_-2px_oklch(0.65_0.13_215/0.55)]',
    },
    rose: {
        idle: 'border-rose-200/80 bg-gradient-to-br from-rose-50 to-rose-100 text-rose-600 shadow-[inset_0_1px_0_0_rgb(255_255_255/0.65)] group-hover/nav:border-rose-300 group-hover/nav:shadow-[0_4px_10px_-3px_oklch(0.62_0.21_15/0.45)]',
        active: 'border-transparent bg-gradient-to-br from-rose-400 to-rose-600 text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.35),0_4px_12px_-2px_oklch(0.62_0.21_15/0.55)]',
    },
    teal: {
        idle: 'border-teal-200/80 bg-gradient-to-br from-teal-50 to-teal-100 text-teal-600 shadow-[inset_0_1px_0_0_rgb(255_255_255/0.65)] group-hover/nav:border-teal-300 group-hover/nav:shadow-[0_4px_10px_-3px_oklch(0.6_0.12_185/0.45)]',
        active: 'border-transparent bg-gradient-to-br from-teal-400 to-teal-600 text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.35),0_4px_12px_-2px_oklch(0.6_0.12_185/0.55)]',
    },
    orange: {
        idle: 'border-orange-200/80 bg-gradient-to-br from-orange-50 to-orange-100 text-orange-600 shadow-[inset_0_1px_0_0_rgb(255_255_255/0.65)] group-hover/nav:border-orange-300 group-hover/nav:shadow-[0_4px_10px_-3px_oklch(0.7_0.18_55/0.45)]',
        active: 'border-transparent bg-gradient-to-br from-orange-400 to-orange-600 text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.35),0_4px_12px_-2px_oklch(0.7_0.18_55/0.55)]',
    },
    indigo: {
        idle: 'border-indigo-200/80 bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-600 shadow-[inset_0_1px_0_0_rgb(255_255_255/0.65)] group-hover/nav:border-indigo-300 group-hover/nav:shadow-[0_4px_10px_-3px_oklch(0.55_0.2_275/0.45)]',
        active: 'border-transparent bg-gradient-to-br from-indigo-400 to-indigo-600 text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.35),0_4px_12px_-2px_oklch(0.55_0.2_275/0.55)]',
    },
};

function NavIcon({ item, active }: { item: NavItem; active: boolean }) {
    if (!item.icon) {
        return null;
    }

    const tone = toneStyles[item.tone ?? 'gold'];
    const Icon = item.icon;

    return (
        <span
            className={cn(
                'flex size-6 shrink-0 items-center justify-center rounded-md border transition-all duration-200',
                'group-hover/nav:-translate-y-px group-hover/nav:scale-105',
                active ? tone.active : tone.idle,
            )}
        >
            <Icon className="size-3.5" />
        </span>
    );
}

export function NavMain({
    items = [],
    label = 'Platform',
    extra,
}: {
    items: NavItem[];
    label?: string | null;
    extra?: ReactNode;
}) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            {label && (
                <SidebarGroupLabel className="text-microcaps font-medium text-sidebar-foreground/45">
                    {label}
                </SidebarGroupLabel>
            )}
            <SidebarMenu className="gap-1">
                {items.map((item) => {
                    const active = isCurrentOrParentUrl(item.href);

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={active}
                                tooltip={{ children: item.title }}
                                className={navButtonClasses}
                            >
                                <Link href={item.href} prefetch>
                                    <NavIcon item={item} active={active} />
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
                {extra}
            </SidebarMenu>
        </SidebarGroup>
    );
}
