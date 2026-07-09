import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { extendTailwindMerge } from 'tailwind-merge';

// The dossier type utilities set a font size, so register them in the
// font-size group — otherwise tailwind-merge classifies unknown `text-*`
// classes as colors and drops them whenever a colour follows in cn().
const twMerge = extendTailwindMerge({
    extend: {
        classGroups: {
            'font-size': ['text-eyebrow', 'text-kicker', 'text-microcaps'],
        },
    },
});

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}
