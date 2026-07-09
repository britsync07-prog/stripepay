import { Globe } from 'lucide-react';

import { EmptyState } from '@/components/empty-state';
import { Panel } from '@/components/panel';

import { BarList } from './bar-list';

export function CountriesPanel({
    countries,
}: {
    countries: { name: string; total: number }[];
}) {
    return (
        <Panel title="Consultations by country" className="h-full">
            {countries.length === 0 ? (
                <EmptyState
                    icon={Globe}
                    title="No countries yet"
                    description="Client locations appear here once consultations come in."
                    className="py-10"
                />
            ) : (
                <BarList
                    rows={countries.map((c) => ({
                        label: c.name,
                        raw: c.total,
                        display: String(c.total),
                    }))}
                />
            )}
        </Panel>
    );
}
