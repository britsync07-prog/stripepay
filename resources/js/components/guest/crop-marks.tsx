import { cn } from '@/lib/utils';

const CORNERS = [
    'top-0 left-0 border-t border-l',
    'top-0 right-0 border-t border-r',
    'bottom-0 left-0 border-b border-l',
    'bottom-0 right-0 border-b border-r',
] as const;

export function CropMarks({ className }: { className?: string }) {
    return (
        <>
            {CORNERS.map((pos) => (
                <span
                    key={pos}
                    aria-hidden
                    className={cn(
                        'pointer-events-none absolute z-10 size-3.5 border-primary/70',
                        pos,
                        className,
                    )}
                />
            ))}
        </>
    );
}
