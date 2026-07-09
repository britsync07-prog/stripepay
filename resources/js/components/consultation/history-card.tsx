import { Deferred } from '@inertiajs/react';

import { CountBadge, Section } from '@/components/consultation/section';
import {
    TimelineList,
    TimelineSkeleton,
} from '@/components/consultation/timeline';
import { TRANSITION } from '@/constants/consultation';
import type { TimelineEvent } from '@/types';

export function HistoryCard({ timeline }: { timeline?: TimelineEvent[] }) {
    const history = timeline?.filter((e) => TRANSITION[e.type]) ?? [];

    return (
        <Section title="History" badge={<CountBadge count={history.length} />}>
            <Deferred data="timeline" fallback={<TimelineSkeleton />}>
                <TimelineList
                    items={history}
                    empty="No status or assignment changes yet."
                />
            </Deferred>
        </Section>
    );
}
