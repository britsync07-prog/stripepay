import { Badge } from '@/components/ui/badge';
import type { Expert } from '@/types';

export function PayoutReadiness({ expert }: { expert: Expert }) {
    if (expert.stripe_payouts_enabled) {
        return <Badge variant="success">Payouts ready</Badge>;
    }

    if (expert.stripe_account_id) {
        return <Badge variant="secondary">Onboarding pending</Badge>;
    }

    return <Badge variant="outline">Payouts not set up</Badge>;
}
