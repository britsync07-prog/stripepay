import { Badge } from '@/components/ui/badge';
import { humanizeStatus, statusBadgeVariant } from '@/lib/status';
import { cn } from '@/lib/utils';

export function ConsultationStatusBadge({
    status,
    label,
    className,
}: {
    status: string;
    label?: string;
    className?: string;
}) {
    return (
        <Badge
            variant={statusBadgeVariant(status)}
            className={cn('gap-1.5 capitalize', className)}
        >
            <span
                aria-hidden
                className="size-1.5 rounded-full bg-current opacity-70"
            />
            {humanizeStatus(label ?? status)}
        </Badge>
    );
}
