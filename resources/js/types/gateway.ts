// Checkout rendering config for one payment rail (mirrors the backend
// GatewayDescriptor sent to the checkout page). Frontend-only type. `key` and
// `mode` stay plain strings — their canonical values are owned by the backend
// and shared via GatewayRegistry, not hardcoded here.
export type Gateway = {
    key: string;
    label: string;
    title: string;
    mode: string;
    sessionUrl: string;
    publishableKey?: string | null;
    clientId?: string | null;
    currency?: string | null;
    captureUrl?: string | null;
};

// Gateway keys + render modes, shared globally from the backend
// (HandleInertiaRequests). Each is a self-map, e.g. { stripe: 'stripe' } /
// { embedded: 'embedded' } — so the frontend reads keys/modes instead of
// declaring their string values.
export type GatewayRegistry = {
    keys: Record<string, string>;
    modes: Record<string, string>;
};
