import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { CALENDAR_WEEKDAYS, MAX_LEADS_PER_DAY } from '@/constants/calendar';
import { cn } from '@/lib/utils';
import { followUps } from '@/routes/admin';

import type { FollowUpCalendarMonth } from './types';

export function FollowUpCalendar({
    calendar,
}: {
    calendar: FollowUpCalendarMonth;
}) {
    const monthUrl = (month?: string) =>
        month ? followUps({ query: { month } }).url : followUps().url;

    return (
        <div className="overflow-hidden surface-card">
            <div className="flex items-center justify-between border-b px-4 py-3">
                <h2 className="font-serif text-lg font-semibold">
                    {calendar.month}
                </h2>
                <div className="flex items-center gap-1.5">
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="w-8 px-0"
                        aria-label="Previous month"
                    >
                        <Link
                            href={monthUrl(calendar.prev)}
                            only={['calendar']}
                            preserveState
                            preserveScroll
                        >
                            <ChevronLeft className="size-4" />
                        </Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link
                            href={monthUrl()}
                            only={['calendar']}
                            preserveState
                            preserveScroll
                        >
                            Today
                        </Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="w-8 px-0"
                        aria-label="Next month"
                    >
                        <Link
                            href={monthUrl(calendar.next)}
                            only={['calendar']}
                            preserveState
                            preserveScroll
                        >
                            <ChevronRight className="size-4" />
                        </Link>
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-7 gap-px border-b bg-border text-center text-[11px] font-medium tracking-wider text-muted-foreground uppercase">
                {CALENDAR_WEEKDAYS.map((d) => (
                    <div key={d} className="bg-muted/40 py-2">
                        {d}
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-7 gap-px bg-border">
                {calendar.weeks.flat().map((day) => (
                    <div
                        key={day.iso}
                        className={cn(
                            'min-h-32 p-2 transition-colors',
                            day.inMonth ? 'bg-card' : 'bg-muted/60',
                        )}
                    >
                        <div
                            className={cn(
                                'mb-1.5 flex size-7 items-center justify-center rounded-full font-mono text-sm tabular-nums',
                                day.isToday
                                    ? 'bg-primary font-semibold text-primary-foreground shadow-sm'
                                    : day.inMonth
                                      ? 'text-foreground'
                                      : 'text-muted-foreground/45',
                            )}
                        >
                            {day.day}
                        </div>

                        <div
                            className={cn(
                                'flex flex-col gap-1',
                                !day.inMonth && 'opacity-60',
                            )}
                        >
                            {day.leads
                                .slice(0, MAX_LEADS_PER_DAY)
                                .map((lead) => (
                                    <Link
                                        key={lead.uuid}
                                        href={lead.consultation_url}
                                        title={`${lead.name ?? lead.reference} · ${lead.stage_label}`}
                                        className={cn(
                                            'truncate rounded-md px-2 py-1 text-xs font-medium transition-colors',
                                            day.isPast
                                                ? 'bg-destructive/12 text-destructive hover:bg-destructive/20'
                                                : 'bg-primary/12 text-primary hover:bg-primary/20',
                                        )}
                                    >
                                        {lead.name ?? 'Unnamed lead'}
                                    </Link>
                                ))}
                            {day.leads.length > MAX_LEADS_PER_DAY && (
                                <span className="px-1 text-[11px] font-medium text-muted-foreground">
                                    +{day.leads.length - MAX_LEADS_PER_DAY} more
                                </span>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
