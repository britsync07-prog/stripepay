import { Link, router } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { ArrowRight, EyeOff, FileText, FolderKanban } from 'lucide-react';

import { Section } from '@/components/consultation/section';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { assign as assignRoute } from '@/routes/admin/consultations';
import { show as invoiceShowRoute } from '@/routes/admin/invoices';
import { show as projectShowRoute } from '@/routes/admin/projects';
import type { SelectOption } from '@/types';

type LinkedRecord = { uuid: string; reference: string | null } | null;

function LinkedRecordLink({
    href,
    icon: Icon,
    title,
    reference,
}: {
    href: string;
    icon: LucideIcon;
    title: string;
    reference: string | null;
}) {
    return (
        <Link
            href={href}
            className="group flex items-center gap-3 rounded-xl border bg-gradient-to-br from-muted/50 to-transparent px-3.5 py-3 transition-all hover:border-primary/40 hover:shadow-card"
        >
            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Icon className="size-[18px]" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block text-sm font-medium">{title}</span>
                {reference && (
                    <span className="block truncate font-mono text-xs text-muted-foreground">
                        {reference}
                    </span>
                )}
            </span>
            <ArrowRight className="size-4 shrink-0 text-muted-foreground transition-all group-hover:translate-x-0.5 group-hover:text-primary" />
        </Link>
    );
}

export function AssignmentCard({
    uuid,
    experts,
    assignedExpertId,
    project,
    invoice,
}: {
    uuid: string;
    experts: SelectOption[];
    assignedExpertId: string;
    project: LinkedRecord;
    invoice: LinkedRecord;
}) {
    const assignExpert = (value: string) => {
        router.post(
            assignRoute(uuid).url,
            { expert_id: value === 'none' ? null : Number(value) },
            { preserveScroll: true },
        );
    };

    return (
        <Section
            title="Assignment"
            accent
            badge={
                <Badge variant="secondary" className="gap-1">
                    <EyeOff className="size-3" /> Internal
                </Badge>
            }
        >
            <div className="grid gap-1.5">
                <Label className="text-xs">Assigned expert</Label>
                <Select value={assignedExpertId} onValueChange={assignExpert}>
                    <SelectTrigger className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">Unassigned</SelectItem>
                        {experts.map((ex) => (
                            <SelectItem key={ex.value} value={ex.value}>
                                {ex.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="mt-3 grid gap-1.5">
                <Label className="text-xs">Project</Label>
                {project ? (
                    <LinkedRecordLink
                        href={projectShowRoute(project.uuid).url}
                        icon={FolderKanban}
                        title="View project"
                        reference={project.reference}
                    />
                ) : (
                    <p className="rounded-xl border border-dashed px-3 py-2 text-xs text-muted-foreground">
                        A project is created automatically once this
                        consultation is paid.
                    </p>
                )}
            </div>

            <div className="mt-3 grid gap-1.5">
                <Label className="text-xs">Invoice</Label>
                {invoice ? (
                    <LinkedRecordLink
                        href={invoiceShowRoute(invoice.uuid).url}
                        icon={FileText}
                        title="View invoice"
                        reference={invoice.reference}
                    />
                ) : (
                    <p className="rounded-xl border border-dashed px-3 py-2 text-xs text-muted-foreground">
                        An invoice is issued automatically once this
                        consultation is paid.
                    </p>
                )}
            </div>
        </Section>
    );
}
