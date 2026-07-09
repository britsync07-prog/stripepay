import type { LucideIcon } from 'lucide-react';
import { X } from 'lucide-react';
import { useState } from 'react';

import { cn } from '@/lib/utils';

export type FloatingDockItem = {
    key: string;
    label: string;
    icon: LucideIcon;
    content: React.ReactNode;
};

/**
 * A vertical rail of floating circular buttons pinned to the bottom-left. Each
 * opens its card in a floating panel beside the rail; only one is open at a
 * time. Keeps the heavy AI cards off the page flow until the admin wants them.
 */
export function FloatingAiDock({ items }: { items: FloatingDockItem[] }) {
    const [openKey, setOpenKey] = useState<string | null>(null);
    const active = items.find((i) => i.key === openKey) ?? null;

    if (items.length === 0) {
        return null;
    }

    return (
        <>
            <div className="fixed right-5 bottom-5 z-40 flex flex-col-reverse gap-3">
                {items.map(({ key, label, icon: Icon }) => {
                    const isOpen = openKey === key;

                    return (
                        <button
                            key={key}
                            type="button"
                            onClick={() =>
                                setOpenKey((k) => (k === key ? null : key))
                            }
                            aria-label={label}
                            aria-expanded={isOpen}
                            title={label}
                            className={cn(
                                'flex size-12 items-center justify-center rounded-full shadow-elevated ring-1 ring-black/5 transition-transform hover:scale-105 focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none',
                                isOpen
                                    ? 'bg-foreground text-background'
                                    : 'bg-primary text-primary-foreground',
                            )}
                        >
                            <Icon className="size-5" />
                        </button>
                    );
                })}
            </div>

            {active && (
                <div
                    role="dialog"
                    aria-label={active.label}
                    onKeyDown={(e) => e.key === 'Escape' && setOpenKey(null)}
                    className="fixed right-20 bottom-5 z-40 w-[calc(100vw-6rem)] max-w-md"
                >
                    <button
                        type="button"
                        onClick={() => setOpenKey(null)}
                        aria-label="Close"
                        className="absolute -top-2 -right-2 z-10 flex size-7 items-center justify-center rounded-full border bg-card text-muted-foreground shadow-card transition-colors hover:bg-muted hover:text-foreground"
                    >
                        <X className="size-4" />
                    </button>
                    <div className="max-h-[calc(100vh-2.5rem)] overflow-y-auto rounded-2xl">
                        {active.content}
                    </div>
                </div>
            )}
        </>
    );
}
