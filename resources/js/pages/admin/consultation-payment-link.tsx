import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2 } from 'lucide-react';

import { PaymentLinkCard } from '@/components/consultation/payment-link-card';
import { QuotationBuilderCard } from '@/components/consultation/quotation-builder-card';
import type {
    QuotationInitial,
    TemplateOption,
} from '@/components/consultation/quotation-builder-card';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
import { consultation as consultationRoute } from '@/routes/admin';

type PaymentLink = {
    url: string;
    expiresAt: string | null;
    expired: boolean;
    isCustom: boolean;
    unpriced: boolean;
    isQuotation: boolean;
    quoteNumber: string | null;
};

type Consultation = {
    uuid: string;
    reference: string;
    name: string | null;
    email: string | null;
    amount: string;
    unpriced: boolean;
    is_paid: boolean;
};

export default function ConsultationPaymentLink({
    consultation,
    paymentLink,
    quotationInitial,
    templates,
}: {
    consultation: Consultation;
    paymentLink: PaymentLink;
    quotationInitial: QuotationInitial;
    templates: TemplateOption[];
}) {
    return (
        <>
            <Head title={`Quotation — ${consultation.reference}`} />
            <div className="flex flex-1 flex-col gap-5 p-4 sm:p-6">
                <Link
                    href={consultationRoute(consultation.uuid).url}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> Back to consultation
                </Link>

                <PageHeader
                    eyebrow={paymentLink.quoteNumber ?? consultation.reference}
                    title="Quotation & payment link"
                    description={
                        consultation.name
                            ? `Build the quotation and share the secure link with ${consultation.name}.`
                            : 'Build the quotation and share the secure link with the client.'
                    }
                />

                <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">
                    {consultation.is_paid ? (
                        <Panel title="Paid">
                            <div className="flex items-start gap-3 text-sm">
                                <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-success" />
                                <p className="text-muted-foreground">
                                    This request has been paid
                                    {consultation.amount
                                        ? ` (${consultation.amount})`
                                        : ''}
                                    . The quotation can no longer be changed.
                                </p>
                            </div>
                        </Panel>
                    ) : (
                        <QuotationBuilderCard
                            uuid={consultation.uuid}
                            initial={quotationInitial}
                            templates={templates}
                        />
                    )}

                    {!consultation.unpriced && (
                        <PaymentLinkCard
                            uuid={consultation.uuid}
                            paymentLink={paymentLink}
                        />
                    )}
                </div>
            </div>
        </>
    );
}
