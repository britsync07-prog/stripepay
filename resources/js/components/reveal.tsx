import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

const prefersReducedMotion = (): boolean =>
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function Reveal({
    children,
    className,
    delay = 0,
}: {
    children: ReactNode;
    className?: string;
    delay?: number;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const [shown, setShown] = useState(prefersReducedMotion);

    useEffect(() => {
        const el = ref.current;

        if (shown || !el) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setShown(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.15, rootMargin: '0px 0px -10% 0px' },
        );

        observer.observe(el);

        return () => observer.disconnect();
    }, [shown]);

    return (
        <div
            ref={ref}
            style={{ transitionDelay: shown ? `${delay}ms` : '0ms' }}
            className={cn(
                'transition-all duration-700 ease-out motion-reduce:transition-none',
                shown ? 'translate-y-0 opacity-100' : 'translate-y-5 opacity-0',
                className,
            )}
        >
            {children}
        </div>
    );
}
