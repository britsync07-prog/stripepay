import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    AlarmClock,
    ArrowRight,
    Building2,
    CalendarClock,
    CalendarDays,
    LayoutGrid,
    List,
} from 'lucide-react';
import { useState } from 'react';

import {
    DataTable,
    TableEmpty,
    TBody,
    TD,
    TH,
    THead,
    TR,
} from '@/components/data-table';
import { FollowUpCalendar } from '@/components/leads/follow-up-calendar';
import { FollowUpDraftButton } from '@/components/leads/follow-up-draft-button';
import type { FollowUpCalendarMonth, Lead } from '@/components/leads/types';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import type { StatTone } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useAiEnabled } from '@/hooks/use-ai-enabled';
import { leads as leadsRoute } from '@/routes/admin';

interface Props {
    overdue: Lead[];
    today: Lead[];
    upcoming: Lead[];
    stats: { overdue: number; today: number; upcoming: number };
    calendar: FollowUpCalendarMonth;
}

function FollowUpTable({ rows, empty }: { rows: Lead[]; empty: string }) {
    const aiEnabled = useAiEnabled();

    return (
        <DataTable>
            <THead>
                <tr>
                    <TH>Lead</TH>
                    <TH>Stage</TH>
                    <TH>Follow-up date</TH>
                    <TH>
                        <span className="sr-only">Open</span>
                    </TH>
                </tr>
            </THead>
            <TBody>
                {rows.length === 0 ? (
                    <TableEmpty
                        colSpan={4}
                        icon={CalendarClock}
                        title={empty}
                        description="Scheduled follow-ups for these leads will appear here."
                    />
                ) : (
                    rows.map((lead) => (
                        <TR key={lead.uuid}>
                            <TD>
                                <Link
                                    href={lead.consultation_url}
                                    className="group/row block"
                                >
                                    <span className="font-medium transition-colors group-hover/row:text-primary">
                                        {lead.name ?? 'Unnamed lead'}
                                    </span>
                                    <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        {lead.company ? (
                                            <>
                                                <Building2 className="size-3" />
                                                {lead.company}
                                            </>
                                        ) : (
                                            (lead.email ?? lead.reference)
                                        )}
                                    </span>
                                </Link>
                            </TD>
                            <TD>
                                <Badge variant={lead.stage_tone}>
                                    {lead.stage_label}
                                </Badge>
                            </TD>
                            <TD>
                                <span
                                    className={
                                        lead.follow_up_overdue
                                            ? 'font-medium text-destructive'
                                            : 'text-muted-foreground'
                                    }
                                >
                                    {lead.next_follow_up_at ?? '—'}
                                </span>
                                {lead.follow_up_overdue && (
                                    <Badge
                                        variant="destructive"
                                        className="ml-2"
                                    >
                                        Overdue
                                    </Badge>
                                )}
                            </TD>
                            <TD align="right">
                                <span className="inline-flex items-center gap-1">
                                    {aiEnabled && (
                                        <FollowUpDraftButton
                                            uuid={lead.uuid}
                                            leadName={
                                                lead.name ?? 'Unnamed lead'
                                            }
                                        />
                                    )}
                                    <Link
                                        href={lead.consultation_url}
                                        className="inline-flex text-muted-foreground transition-colors hover:text-primary"
                                    >
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </span>
                            </TD>
                        </TR>
                    ))
                )}
            </TBody>
        </DataTable>
    );
}

function Section({
    icon: Icon,
    title,
    count,
    tone,
    rows,
    empty,
}: {
    icon: LucideIcon;
    title: string;
    count: number;
    tone: string;
    rows: Lead[];
    empty: string;
}) {
    return (
        <section className="flex flex-col gap-3">
            <div className="flex items-center gap-2.5">
                <Icon className={`size-4 ${tone}`} />
                <h2 className="font-serif text-lg font-semibold">{title}</h2>
                <span className="font-mono text-sm text-muted-foreground tabular-nums">
                    {count}
                </span>
            </div>
            <FollowUpTable rows={rows} empty={empty} />
        </section>
    );
}

export default function FollowUps({
    overdue,
    today,
    upcoming,
    stats,
    calendar,
}: Props) {
    const [view, setView] = useState<'agenda' | 'calendar'>('agenda');

    const cards: {
        label: string;
        value: number;
        icon: LucideIcon;
        tone: StatTone;
    }[] = [
        {
            label: 'Overdue',
            value: stats.overdue,
            icon: AlarmClock,
            tone: 'warning',
        },
        {
            label: 'Due today',
            value: stats.today,
            icon: CalendarClock,
            tone: 'primary',
        },
        {
            label: 'Upcoming',
            value: stats.upcoming,
            icon: CalendarDays,
            tone: 'info',
        },
    ];

    return (
        <>
            <Head title="Follow-ups" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Pipeline"
                    title="Follow-ups"
                    description="Leads with a scheduled follow-up, prioritised so you work one list instead of scanning the board."
                    actions={
                        <>
                            <ToggleGroup
                                type="single"
                                variant="outline"
                                size="sm"
                                value={view}
                                onValueChange={(v) =>
                                    v && setView(v as 'agenda' | 'calendar')
                                }
                            >
                                <ToggleGroupItem
                                    value="agenda"
                                    aria-label="Agenda view"
                                >
                                    <List className="size-4" /> Agenda
                                </ToggleGroupItem>
                                <ToggleGroupItem
                                    value="calendar"
                                    aria-label="Calendar view"
                                >
                                    <CalendarDays className="size-4" /> Calendar
                                </ToggleGroupItem>
                            </ToggleGroup>
                            <Button asChild variant="outline">
                                <Link href={leadsRoute()}>
                                    <LayoutGrid className="size-4" /> Leads
                                    board
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-3 sm:grid-cols-3">
                    {cards.map((c) => (
                        <StatCard
                            key={c.label}
                            compact
                            label={c.label}
                            value={c.value}
                            icon={c.icon}
                            tone={c.tone}
                        />
                    ))}
                </div>

                {view === 'calendar' ? (
                    <FollowUpCalendar calendar={calendar} />
                ) : (
                    <>
                        <Section
                            icon={AlarmClock}
                            title="Overdue"
                            count={stats.overdue}
                            tone="text-warning"
                            rows={overdue}
                            empty="Nothing overdue"
                        />
                        <Section
                            icon={CalendarClock}
                            title="Due today"
                            count={stats.today}
                            tone="text-primary"
                            rows={today}
                            empty="Nothing due today"
                        />
                        <Section
                            icon={CalendarDays}
                            title="Upcoming"
                            count={stats.upcoming}
                            tone="text-info"
                            rows={upcoming}
                            empty="No upcoming follow-ups"
                        />
                    </>
                )}
            </div>
        </>
    );
}
