export interface QuotationLineItem {
    description: string;
    quantity: number;
    unitPrice: string;
    total: string;
}

export interface QuotationParty {
    name: string | null;
    company: string | null;
    email: string | null;
    country: string | null;
    signatoryName: string | null;
    signatoryTitle: string | null;
}

export interface Quotation {
    number: string;
    issuedAt: string | null;
    validUntil: string | null;
    validityDays: number | null;
    title: string;
    scopeItems: string[];
    lineItems: QuotationLineItem[];
    subtotal: string;
    taxLabel: string;
    total: string;
    deliveryTimeline: string | null;
    preparedBy: QuotationParty;
    preparedFor: QuotationParty;
    expired: boolean;
    paid: boolean;
}
