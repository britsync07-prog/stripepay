export type Payment = {
    gateway: string;
    amount: string;
    status: string;
    plan: string | null;
    reference: string | null;
    consultation: string | null;
    date: string | null;
};
