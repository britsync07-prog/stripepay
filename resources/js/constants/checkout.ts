import { Check, Lock, ShieldCheck } from 'lucide-react';

export const TRUST = [
    { icon: ShieldCheck, label: 'Stripe & PayPal secured' },
    { icon: Lock, label: 'Encrypted payment' },
    { icon: Check, label: 'No account required' },
] as const;
