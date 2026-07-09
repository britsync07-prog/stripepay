import { Banknote, Clock, Receipt } from 'lucide-react';

import { StatCard } from '@/components/stat-card';

export function PayoutStats({
    stats,
}: {
    stats: { totalPaid: string; count: number; pending: number };
}) {
    return (
        <div className="grid gap-4 sm:grid-cols-3">
            <StatCard
                label="Total paid"
                value={stats.totalPaid}
                icon={Banknote}
                tone="primary"
            />
            <StatCard
                label="Payouts"
                value={stats.count}
                icon={Receipt}
                tone="info"
            />
            <StatCard
                label="In progress"
                value={stats.pending}
                icon={Clock}
                tone="warning"
            />
        </div>
    );
}
