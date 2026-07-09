import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';

import { InvoiceDocument } from '@/components/invoice-document';
import { Button } from '@/components/ui/button';
import { invoices as invoicesIndex } from '@/routes/admin';
import { pdf } from '@/routes/admin/invoices';
import type { InvoiceDetail } from '@/types';

export default function InvoiceShow({ invoice }: { invoice: InvoiceDetail }) {
    return (
        <>
            <Head title={invoice.reference} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
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
