export type TimelineEvent = {
    id: number;
    type: string;
    message: string | null;
    duration: number | null;
    from: string | null;
    to: string | null;
    by: string | null;
    at: string | null;
};
