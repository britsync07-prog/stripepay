import { router } from '@inertiajs/react';
import type { ComponentProps, ElementType, MouseEvent, ReactNode } from 'react';

import { EmptyState } from '@/components/empty-state';
import { cn } from '@/lib/utils';

/**
 * The single styled table shell used across admin + portal lists. Wraps a
 * native <table> in the warm-paper card surface with a gradient header, a
 * consistent cell rhythm, and — crucially — horizontal-scroll affordance so
 * wide tables stay usable on mobile instead of overflowing the viewport.
 *
 * Compose with THead / TH / TBody / TR / TD / TableEmpty. For the richest
 * mobile experience, pair a `hidden md:block` <DataTable> with a `md:hidden`
 * card list (see admin/portal list pages).
 */
export function DataTable({
    children,
    className,
    bare = false,
    toolbar,
    footer,
}: {
    children: ReactNode;
    className?: string;
    /** Drop the outer card chrome — for embedding inside an existing Panel. */
    bare?: boolean;
    /** Filter bar rendered inside the card, above the table. */
    toolbar?: ReactNode;
    /** Pagination rendered inside the card, below the table. */
    footer?: ReactNode;
}) {
    const scroll = (
        // scroll container — wide tables stay usable on mobile via horizontal scroll
        <div className="-mx-px overflow-x-auto overscroll-x-contain">
            <table className="w-full min-w-[36rem] border-collapse text-sm">
                {children}
            </table>
        </div>
    );

    if (bare) {
        return <div className={className}>{scroll}</div>;
    }

    return (
        <div className={cn('overflow-hidden surface-tile', className)}>
            {toolbar && (
                <div className="border-b border-border/70 p-3 sm:p-4">
                    {toolbar}
                </div>
            )}
            {scroll}
            {footer && (
                <div className="border-t border-border/70 p-3 sm:p-4">
                    {footer}
                </div>
            )}
        </div>
    );
}

export function THead({ children }: { children: ReactNode }) {
    return (
        <thead className="border-b bg-muted/40 text-left text-[11px] font-medium tracking-[0.08em] text-muted-foreground uppercase">
            {children}
        </thead>
    );
}

export function TH({
    children,
    className,
    align = 'left',
    ...rest
}: ComponentProps<'th'> & { align?: 'left' | 'right' | 'center' }) {
    return (
        <th
            scope="col"
            className={cn(
                'px-4 py-3 font-medium whitespace-nowrap',
                align === 'right' && 'text-right',
                align === 'center' && 'text-center',
                className,
            )}
            {...rest}
        >
            {children}
        </th>
    );
}

export function TBody({ children }: { children: ReactNode }) {
    return <tbody className="divide-y divide-border/70">{children}</tbody>;
}

/**
 * Table row. Pass `href` to make the whole row navigate on click — clicks on
 * inner links, buttons and inputs still win, and the per-row action link stays
 * as the keyboard/screen-reader path.
 */
export function TR({
    children,
    className,
    href,
    ...rest
}: ComponentProps<'tr'> & { href?: string }) {
    const navigate = (event: MouseEvent<HTMLTableRowElement>) => {
        if (!href) {
            return;
        }

        const target = event.target as HTMLElement;

        if (target.closest('a, button, input, select, textarea, label')) {
            return;
        }

        router.visit(href);
    };

    return (
        <tr
            onClick={href ? navigate : undefined}
            className={cn(
                'transition-colors hover:bg-muted/40',
                href && 'cursor-pointer',
                className,
            )}
            {...rest}
        >
            {children}
        </tr>
    );
}

export function TD({
    children,
    className,
    align = 'left',
    as,
    ...rest
}: ComponentProps<'td'> & {
    align?: 'left' | 'right' | 'center';
    /** Render as <th scope="row"> for the row's primary cell (a11y). */
    as?: ElementType;
}) {
    const Cell = (as ?? 'td') as ElementType;

    return (
        <Cell
            className={cn(
                'px-4 py-3 align-middle',
                align === 'right' && 'text-right',
                align === 'center' && 'text-center',
                className,
            )}
            {...rest}
        >
            {children}
        </Cell>
    );
}

/** Full-width empty placeholder row spanning every column. */
export function TableEmpty({
    colSpan,
    icon,
    title,
    description,
    action,
}: {
    colSpan: number;
    icon: ComponentProps<typeof EmptyState>['icon'];
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <tr>
            <td colSpan={colSpan}>
                <EmptyState
                    icon={icon}
                    title={title}
                    description={description}
                    action={action}
                />
            </td>
        </tr>
    );
}
