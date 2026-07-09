export type Expert = {
    id: number;
    name: string;
    email: string | null;
    specialization: string | null;
    country: string | null;
    is_active: boolean;
    preferred_payout_method: string;
    payout_notes: string | null;
    stripe_account_id: string | null;
    stripe_payouts_enabled: boolean;
};

/** Slim projection used by the experts index table (see ExpertListData). */
export type ExpertListItem = {
    id: number;
    name: string;
    email: string | null;
    specialization: string | null;
    is_active: boolean;
    has_payout_account: boolean;
    stripe_payouts_enabled: boolean;
};
