export type Payout = {
    id: number;
    expert: string | null;
    amount: string;
    method: string;
    method_label: string;
    status: string;
    status_label: string;
    reference: string | null;
    note: string | null;
    paid_at: string | null;
    created_at: string | null;
    proof_url: string | null;
};
