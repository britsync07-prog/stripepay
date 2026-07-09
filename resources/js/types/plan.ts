export type Plan = {
    id: number | null;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    currency: string;
    formatted_price: string;
    billing_interval: string;
    interval_label: string;
    features: string[];
    outcome: string | null;
    cta_label: string;
    is_featured: boolean;
    is_active: boolean;
    sort_order: number;
};
