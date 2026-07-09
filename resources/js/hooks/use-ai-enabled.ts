import { usePage } from '@inertiajs/react';

/**
 * Whether AI assistance is configured, shared from the backend
 * ({@link App\Services\AI\Gemini::configured}). Admin AI surfaces gate on this.
 */
export function useAiEnabled(): boolean {
    return usePage().props.aiEnabled;
}
