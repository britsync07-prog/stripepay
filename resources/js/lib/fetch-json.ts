export function csrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

export class HttpError extends Error {
    constructor(public status: number) {
        super(`Request failed with status ${status}`);
        this.name = 'HttpError';
    }
}

/**
 * Turn a failed AI request into a message that tells the user what to do.
 * 503 = not configured, 429 = rate-limited (wait), anything else = the caller's
 * generic fallback.
 */
export function aiErrorMessage(error: unknown, fallback: string): string {
    if (error instanceof HttpError) {
        if (error.status === 503) {
            return 'AI assistance is not configured yet.';
        }

        if (error.status === 429) {
            return 'The AI assistant is busy right now (usage limit reached). Please try again a bit later.';
        }
    }

    return fallback;
}

export async function postJson<T>(url: string, body: unknown): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new HttpError(response.status);
    }

    return response.json() as Promise<T>;
}
