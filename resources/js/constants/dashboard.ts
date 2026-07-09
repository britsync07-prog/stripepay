/**
 * Colour treatment for each money segment on the dashboard financials — the
 * stacked composition bar (gradient) and the legend pills (dot).
 *
 * This is a sanctioned chart palette: four visually distinct hues chosen for
 * finance conventions (green = kept, amber = cost, blue = outflow). Status
 * colours elsewhere in the app must use the semantic tokens
 * (success/warning/info/destructive) instead of raw Tailwind palette classes.
 */
export const FINANCE_SEGMENTS = {
    netMargin: {
        gradient: 'bg-gradient-to-b from-emerald-400 to-emerald-600',
        dot: 'bg-emerald-500',
    },
    tax: {
        gradient: 'bg-gradient-to-b from-violet-400 to-violet-600',
        dot: 'bg-violet-500',
    },
    fees: {
        gradient: 'bg-gradient-to-b from-amber-400 to-amber-500',
        dot: 'bg-amber-500',
    },
    payouts: {
        gradient: 'bg-gradient-to-b from-sky-400 to-sky-600',
        dot: 'bg-sky-500',
    },
} as const;
