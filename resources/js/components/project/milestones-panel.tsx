import { router, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    Circle,
    CircleDot,
    Pencil,
    Plus,
    Sparkles,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Panel } from '@/components/panel';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useAiEnabled } from '@/hooks/use-ai-enabled';
import {
    destroy as destroyMilestone,
    status as milestoneStatus,
    store as storeMilestone,
    suggest as suggestMilestones,
    update as updateMilestone,
} from '@/routes/admin/projects/milestones';
import type { Milestone } from '@/types';

function MilestoneIcon({ status }: { status: string }) {
    if (status === 'completed') {
        return <CheckCircle2 className="size-5 text-success" />;
    }

    if (status === 'in_progress') {
        return <CircleDot className="size-5 text-primary" />;
    }

    return <Circle className="size-5 text-muted-foreground" />;
}

function MilestoneDialog({
    projectUuid,
    milestone,
    trigger,
}: {
    projectUuid: string;
    milestone?: Milestone;
    trigger: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const editing = !!milestone;

    const { data, setData, post, patch, processing, errors, clearErrors } =
        useForm({
            title: milestone?.title ?? '',
            description: milestone?.description ?? '',
            due_date: milestone?.due_on ?? '',
        });

    const onOpenChange = (next: boolean) => {
        setOpen(next);

        if (next) {
            clearErrors();
            setData({
                title: milestone?.title ?? '',
                description: milestone?.description ?? '',
                due_date: milestone?.due_on ?? '',
            });
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            patch(updateMilestone([projectUuid, milestone.id]).url, options);
        } else {
            post(storeMilestone(projectUuid).url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {editing ? 'Edit milestone' : 'New milestone'}
                    </DialogTitle>
                    <DialogDescription>
                        {editing
                            ? 'Update the title, description or due date.'
                            : 'Add a delivery milestone to this project.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="ms_title">Title</Label>
                        <Input
                            id="ms_title"
                            placeholder="e.g. Market Analysis"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="ms_desc">Description (optional)</Label>
                        <Textarea
                            id="ms_desc"
                            rows={3}
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="ms_due">Due date</Label>
                        <Input
                            id="ms_due"
                            type="date"
                            value={data.due_date}
                            onChange={(e) =>
                                setData('due_date', e.target.value)
                            }
                        />
                        <InputError message={errors.due_date} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => setOpen(false)}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner className="mr-2" />}
                            {editing ? 'Save changes' : 'Add milestone'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function MilestoneRow({
    projectUuid,
    milestone,
}: {
    projectUuid: string;
    milestone: Milestone;
}) {
    return (
        <li className="flex items-start gap-3 px-5 py-4">
            <span className="mt-0.5">
                <MilestoneIcon status={milestone.status} />
            </span>
            <div className="min-w-0 flex-1">
                <p className="font-medium">{milestone.title}</p>
                {milestone.description && (
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        {milestone.description}
                    </p>
                )}
                {milestone.due_date && (
                    <p className="mt-1 inline-flex items-center gap-1 text-xs text-muted-foreground">
                        <CalendarDays className="size-3.5" /> Due{' '}
                        {milestone.due_date}
                    </p>
                )}
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <Select
                    value={milestone.status}
                    onValueChange={(v) =>
                        router.patch(
                            milestoneStatus([projectUuid, milestone.id]).url,
                            { status: v },
                            { preserveScroll: true },
                        )
                    }
                >
                    <SelectTrigger className="h-8 w-36 text-xs">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {milestone.status_options.map((o) => (
                            <SelectItem key={o.value} value={o.value}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <MilestoneDialog
                    projectUuid={projectUuid}
                    milestone={milestone}
                    trigger={
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-8 text-muted-foreground hover:text-foreground"
                        >
                            <Pencil className="size-4" />
                        </Button>
                    }
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-8 text-muted-foreground hover:text-destructive"
                    onClick={() => {
                        if (confirm('Remove this milestone?')) {
                            router.delete(
                                destroyMilestone([projectUuid, milestone.id])
                                    .url,
                                { preserveScroll: true },
                            );
                        }
                    }}
                >
                    <Trash2 className="size-4" />
                </Button>
            </div>
        </li>
    );
}

export function MilestonesPanel({
    projectUuid,
    milestones,
}: {
    projectUuid: string;
    milestones: Milestone[];
}) {
    const aiEnabled = useAiEnabled();
    const [drafting, setDrafting] = useState(false);

    const draftPlan = () =>
        router.post(
            suggestMilestones(projectUuid).url,
            {},
            {
                preserveScroll: true,
                onStart: () => setDrafting(true),
                onFinish: () => setDrafting(false),
            },
        );

    return (
        <Panel
            flush
            eyebrow="Milestones"
            title="Milestone tracking"
            action={
                <div className="flex items-center gap-2">
                    {aiEnabled && (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={draftPlan}
                            disabled={drafting}
                            title="Draft a milestone plan from the brief"
                        >
                            {drafting ? (
                                <Spinner className="size-4" />
                            ) : (
                                <Sparkles className="size-4" />
                            )}
                            Draft with AI
                        </Button>
                    )}
                    <MilestoneDialog
                        projectUuid={projectUuid}
                        trigger={
                            <Button size="sm">
                                <Plus className="size-4" /> Add milestone
                            </Button>
                        }
                    />
                </div>
            }
        >
            <ul className="divide-y">
                {milestones.length === 0 ? (
                    <li className="px-5 py-8 text-center text-sm text-muted-foreground">
                        No milestones yet — add one with the button above.
                    </li>
                ) : (
                    milestones.map((m) => (
                        <MilestoneRow
                            key={m.id}
                            projectUuid={projectUuid}
                            milestone={m}
                        />
                    ))
                )}
            </ul>
        </Panel>
    );
}
