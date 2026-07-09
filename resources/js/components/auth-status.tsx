import { CheckCircle2 } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function AuthStatus({
    status,
    className,
}: {
    status?: string;
    className?: string;
}) {
    if (!status) {
        return null;
    }

    return (
        <div
            role="status"
            className={cn(
                'flex items-start gap-3 rounded-xl border border-success/25 bg-success/10 px-4 py-3 text-left text-sm font-medium text-success shadow-xs',
                className,
            )}
        >
            <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
            <span className="leading-snug text-balance">{status}</span>
        </div>
    );
}
