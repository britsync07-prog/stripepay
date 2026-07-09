import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Option = { value: string; label: string };

type ExtraFilter = {
    param: string;
    value: string;
    options: Option[];
    allLabel: string;
};

/**
 * Search + status filter bar that drives **server-side** filtering: it pushes the
 * current filters to the URL (Inertia `router.get`, debounced search), so the
 * backend re-queries + re-paginates. State is preserved across reloads.
 */
export function ListFilters({
    url,
    search = '',
    status = '',
    statusOptions,
    extra,
    searchable = true,
    searchPlaceholder = 'Search…',
}: {
    url: string;
    search?: string;
    status?: string;
    statusOptions?: Option[];
    /** Optional additional filter selects (e.g. a record "type"). */
    extra?: ExtraFilter | ExtraFilter[];
    searchable?: boolean;
    searchPlaceholder?: string;
}) {
    const [term, setTerm] = useState(search);
    const firstRender = useRef(true);
    const extras = extra ? (Array.isArray(extra) ? extra : [extra]) : [];

    // Spatie QueryBuilder reads filters from `?filter[...]`. Empty values are
    // dropped so they don't clutter the URL.
    const apply = (
        over: {
            search?: string;
            status?: string;
            extras?: Record<string, string>;
        } = {},
    ) => {
        const nextSearch = over.search ?? term;
        const nextStatus = over.status ?? status;

        const filter: Record<string, string | undefined> = {
            search: nextSearch || undefined,
            status: nextStatus || undefined,
        };

        for (const e of extras) {
            filter[e.param] = (over.extras?.[e.param] ?? e.value) || undefined;
        }

        router.get(
            url,
            { filter },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    // Debounce the search box so we don't fire a request per keystroke.
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const id = setTimeout(() => apply({ search: term }), 350);

        return () => clearTimeout(id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [term]);

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            {searchable && (
                <div className="relative w-full sm:w-72">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={term}
                        onChange={(e) => setTerm(e.target.value)}
                        placeholder={searchPlaceholder}
                        className="pl-9"
                    />
                </div>
            )}

            {extras.map((e) => (
                <Select
                    key={e.param}
                    value={e.value || 'all'}
                    onValueChange={(v) =>
                        apply({ extras: { [e.param]: v === 'all' ? '' : v } })
                    }
                >
                    <SelectTrigger className="sm:w-48">
                        <SelectValue placeholder={e.allLabel} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{e.allLabel}</SelectItem>
                        {e.options.map((o) => (
                            <SelectItem key={o.value} value={o.value}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ))}

            {statusOptions && (
                <Select
                    value={status || 'all'}
                    onValueChange={(v) =>
                        apply({ status: v === 'all' ? '' : v })
                    }
                >
                    <SelectTrigger className="sm:w-48">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        {statusOptions.map((o) => (
                            <SelectItem key={o.value} value={o.value}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
        </div>
    );
}
