import { router } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { quotation as quotationRoute } from '@/routes/admin/payment-links';

export interface QuotationTemplateOption {
    uuid: string;
    name: string;
    total: string;
}

const BLANK = 'blank';

/**
 * Creates a client-less quotation link — optionally pre-filled from a template.
 * No client details needed here: the buyer fills their own "Prepared for" info
 * on the quotation page and pays. Lands the admin on the builder to review.
 */
export function CreateQuotationLinkDialog({
    open,
    onOpenChange,
    templates,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    templates: QuotationTemplateOption[];
}) {
    const [template, setTemplate] = useState<string>(BLANK);
    const [submitting, setSubmitting] = useState(false);

    const submit = () => {
        setSubmitting(true);
        router.post(
            quotationRoute().url,
            { template: template === BLANK ? undefined : template },
            {
                preserveScroll: true,
                onFinish: () => setSubmitting(false),
                onSuccess: () => onOpenChange(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>New quotation link</DialogTitle>
                    <DialogDescription>
                        No client needed — they fill in their own details on the
                        quotation and pay. Start from a template or a blank
                        quotation; you can tweak everything next.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-2">
                    <Label>Start from</Label>
                    <Select value={template} onValueChange={setTemplate}>
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={BLANK}>
                                Blank quotation
                            </SelectItem>
                            {templates.map((t) => (
                                <SelectItem key={t.uuid} value={t.uuid}>
                                    {t.name} · {t.total}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {templates.length === 0 && (
                        <p className="text-xs text-muted-foreground">
                            Tip: create reusable templates under Quotation
                            Templates to fill this faster.
                        </p>
                    )}
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={submit}
                        disabled={submitting}
                    >
                        <FileText className="size-4" />
                        Create quotation
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
