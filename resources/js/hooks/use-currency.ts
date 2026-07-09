import { usePage } from '@inertiajs/react';

/**
 * The single system currency, shared from the backend
 * ({@link App\Support\Currency}). Money is always USD — never user-editable.
 */
export function useCurrency() {
    return usePage().props.currency;
}
