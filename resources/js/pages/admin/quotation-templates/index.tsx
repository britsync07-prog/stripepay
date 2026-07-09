import { Head, Link, router } from '@inertiajs/react';
import { FileStack, Pencil, Plus, Trash2 } from 'lucide-react';

import {
    DataTable,
    TableEmpty,
    TBody,
    TD,
    TH,
    THead,
    TR,
} from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import {
    RowActionButton,
    RowActionLink,
    RowActions,
} from '@/components/row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, destroy, edit } from '@/routes/admin/quotation-templates';

interface Template {
    uuid: string;
    name: string;
    title: string;
    total: string;
    lineItemCount: number;
    validDays: number;
    isActive: boolean;
}

export default function QuotationTemplates({
    templates,
}: {
    templates: Template[];
}) {
    const remove = (t: Template) => {
        if (!confirm(`Delete the “${t.name}” template?`)) {
            return;
        }

        router.delete(destroy(t.uuid).url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Quotation templates" />
            <div className="flex flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Configuration"
                    title="Quotation templates"
                    description="Reusable scope + line-item presets. Drop one onto a quotation to skip the retyping."
                    actions={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" /> New template
                            </Link>
                        </Button>
                    }
                />

                <DataTable>
                    <THead>
                        <TR>
                            <TH>Name</TH>
                            <TH>Title</TH>
                            <TH className="text-right">Items</TH>
                            <TH className="text-right">Total</TH>
                            <TH>Validity</TH>
                            <TH>Status</TH>
                            <TH className="text-right">Actions</TH>
                        </TR>
                    </THead>
                    <TBody>
                        {templates.length === 0 ? (
                            <TableEmpty
                                colSpan={7}
                                icon={FileStack}
                                title="No templates yet"
                                description="Create a template for offers you send often — like a Strategic Business Assessment."
                            />
                        ) : (
                            templates.map((t) => (
                                <TR key={t.uuid}>
                                    <TD className="font-medium">{t.name}</TD>
                                    <TD className="text-muted-foreground">
                                        {t.title}
                                    </TD>
                                    <TD className="text-right font-mono tabular-nums">
                                        {t.lineItemCount}
                                    </TD>
                                    <TD className="text-right font-mono font-semibold tabular-nums">
                                        {t.total}
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {t.validDays} days
                                    </TD>
                                    <TD>
                                        <Badge
                                            variant={
                                                t.isActive
                                                    ? 'success'
                                                    : 'secondary'
                                            }
                                        >
                                            {t.isActive ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </TD>
                                    <TD className="text-right">
                                        <RowActions>
                                            <RowActionLink
                                                href={edit(t.uuid).url}
                                                icon={Pencil}
                                                label="Edit"
                                            />
                                            <RowActionButton
                                                icon={Trash2}
                                                onClick={() => remove(t)}
                                                label="Delete"
                                                tone="danger"
                                            />
                                        </RowActions>
                                    </TD>
                                </TR>
                            ))
                        )}
                    </TBody>
                </DataTable>
            </div>
        </>
    );
}
