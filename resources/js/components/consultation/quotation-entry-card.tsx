import { Link } from '@inertiajs/react';
import { ArrowRight, FileText } from 'lucide-react';

import { Section } from '@/components/consultation/section';
import { Button } from '@/components/ui/button';
import { paymentLink as builderRoute } from '@/routes/admin/consultation';

/**
 * The admin's entry point to the quotation builder for a custom request — shown
 * on the consultation sidebar. Whether the quotation is still to be created or
 * already issued, this is the one-click way into the full builder page.
 */
export function QuotationEntryCard({
    uuid,
    hasQuotation,
    quoteNumber,
}: {
    uuid: string;
    hasQuotation: boolean;
    quoteNumber: string | null;
}) {
    return (
        <Section title="Quotation" accent>
            <p className="mb-3 text-sm text-muted-foreground">
                {hasQuotation
                    ? 'Edit the scope, line items and validity of this quotation.'
                    : 'Compose a formal quotation — scope of services, line items and validity. A document number is assigned automatically.'}
            </p>

            {quoteNumber && (
                <p className="mb-3 flex items-center gap-2 text-sm">
                    <FileText className="size-4 text-primary" />
                    <span className="font-mono font-medium">{quoteNumber}</span>
                </p>
            )}

            <Button asChild className="justify-self-start">
                <Link href={builderRoute(uuid).url}>
                    {hasQuotation ? 'Edit quotation' : 'Build quotation'}
                    <ArrowRight className="ml-1 size-4" />
                </Link>
            </Button>
        </Section>
    );
}
