export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** Mirrors Laravel's `LengthAwarePaginator` JSON shape (server-side pagination). */
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
};
