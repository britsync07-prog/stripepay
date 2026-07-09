import {
    AlarmClock,
    AlertTriangle,
    ArrowRightLeft,
    Bell,
    CheckCircle2,
    DollarSign,
    Send,
    Sparkles,
    Undo2,
    UserCog,
    XCircle,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

type NotificationMeta = { icon: LucideIcon; chip: string };

/** Icon + tinted chip per notification `type` (data.type from the backend). */
const NOTIFICATION_META: Record<string, NotificationMeta> = {
    consultation_paid: { icon: DollarSign, chip: 'bg-success/12 text-success' },
    payment_received: {
        icon: CheckCircle2,
        chip: 'bg-success/12 text-success',
    },
    payment_refunded: {
        icon: Undo2,
        chip: 'bg-destructive/10 text-destructive',
    },
    payment_disputed: {
        icon: AlertTriangle,
        chip: 'bg-destructive/10 text-destructive',
    },
    payment_failed: {
        icon: XCircle,
        chip: 'bg-destructive/10 text-destructive',
    },
    consultation_status: { icon: ArrowRightLeft, chip: 'bg-info/12 text-info' },
    consultation_assigned: {
        icon: UserCog,
        chip: 'bg-primary/12 text-primary',
    },
    custom_plan_requested: {
        icon: Sparkles,
        chip: 'bg-warning/15 text-warning-foreground dark:text-warning',
    },
    payment_link: { icon: Send, chip: 'bg-info/12 text-info' },
    lead_follow_up: {
        icon: AlarmClock,
        chip: 'bg-warning/15 text-warning-foreground dark:text-warning',
    },
};

const FALLBACK: NotificationMeta = {
    icon: Bell,
    chip: 'bg-muted text-muted-foreground',
};

export function notificationMeta(type: string | null): NotificationMeta {
    return (type && NOTIFICATION_META[type]) || FALLBACK;
}
