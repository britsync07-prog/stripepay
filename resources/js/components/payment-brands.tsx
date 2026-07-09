import { cn } from '@/lib/utils';

/**
 * Small, theme-safe payment brand marks (white chips so they stay legible on any
 * background). Used in the checkout gateway selector.
 */

const CHIP = 'h-5 w-auto shrink-0';

export function VisaMark({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 40 20"
            className={cn(CHIP, className)}
            role="img"
            aria-label="Visa"
        >
            <rect width="40" height="20" rx="4" fill="#fff" stroke="#e5e7eb" />
            <text
                x="20"
                y="14"
                textAnchor="middle"
                fontFamily="Helvetica, Arial, sans-serif"
                fontSize="11"
                fontWeight="800"
                fontStyle="italic"
                letterSpacing="0.4"
                fill="#1A1F71"
            >
                VISA
            </text>
        </svg>
    );
}

export function MastercardMark({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 40 20"
            className={cn(CHIP, className)}
            role="img"
            aria-label="Mastercard"
        >
            <rect width="40" height="20" rx="4" fill="#fff" stroke="#e5e7eb" />
            <circle cx="17" cy="10" r="6" fill="#EB001B" />
            <circle cx="23" cy="10" r="6" fill="#F79E1B" />
            <path d="M20 5.4a6 6 0 0 0 0 9.2 6 6 0 0 0 0-9.2Z" fill="#FF5F00" />
        </svg>
    );
}

export function AmexMark({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 40 20"
            className={cn(CHIP, className)}
            role="img"
            aria-label="American Express"
        >
            <rect width="40" height="20" rx="4" fill="#1F72CD" />
            <text
                x="20"
                y="13"
                textAnchor="middle"
                fontFamily="Helvetica, Arial, sans-serif"
                fontSize="6.5"
                fontWeight="800"
                letterSpacing="0.5"
                fill="#fff"
            >
                AMEX
            </text>
        </svg>
    );
}

export function PaypalMark({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 64 20"
            className={cn('h-6 w-auto shrink-0', className)}
            role="img"
            aria-label="PayPal"
        >
            <rect width="64" height="20" rx="4" fill="#fff" stroke="#e5e7eb" />
            <text
                x="32"
                y="14.5"
                textAnchor="middle"
                fontFamily="Helvetica, Arial, sans-serif"
                fontSize="12"
                fontWeight="800"
                fontStyle="italic"
                letterSpacing="-0.2"
            >
                <tspan fill="#003087">Pay</tspan>
                <tspan fill="#0070e0">Pal</tspan>
            </text>
        </svg>
    );
}

export function CardBrands({ className }: { className?: string }) {
    return (
        <span className={cn('flex items-center gap-1.5', className)}>
            <VisaMark />
            <MastercardMark />
            <AmexMark />
        </span>
    );
}
