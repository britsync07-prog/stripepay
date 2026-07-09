import { usePage } from '@inertiajs/react';

import type { GatewayRegistry } from '@/types';

/**
 * Payment gateway keys + render modes, shared globally from the backend
 * (HandleInertiaRequests). Use this instead of hardcoding gateway/mode strings.
 */
export function useGateways(): GatewayRegistry {
    return usePage().props.gatewayRegistry;
}
