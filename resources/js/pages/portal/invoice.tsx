import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';

import { InvoiceDocument } from '@/components/invoice-document';
import { Button } from '@/components/ui/button';
import { invoices as invoicesIndex } from '@/routes/portal';
import { pdf } from '@/routes/portal/invoice';
import type { InvoiceDetail } from '@/types';

export default function PortalInvoice({ invoice }: { invoice: InvoiceDetail }) {
    return (
        <>
            <Head title={invoice.reference} />
            <div className="animate-fade-up space-y-6">
                <div className="flex items-center justify-between gap-3">
                    <Link
                        href={invoicesIndex()}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" /> All invoices
                    </Link>
                    <Button asChild variant="outline">
                        <a href={pdf(invoice.uuid).url}>
                            <Download className="size-4" /> Download PDF
                        </a>
                    </Button>
                </div>

                <div className="mx-auto w-full max-w-3xl">
                    <InvoiceDocument invoice={invoice} />
                </div>
            </div>
        </>
    );
}
