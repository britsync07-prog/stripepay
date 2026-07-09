import { router } from '@inertiajs/react';
import { Check, Clock, Copy, Mail, Pencil } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Section } from '@/components/consultation/section';
import { Button } from '@/components/ui/button';
import { sendLink as sendLinkRoute } from '@/routes/admin/consultations';

type PaymentLink = {
    url: string;
    expiresAt: string | null;
    expired: boolean;
};

export function PaymentLinkCard({
    uuid,
    paymentLink,
    onManage,
}: {
    uuid: string;
    paymentLink: PaymentLink;
    /** When set, shows a "Manage" button (opens the in-place price editor). */
    onManage?: () => void;
}) {
    const [copied, setCopied] = useState(false);

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(paymentLink.url);
            setCopied(true);
            toast.success('Payment link copied');
            setTimeout(() => setCopied(false), 2000);
        } catch {
            toast.error('Could not copy — copy it manually');
        }
    };

    const emailLink = () => {
        router.post(sendLinkRoute(uuid).url, {}, { preserveScroll: true });
    };

    return (
        <Section title="Payment link" accent>
            <p className="mb-3 text-sm text-muted-foreground">
                Share this secure link so the client can pay.
                {paymentLink.expiresAt && !paymentLink.expired && (
                    <span className="mt-1 flex items-center gap-1.5 text-xs">
                        <Clock className="size-3.5" />
                        Valid until {paymentLink.expiresAt}
                    </span>
                )}
                {paymentLink.expired && (
                    <span className="mt-1 flex items-center gap-1.5 text-xs text-destructive">
                        <Clock className="size-3.5" />
                        This link has expired — create a new request.
                    </span>
                )}
            </p>
            <div className="flex items-center gap-2 rounded-xl border bg-muted/40 px-3 py-2">
                <span className="min-w-0 flex-1 truncate font-mono text-xs text-muted-foreground">
                    {paymentLink.url}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-7 shrink-0"
                    aria-label="Copy link"
                    onClick={copyLink}
                >
                    {copied ? (
                        <Check className="size-4 text-success" />
                    ) : (
                        <Copy className="size-4" />
                    )}
                </Button>
            </div>
            <div className="mt-3 flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={copyLink}
                >
                    <Copy className="size-4" /> Copy link
                </Button>
                <Button
                    type="button"
                    size="sm"
                    onClick={emailLink}
                    disabled={paymentLink.expired}
                >
                    <Mail className="size-4" /> Email client
                </Button>
                {onManage && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={onManage}
                    >
                        <Pencil className="size-4" /> Manage
                    </Button>
                )}
            </div>
        </Section>
    );
}
