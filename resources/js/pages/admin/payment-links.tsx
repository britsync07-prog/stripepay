import { Head } from '@inertiajs/react';
import { Check, Copy, FileText, Link2, Pencil, Wallet } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { ConsultationStatusBadge } from '@/components/consultation-status-badge';
import {
    DataTable,
    TableEmpty,
    TBody,
    TD,
    TH,
    THead,
    TR,
} from '@/components/data-table';
import { ListFilters } from '@/components/list-filters';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { CreatePaymentLinkDialog } from '@/components/payment-links/create-payment-link-dialog';
import { CreateQuotationLinkDialog } from '@/components/payment-links/create-quotation-link-dialog';
import type { QuotationTemplateOption } from '@/components/payment-links/create-quotation-link-dialog';
import {
    RowActionButton,
    RowActionLink,
    RowActions,
} from '@/components/row-actions';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { paymentLinks as paymentLinksRoute } from '@/routes/admin';
import type { Paginated } from '@/types';

type PaymentLinkRow = {
    uuid: string;
    reference: string;
    title: string;
    amount: string;
    status: string;
    status_label: string;
    is_paid: boolean;
    expired: boolean;
    url: string;
    manage_url: string;
    name: string | null;
    email: string | null;
    created_at: string | null;
};

interface Props {
    links: Paginated<PaymentLinkRow>;
    filters: { search: string; status: string };
    stats: { total: number; unpaid: number; paid: number };
    statusOptions: { value: string; label: string }[];
    templates: QuotationTemplateOption[];
}

function CopyLink({ url }: { url: string }) {
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            toast.success('Payment link copied');
            setTimeout(() => setCopied(false), 2000);
        } catch {
            toast.error('Could not copy — copy it manually');
        }
    };

    return (
        <RowActionButton
            icon={copied ? Check : Copy}
            label="Copy link"
            onClick={copy}
        />
    );
}

export default function AdminPaymentLinks({
    links,
    filters,
    stats,
    statusOptions,
    templates,
}: Props) {
    const [creating, setCreating] = useState(false);
    const [quoting, setQuoting] = useState(false);
    const rows = links.data;

    return (
        <>
            <Head title="Payment Links" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Get paid"
                    title="Payment Links"
                    description="Set a price, share the link, and let the client pay and fill in their own details."
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                onClick={() => setQuoting(true)}
                            >
                                <FileText className="size-4" />
                                New quotation
                            </Button>
                            <Button onClick={() => setCreating(true)}>
                                <Link2 className="size-4" />
                                New payment link
                            </Button>
                        </div>
                    }
                />

                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        compact
                        label="All links"
                        value={stats.total}
                        icon={Link2}
                        tone="info"
                    />
                    <StatCard
                        compact
                        label="Awaiting payment"
                        value={stats.unpaid}
                        icon={Wallet}
                        tone="warning"
                    />
                    <StatCard
                        compact
                        label="Paid"
                        value={stats.paid}
                        icon={Check}
                        tone="success"
                    />
                </div>

                <DataTable
                    toolbar={
                        <ListFilters
                            url={paymentLinksRoute().url}
                            search={filters.search}
                            status={filters.status}
                            statusOptions={statusOptions}
                            searchPlaceholder="Search by reference, label or client…"
                        />
                    }
                    footer={
                        links.total > 0 ? (
                            <Pagination
                                links={links.links}
                                from={links.from}
                                to={links.to}
                                total={links.total}
                            />
                        ) : undefined
                    }
                >
                    <THead>
                        <tr>
                            <TH>Reference</TH>
                            <TH>Label</TH>
                            <TH align="right">Amount</TH>
                            <TH>Status</TH>
                            <TH>Paid by</TH>
                            <TH>Created</TH>
                            <TH>
                                <span className="sr-only">Actions</span>
                            </TH>
                        </tr>
                    </THead>
                    <TBody>
                        {rows.length === 0 ? (
                            <TableEmpty
                                colSpan={7}
                                icon={Link2}
                                title="No payment links yet"
                                description="Create a link, set a price, and share it with your client."
                            />
                        ) : (
                            rows.map((link) => (
                                <TR key={link.uuid}>
                                    <TD className="font-mono text-xs">
                                        {link.reference}
                                    </TD>
                                    <TD>{link.title}</TD>
                                    <TD
                                        align="right"
                                        className="font-mono tabular-nums"
                                    >
                                        {link.amount}
                                    </TD>
                                    <TD>
                                        {link.expired && !link.is_paid ? (
                                            <Badge variant="destructive">
                                                Expired
                                            </Badge>
                                        ) : (
                                            <ConsultationStatusBadge
                                                status={link.status}
                                                label={link.status_label}
                                            />
                                        )}
                                    </TD>
                                    <TD>
                                        {link.is_paid && link.name ? (
                                            <>
                                                <div className="font-medium">
                                                    {link.name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {link.email}
                                                </div>
                                            </>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                —
                                            </span>
                                        )}
                                    </TD>
                                    <TD className="text-muted-foreground">
                                        {link.created_at ?? '—'}
                                    </TD>
                                    <TD align="right">
                                        <RowActions>
                                            {!link.is_paid && (
                                                <CopyLink url={link.url} />
                                            )}
                                            <RowActionLink
                                                href={link.manage_url}
                                                icon={Pencil}
                                                label="Manage link"
                                            />
                                        </RowActions>
                                    </TD>
                                </TR>
                            ))
                        )}
                    </TBody>
                </DataTable>
            </div>

            <CreatePaymentLinkDialog
                open={creating}
                onOpenChange={setCreating}
            />

            <CreateQuotationLinkDialog
                open={quoting}
                onOpenChange={setQuoting}
                templates={templates}
            />
        </>
    );
}
