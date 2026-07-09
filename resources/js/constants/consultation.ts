import {
    Mail,
    MessageSquare,
    Phone,
    RefreshCw,
    UserCog,
    Video,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export const CHANNEL_ICONS: Record<string, LucideIcon> = {
    note: MessageSquare,
    phone: Phone,
    video: Video,
    email: Mail,
};

export const CONTACT_META: Record<
    string,
    { label: string; icon: LucideIcon; chip: string; text: string }
> = {
    note: {
        label: 'Note',
        icon: MessageSquare,
        chip: 'bg-muted',
        text: 'text-muted-foreground',
    },
    phone: {
        label: 'Phone call',
        icon: Phone,
        chip: 'bg-success/10',
        text: 'text-success',
    },
    video: {
        label: 'Video call',
        icon: Video,
        chip: 'bg-info/10',
        text: 'text-info',
    },
    email: {
        label: 'Email',
        icon: Mail,
        chip: 'bg-warning/15',
        text: 'text-warning-foreground dark:text-warning',
    },
};

export const TRANSITION: Record<string, { label: string; icon: LucideIcon }> = {
    status: { label: 'Status', icon: RefreshCw },
    assigned: { label: 'Expert', icon: UserCog },
};
