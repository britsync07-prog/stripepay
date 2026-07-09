import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { ExpertHero } from '@/components/expert/expert-hero';
import { PayoutHistory } from '@/components/expert/payout-history';
import { PayoutStats } from '@/components/expert/payout-stats';
import { experts as expertsIndex } from '@/routes/admin';
import type { Expert, Paginated, Payout, SelectOption } from '@/types';

interface Props {
    expert: Expert;
    payouts: Paginated<Payout>;
    filters: { status: string };
    stats: { totalPaid: string; count: number; pending: number };
    statusOptions: SelectOption[];
}

export default function ShowExpertDetails({
    expert,
    payouts,
    filters,
    stats,
    statusOptions,
}: Props) {
    return (
        <>
            <Head title={expert.name} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <Link
                    href={expertsIndex()}
                    className="inline-flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> All experts
                </Link>

                <ExpertHero expert={expert} />
                <PayoutStats stats={stats} />
                <PayoutHistory
                    expertId={expert.id}
                    payouts={payouts}
                    filters={filters}
                    statusOptions={statusOptions}
                />
            </div>
        </>
    );
}
