import type { BadgeTone } from '@/types/project';

export type InvoiceListItem = {
    uuid: string;
    reference: string;
    client: string | null;
    status: string;
    status_label: string;
    status_tone: BadgeTone;
    total: string;
    issued_at: string | null;
    created_at: string | null;
};

export type InvoiceItem = {
    id: number;
    description: string;
    quantity: number;
    unit_amount: string;
    amount: string;
};

export type InvoiceDetail = {
    uuid: string;
    reference: string;
    client: string | null;
    consultation_reference: string | null;
    status: string;
    status_label: string;
    status_tone: BadgeTone;
    subtotal: string;
    tax_rate: string;
    tax_amount: string;
    total: string;
    currency: string;
    notes: string | null;
    issued_at: string | null;
    paid_at: string | null;
    created_at: string | null;
    items: InvoiceItem[];
};
