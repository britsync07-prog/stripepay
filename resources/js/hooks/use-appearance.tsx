/**
 * BlackDesck runs a single, fixed light brand theme derived from the logo.
 * OS/system dark switching is intentionally disabled — these helpers keep the
 * same public API as before so existing imports keep working, but always
 * resolve to `light` and never toggle the `dark` class.
 */
import { useSyncExternalStore } from 'react';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

const forceLight = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
};

export function initializeTheme(): void {
    forceLight();
}

export function useAppearance(): UseAppearanceReturn {
    // Subscribe to nothing — the theme never changes — but keep the hook shape.
    useSyncExternalStore(
        () => () => {},
        () => 'light' as const,
        () => 'light' as const,
    );

    return {
        appearance: 'light',
        resolvedAppearance: 'light',
        updateAppearance: forceLight,
    } as const;
}
