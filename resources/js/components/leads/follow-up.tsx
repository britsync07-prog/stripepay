import { AlarmClock, CalendarClock } from 'lucide-react';

import { cn } from '@/lib/utils';

import type { Lead } from './types';

export function FollowUp({ lead }: { lead: Lead }) {
    if (!lead.next_follow_up_at) {
        return null;
    }

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 text-xs',
                lead.follow_up_overdue
                    ? 'font-medium text-destructive'
                    : 'text-muted-foreground',
            )}
        >
            {lead.follow_up_overdue ? (
                <AlarmClock className="size-3" />
            ) : (
                <CalendarClock className="size-3" />
            )}
            {lead.next_follow_up_at}
        </span>
    );
}
