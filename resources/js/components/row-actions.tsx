import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { ComponentProps, ReactNode } from 'react';

import { cn } from '@/lib/utils';

type Tone = 'default' | 'danger';

const base =
    'inline-flex size-8 items-center justify-center rounded-md border border-border text-muted-foreground transition-colors focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50';

const toneClass: Record<Tone, string> = {
    default: 'hover:border-primary/40 hover:bg-primary/10 hover:text-primary',
    danger: 'hover:border-destructive/40 hover:bg-destructive/10 hover:text-destructive',
};

/** Right-aligned container for a row's icon-only actions. */
export function RowActions({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('flex items-center justify-end gap-1.5', className)}>
            {children}
        </div>
    );
}

/** Icon-only navigation action (Inertia Link). `label` drives tooltip + a11y. */
export function RowActionLink({
    href,
    icon: Icon,
    label,
    tone = 'default',
    className,
}: {
    href: ComponentProps<typeof Link>['href'];
    icon: LucideIcon;
    label: string;
    tone?: Tone;
    className?: string;
}) {
    return (
        <Link
            href={href}
            title={label}
            aria-label={label}
            className={cn(base, toneClass[tone], className)}
        >
            <Icon className="size-4" />
        </Link>
    );
}

/** Icon-only button action (onClick). `label` drives tooltip + a11y. */
export function RowActionButton({
    icon: Icon,
    label,
    onClick,
    tone = 'default',
    type = 'button',
    disabled,
    className,
}: {
    icon: LucideIcon;
    label: string;
    onClick?: () => void;
    tone?: Tone;
    type?: 'button' | 'submit';
    disabled?: boolean;
    className?: string;
}) {
    return (
        <button
            type={type}
            onClick={onClick}
            disabled={disabled}
            title={label}
            aria-label={label}
            className={cn(base, toneClass[tone], className)}
        >
            <Icon className="size-4" />
        </button>
    );
}
