import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Compass, Gauge, Sparkles } from 'lucide-react';
import { useState } from 'react';

import { AiAssistCard } from '@/components/consultation/ai-assist-card';
import { AssignmentCard } from '@/components/consultation/assignment-card';
import { AttachmentsCard } from '@/components/consultation/attachments-card';
import { ConsultationHero } from '@/components/consultation/consultation-hero';
import { DetailsCard } from '@/components/consultation/details-card';
import { FloatingAiDock } from '@/components/consultation/floating-ai-dock';
import { HistoryCard } from '@/components/consultation/history-card';
import type { LeadScore } from '@/components/consultation/lead-score-card';
import { LeadScoreCard } from '@/components/consultation/lead-score-card';
import { ManagePaymentLinkDialog } from '@/components/consultation/manage-payment-link-dialog';
import { NextStepsCard } from '@/components/consultation/next-steps-card';
import { NotesCard } from '@/components/consultation/notes-card';
import { PaymentLinkCard } from '@/components/consultation/payment-link-card';
import { PaymentsCard } from '@/components/consultation/payments-card';
import { QuotationEntryCard } from '@/components/consultation/quotation-entry-card';
import { WorkStatusCard } from '@/components/consultation/work-status-card';
import { useAiEnabled } from '@/hooks/use-ai-enabled';
import { consultations } from '@/routes/admin';
import type {
    Consultation,
    MediaFile,
    Payment,
    SelectOption,
    TimelineEvent,
} from '@/types';

export default function AdminConsultation({
    consultation,
    paymentLink,
    payments,
    workStatus,
    statusOptions,
    channelOptions,
    experts,
    assignedExpertId,
    project,
    invoice,
    attachments,
    timeline,
    leadScore,
}: {
    consultation: Consultation;
    paymentLink: {
        url: string;
        expiresAt: string | null;
        expired: boolean;
        isCustom: boolean;
        unpriced: boolean;
        isQuotation: boolean;
        quoteNumber: string | null;
        title: string | null;
        amount: string;
        expiresInDays: number;
    };
    payments?: Payment[];
    workStatus: string;
    statusOptions: SelectOption[];
    channelOptions: SelectOption[];
    experts: SelectOption[];
    assignedExpertId: string;
    project: { uuid: string; reference: string | null } | null;
    invoice: { uuid: string; reference: string | null } | null;
    attachments: MediaFile[];
    timeline?: TimelineEvent[];
    leadScore: LeadScore | null;
}) {
    const aiEnabled = useAiEnabled();
    const isPaid = consultation.is_paid;
    const [manageOpen, setManageOpen] = useState(false);

    return (
        <>
            <Head title={`Consultation ${consultation.reference}`} />
            <div className="flex flex-1 flex-col gap-5 p-4 sm:p-6">
                <Link
                    href={consultations()}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All consultations
                </Link>

                <ConsultationHero consultation={consultation} />

                <div className="grid grid-cols-1 items-start gap-4 xl:grid-cols-3">
                    {/* Main column — the record */}
                    <div className="flex min-w-0 flex-col gap-4 xl:col-span-2">
                        <DetailsCard consultation={consultation} />

                        <NotesCard
                            uuid={consultation.uuid}
                            channelOptions={channelOptions}
                            timeline={timeline}
                        />
                        <PaymentsCard payments={payments} />
                        <HistoryCard timeline={timeline} />
                    </div>

                    {/* Sidebar — action controls */}
                    <div className="flex min-w-0 flex-col gap-4">
                        {/* Custom requests are handled as full quotations. */}
                        {!isPaid && paymentLink.isCustom && (
                            <QuotationEntryCard
                                uuid={consultation.uuid}
                                hasQuotation={paymentLink.isQuotation}
                                quoteNumber={paymentLink.quoteNumber}
                            />
                        )}

                        {!isPaid && !paymentLink.unpriced && (
                            <PaymentLinkCard
                                uuid={consultation.uuid}
                                paymentLink={paymentLink}
                                onManage={
                                    paymentLink.isCustom &&
                                    !paymentLink.isQuotation
                                        ? () => setManageOpen(true)
                                        : undefined
                                }
                            />
                        )}

                        <AssignmentCard
                            uuid={consultation.uuid}
                            experts={experts}
                            assignedExpertId={assignedExpertId}
                            project={project}
                            invoice={invoice}
                        />

                        <WorkStatusCard
                            uuid={consultation.uuid}
                            workStatus={workStatus}
                            statusOptions={statusOptions}
                        />

                        <AttachmentsCard
                            uuid={consultation.uuid}
                            attachments={attachments}
                        />
                    </div>
                </div>
            </div>

            {aiEnabled && (
                <FloatingAiDock
                    items={[
                        {
                            key: 'ai-assist',
                            label: 'AI assist — draft proposal or email',
                            icon: Sparkles,
                            content: <AiAssistCard uuid={consultation.uuid} />,
                        },
                        {
                            key: 'next-steps',
                            label: 'Next steps',
                            icon: Compass,
                            content: <NextStepsCard uuid={consultation.uuid} />,
                        },
                        ...(leadScore
                            ? [
                                  {
                                      key: 'lead-score',
                                      label: 'Lead score',
                                      icon: Gauge,
                                      content: (
                                          <LeadScoreCard
                                              uuid={consultation.uuid}
                                              initial={leadScore}
                                          />
                                      ),
                                  },
                              ]
                            : []),
                    ]}
                />
            )}

            {paymentLink.isCustom && (
                <ManagePaymentLinkDialog
                    open={manageOpen}
                    onOpenChange={setManageOpen}
                    uuid={consultation.uuid}
                    initial={{
                        title: paymentLink.title ?? '',
                        amount: paymentLink.amount,
                        expires_in_days: paymentLink.expiresInDays,
                    }}
                />
            )}
        </>
    );
}
