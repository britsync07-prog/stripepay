import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavTone =
    | 'gold'
    | 'blue'
    | 'violet'
    | 'emerald'
    | 'cyan'
    | 'rose'
    | 'teal'
    | 'orange'
    | 'indigo';

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    tone?: NavTone;
};
