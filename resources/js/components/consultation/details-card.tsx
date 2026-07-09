import { Check } from 'lucide-react';

import { Detail, Section } from '@/components/consultation/section';
import type { Consultation } from '@/types';

export function DetailsCard({ consultation }: { consultation: Consultation }) {
    const hasFeatures = consultation.features.length > 0;

    return (
        <Section title="Overview">
            {consultation.description ? (
                <p className="text-sm leading-relaxed break-words whitespace-pre-line text-muted-foreground">
                    {consultation.description}
                </p>
            ) : (
                <p className="text-sm text-muted-foreground italic">
                    No brief was provided with this request.
                </p>
            )}

            {hasFeatures && (
                <div className="mt-5">
                    <p className="mb-2 text-xs tracking-wide text-muted-foreground uppercase">
                        What&apos;s included
                    </p>
                    <ul className="grid gap-1.5 sm:grid-cols-2">
                        {consultation.features.map((feature) => (
                            <li
                                key={feature}
                                className="flex items-start gap-2 text-sm"
                            >
                                <Check className="mt-0.5 size-4 shrink-0 text-success" />
                                <span>{feature}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            <dl className="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 border-t pt-4 sm:grid-cols-3">
                <Detail
                    label={consultation.paid_at ? 'Purchased' : 'Requested'}
                    value={consultation.created_at}
                />
                <Detail label="Paid on" value={consultation.paid_at} />
                <Detail label="Expert" value={consultation.assigned_expert} />
            </dl>
        </Section>
    );
}
