import type { Auth } from '@/types/auth';
import type { GatewayRegistry } from '@/types/gateway';
import type { NotificationFeed } from '@/types/notification';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            notifications: NotificationFeed | null;
            gatewayRegistry: GatewayRegistry;
            currency: { code: string; symbol: string };
            company: {
                name: string;
                email: string | null;
                address: string | null;
                vat: string | null;
                footer: string | null;
            };
            uploads: { accept: Record<string, Record<string, string[]>> };
            aiEnabled: boolean;
            [key: string]: unknown;
        };
    }
}
