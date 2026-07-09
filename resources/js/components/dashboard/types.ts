export type RangeKey = '3m' | '6m' | '12m' | 'all';

export const RANGE_OPTIONS: { value: RangeKey; label: string }[] = [
    { value: '3m', label: '3m' },
    { value: '6m', label: '6m' },
    { value: '12m', label: '12m' },
    { value: 'all', label: 'All' },
];

export const RANGE_LABELS: Record<RangeKey, string> = {
    '3m': 'Last 3 months',
    '6m': 'Last 6 months',
    '12m': 'Last 12 months',
    all: 'All-time',
};

export interface Stats {
    receivedThisMonth: string;
    receivedDelta: number | null;
    avgDeal: string;
    clients: number;
    consultants: number;
    projectsActive: number;
    projectsCompleted: number;
}

export interface GatewayRow {
    gateway: string;
    label: string;
    count: number;
    gross: string;
    fees: string;
    net: string;
    grossRaw: number;
    feesRaw: number;
    netRaw: number;
}

export interface Composition {
    received: number;
    tax: number;
    fees: number;
    paidOut: number;
    netMargin: number;
    received_label: string;
    tax_label: string;
    fees_label: string;
    paidOut_label: string;
    netMargin_label: string;
}

export interface LeadsPipeline {
    stages: { label: string; count: number }[];
    openValue: string;
    won: number;
    lost: number;
    winRate: number | null;
}

export interface PlanRevenueRow {
    plan: string;
    total: number;
    total_label: string;
}
