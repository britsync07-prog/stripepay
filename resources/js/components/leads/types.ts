import type { SelectOption } from '@/types';

export type BadgeTone =
    | 'default'
    | 'secondary'
    | 'destructive'
    | 'outline'
    | 'success'
    | 'warning';

export type Lead = {
    uuid: string;
    reference: string;
    stage: string;
    stage_label: string;
    stage_tone: BadgeTone;
    is_terminal: boolean;
    name: string | null;
    email: string | null;
    company: string | null;
    country: string | null;
    title: string | null;
    amount: string;
    unpriced: boolean;
    source: string | null;
    next_follow_up_at: string | null;
    follow_up_overdue: boolean;
    lost_reason: string | null;
    score: number | null;
    score_band: 'hot' | 'warm' | 'cold' | null;
    score_band_tone: BadgeTone | null;
    is_paid: boolean;
    created_at: string;
    consultation_url: string;
};

export type Column = {
    stage: string;
    label: string;
    tone: BadgeTone;
    count: number;
    capped: boolean;
    leads: Lead[];
};

export type StageOption = { value: string; label: string; tone: BadgeTone };

export type CalendarDay = {
    iso: string;
    day: number;
    inMonth: boolean;
    isToday: boolean;
    isPast: boolean;
    leads: Lead[];
};

export type FollowUpCalendarMonth = {
    month: string;
    prev: string;
    next: string;
    weeks: CalendarDay[][];
};

export type LeadsPageProps = {
    columns: Column[];
    stageOptions: StageOption[];
    countries: SelectOption[];
    filters: { search: string };
    stats: { total: number; open: number; overdue: number; won: number };
};
