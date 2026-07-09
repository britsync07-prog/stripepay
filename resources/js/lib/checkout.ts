function readCookie(name: string): string {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : '';
}

export type CheckoutResponse = {
    httpStatus: number;
    ok?: boolean;
    id?: string;
    clientSecret?: string | null;
    redirect?: string;
    message?: string;
};

/**
 * POST JSON to a checkout endpoint with the CSRF token, tolerating non-JSON
 * error pages. Shared by every gateway rail (Stripe session, PayPal order/capture).
 */
export async function postJson(
    url: string,
    body?: Record<string, unknown>,
): Promise<CheckoutResponse> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': readCookie('XSRF-TOKEN'),
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    let data: Record<string, unknown> = {};

    try {
        data = await response.json();
    } catch {
        // Non-JSON response (e.g. a 404/419/500 HTML page).
    }

    if (!response.ok) {
        // Surfaced in the browser console to make failures diagnosable
        // (404 = stale/missing consultation, 419 = CSRF, 5xx = server).
        console.error(`[checkout] POST ${url} → ${response.status}`, data);
    }

    return { httpStatus: response.status, ...data };
}
