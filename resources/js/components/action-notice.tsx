import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ComponentProps } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type NoticeTone = 'primary' | 'warning' | 'info' | 'success';

const TONE: Record<NoticeTone, { wrap: string; chip: string; cta: string }> = {
    primary: {
        wrap: 'border-primary/30 bg-primary/5',
        chip: 'border-primary/30 bg-primary/10 text-primary',
        cta: 'text-primary hover:bg-primary/10',
    },
    warning: {
        wrap: 'border-warning/40 bg-warning/10',
        chip: 'border-warning/40 bg-warning/15 text-warning',
        cta: 'text-warning hover:bg-warning/15',
    },
    info: {
        wrap: 'border-info/30 bg-info/5',
        chip: 'border-info/30 bg-info/10 text-info',
        cta: 'text-info hover:bg-info/10',
    },
    success: {
        wrap: 'border-success/30 bg-success/5',
        chip: 'border-success/30 bg-success/10 text-success',
        cta: 'text-success hover:bg-success/10',
    },
};

export interface ActionNoticeProps {
    href: ComponentProps<typeof Link>['href'];
    icon: LucideIcon;
    title: React.ReactNode;
    description?: React.ReactNode;
    cta: string;
    tone?: NoticeTone;
    className?: string;
}

/**
 * A uniform "something needs your attention" banner used across admin
 * surfaces, composed from the shadcn Alert + Button primitives. Tone changes
 * the accent colour only — every notice keeps the same icon-chip + copy +
 * CTA rhythm so they read as one family.
 */
export function ActionNotice({
    href,
    icon: Icon,
    title,
    description,
    cta,
    tone = 'primary',
    className,
}: ActionNoticeProps) {
    const t = TONE[tone];

    return (
        <Alert
            className={cn(
                'flex items-center gap-4 rounded-2xl py-3.5 pr-3 pl-4 shadow-xs edge-highlight sm:pl-5',
                t.wrap,
                className,
            )}
        >
            <span
                className={cn(
                    'flex size-9 shrink-0 items-center justify-center rounded-xl border',
                    t.chip,
                )}
            >
                <Icon className="size-4" />
            </span>

            <div className="min-w-0 flex-1">
                <AlertTitle className="text-sm font-semibold text-foreground">
                    {title}
                </AlertTitle>
                {description && (
                    <AlertDescription className="text-xs">
                        {description}
                    </AlertDescription>
                )}
            </div>

            <Button
                asChild
                variant="ghost"
                size="sm"
                className={cn('group shrink-0 font-medium', t.cta)}
            >
                <Link href={href}>
                    {cta}
                    <ArrowRight className="size-4 transition-transform duration-200 group-hover:translate-x-0.5" />
                </Link>
            </Button>
        </Alert>
    );
}
