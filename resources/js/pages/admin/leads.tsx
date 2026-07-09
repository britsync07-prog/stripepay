import {
    DndContext,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type { DragEndEvent } from '@dnd-kit/core';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlarmClock,
    Building2,
    CalendarClock,
    ExternalLink,
    LayoutGrid,
    List,
    Sparkles,
    Target,
    UserPlus,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';

import {
    DataTable,
    TBody,
    TD,
    TH,
    THead,
    TR,
    TableEmpty,
} from '@/components/data-table';
import { AddLeadDialog } from '@/components/leads/add-lead-dialog';
import { BoardColumn } from '@/components/leads/board-column';
import { FollowUp } from '@/components/leads/follow-up';
import { FollowUpDialog } from '@/components/leads/follow-up-dialog';
import { LostReasonDialog } from '@/components/leads/lost-reason-dialog';
import type { Column, Lead, LeadsPageProps } from '@/components/leads/types';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useAiEnabled } from '@/hooks/use-ai-enabled';
import {
    scoreAll as scoreAllRoute,
    stage as stageRoute,
} from '@/routes/admin/leads';

export default function AdminLeads({
    columns,
    countries,
    filters,
    stats,
}: LeadsPageProps) {
    const aiEnabled = useAiEnabled();
    const [board, setBoard] = useState<Column[]>(columns);
    const [syncedColumns, setSyncedColumns] = useState(columns);
    const [view, setView] = useState<'board' | 'list'>('board');
    const [search, setSearch] = useState(filters.search);
    const [adding, setAdding] = useState(false);
    const [lostFor, setLostFor] = useState<Lead | null>(null);
    const [followUpFor, setFollowUpFor] = useState<Lead | null>(null);
    const [scoring, setScoring] = useState(false);
    const scorePoll = useRef<ReturnType<typeof setInterval>>(undefined);

    useEffect(() => () => clearInterval(scorePoll.current), []);

    const scoreWithAi = () => {
        router.post(
            scoreAllRoute().url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Scores land as the staggered jobs finish — poll the board
                    // a few times so the chips visibly fill in without a manual
                    // refresh, then stop.
                    setScoring(true);
                    clearInterval(scorePoll.current);
                    let pulls = 0;
                    scorePoll.current = setInterval(() => {
                        pulls += 1;
                        router.reload({ only: ['columns', 'stats'] });

                        if (pulls >= 6) {
                            clearInterval(scorePoll.current);
                            setScoring(false);
                        }
                    }, 4000);
                },
            },
        );
    };

    // Re-sync to the authoritative server state after any round-trip.
    if (syncedColumns !== columns) {
        setSyncedColumns(columns);
        setBoard(columns);
    }

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor),
    );

    const allLeads = useMemo(() => board.flatMap((c) => c.leads), [board]);

    const moveLocally = (uuid: string, toStage: string) => {
        setBoard((prev) => {
            let moved: Lead | undefined;
            const stripped = prev.map((col) => {
                const leads = col.leads.filter((l) => {
                    if (l.uuid === uuid) {
                        moved = l;

                        return false;
                    }

                    return true;
                });

                return { ...col, leads };
            });

            if (!moved) {
                return prev;
            }

            const target = { ...moved, stage: toStage };

            return stripped.map((col) =>
                col.stage === toStage
                    ? { ...col, leads: [target, ...col.leads] }
                    : col,
            );
        });
    };

    const onDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over) {
            return;
        }

        const lead = allLeads.find((l) => l.uuid === active.id);
        const target = String(over.id);

        if (!lead || lead.stage === target) {
            return;
        }

        if (lead.stage === 'won') {
            toast.error('A won lead is closed and cannot be moved.');

            return;
        }

        if (target === 'won') {
            toast.error('A lead becomes Won when its payment is recorded.');

            return;
        }

        if (target === 'lost') {
            setLostFor(lead);

            return;
        }

        moveLocally(lead.uuid, target);
        router.post(
            stageRoute(lead.uuid).url,
            { stage: target },
            {
                preserveScroll: true,
                // Roll the optimistic move back to the server's truth on failure.
                onError: () => setBoard(syncedColumns),
            },
        );
    };

    const searchTimer = useRef<ReturnType<typeof setTimeout>>(undefined);

    useEffect(() => () => clearTimeout(searchTimer.current), []);

    const runSearch = (value: string) => {
        setSearch(value);
        clearTimeout(searchTimer.current);
        searchTimer.current = setTimeout(() => {
            router.get(
                window.location.pathname,
                value ? { 'filter[search]': value } : {},
                { preserveState: true, replace: true, preserveScroll: true },
            );
        }, 300);
    };

    return (
        <>
            <Head title="Leads" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Pipeline"
                    title="Leads"
                    description="Track prospects from first contact to a won engagement."
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            {aiEnabled && (
                                <Button
                                    variant="outline"
                                    onClick={scoreWithAi}
                                    disabled={scoring}
                                    title="Estimate every unscored open lead's likelihood to convert"
                                >
                                    {scoring ? (
                                        <>
                                            <Spinner className="size-4" />{' '}
                                            Scoring…
                                        </>
                                    ) : (
                                        <>
                                            <Sparkles className="size-4" />{' '}
                                            Score with AI
                                        </>
                                    )}
                                </Button>
                            )}
                            <Button onClick={() => setAdding(true)}>
                                <UserPlus className="size-4" /> Add lead
                            </Button>
                        </div>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total leads"
                        value={stats.total}
                        icon={Target}
                    />
                    <StatCard
                        label="Open"
                        value={stats.open}
                        icon={List}
                        tone="info"
                    />
                    <StatCard
                        label="Follow-ups overdue"
                        value={stats.overdue}
                        icon={AlarmClock}
                        tone="warning"
                    />
                    <StatCard
                        label="Won"
                        value={stats.won}
                        icon={Building2}
                        tone="success"
                    />
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <Input
                        value={search}
                        onChange={(e) => runSearch(e.target.value)}
                        placeholder="Search leads…"
                        className="max-w-xs"
                    />
                    <div className="ml-auto flex items-center gap-1 rounded-lg border p-1">
                        <Button
                            type="button"
                            variant={view === 'board' ? 'secondary' : 'ghost'}
                            size="sm"
                            className="h-8"
                            onClick={() => setView('board')}
                        >
                            <LayoutGrid className="size-4" /> Board
                        </Button>
                        <Button
                            type="button"
                            variant={view === 'list' ? 'secondary' : 'ghost'}
                            size="sm"
                            className="h-8"
                            onClick={() => setView('list')}
                        >
                            <List className="size-4" /> List
                        </Button>
                    </div>
                </div>

                {view === 'board' ? (
                    <DndContext sensors={sensors} onDragEnd={onDragEnd}>
                        <div className="flex gap-4 overflow-x-auto pb-4">
                            {board.map((column) => (
                                <BoardColumn
                                    key={column.stage}
                                    column={column}
                                    onFollowUp={setFollowUpFor}
                                />
                            ))}
                        </div>
                    </DndContext>
                ) : (
                    <DataTable>
                        <THead>
                            <TR>
                                <TH>Lead</TH>
                                <TH>Stage</TH>
                                <TH>Amount</TH>
                                <TH>Follow-up</TH>
                                <TH>Created</TH>
                                <TH className="text-right">Actions</TH>
                            </TR>
                        </THead>
                        <TBody>
                            {allLeads.length === 0 ? (
                                <TableEmpty
                                    colSpan={6}
                                    icon={Target}
                                    title="No leads yet"
                                    description="Add a lead or wait for an inbound custom-plan request."
                                />
                            ) : (
                                allLeads.map((lead) => (
                                    <TR
                                        key={lead.uuid}
                                        href={lead.consultation_url}
                                    >
                                        <TD>
                                            <div className="font-medium">
                                                {lead.name ?? 'Unnamed lead'}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {lead.company ?? lead.email}
                                            </div>
                                        </TD>
                                        <TD>
                                            <Badge variant={lead.stage_tone}>
                                                {lead.stage_label}
                                            </Badge>
                                        </TD>
                                        <TD className="font-mono tabular-nums">
                                            {lead.unpriced ? '—' : lead.amount}
                                        </TD>
                                        <TD>
                                            <FollowUp lead={lead} />
                                        </TD>
                                        <TD className="text-muted-foreground">
                                            {lead.created_at}
                                        </TD>
                                        <TD className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 px-2 text-xs"
                                                    onClick={() =>
                                                        setFollowUpFor(lead)
                                                    }
                                                >
                                                    <CalendarClock className="size-3" />
                                                </Button>
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 px-2 text-xs"
                                                >
                                                    <Link
                                                        href={
                                                            lead.consultation_url
                                                        }
                                                    >
                                                        <ExternalLink className="size-3" />
                                                    </Link>
                                                </Button>
                                            </div>
                                        </TD>
                                    </TR>
                                ))
                            )}
                        </TBody>
                    </DataTable>
                )}
            </div>

            <AddLeadDialog
                open={adding}
                onOpenChange={setAdding}
                countries={countries}
            />
            <LostReasonDialog lead={lostFor} onClose={() => setLostFor(null)} />
            <FollowUpDialog
                lead={followUpFor}
                onClose={() => setFollowUpFor(null)}
            />
        </>
    );
}
