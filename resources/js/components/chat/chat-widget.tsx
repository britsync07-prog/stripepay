import { MessageSquare, Send, Sparkles, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { aiErrorMessage, postJson } from '@/lib/fetch-json';
import { cn } from '@/lib/utils';

type Message = { role: 'user' | 'assistant'; content: string };

/**
 * A floating, multi-turn chat. Conversation lives client-side (ephemeral) and
 * the full history is posted to `endpoint` each turn — the backend re-injects
 * its grounded context, so it never trusts what the client sends as context.
 */
export function ChatWidget({
    endpoint,
    title,
    greeting,
    suggestions = [],
    triggerClass,
    panelClass,
}: {
    endpoint: string;
    title: string;
    greeting: string;
    suggestions?: string[];
    triggerClass?: string;
    panelClass?: string;
}) {
    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const scrollRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        scrollRef.current?.scrollTo({
            top: scrollRef.current.scrollHeight,
            behavior: 'smooth',
        });
    }, [messages, loading]);

    const send = async (raw: string) => {
        const text = raw.trim();

        if (!text || loading) {
            return;
        }

        const next: Message[] = [...messages, { role: 'user', content: text }];
        setMessages(next);
        setInput('');
        setLoading(true);

        try {
            const data = await postJson<{ answer: string }>(endpoint, {
                messages: next,
            });
            setMessages((m) => [
                ...m,
                { role: 'assistant', content: data.answer },
            ]);
        } catch (error) {
            toast.error(
                aiErrorMessage(
                    error,
                    'Could not get a reply. Please try again.',
                ),
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                aria-label={open ? 'Close assistant' : 'Open assistant'}
                aria-expanded={open}
                className={cn(
                    'fixed right-5 bottom-5 z-40 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-elevated transition-transform hover:scale-105 focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none',
                    triggerClass,
                )}
            >
                {open ? (
                    <X className="size-6" />
                ) : (
                    <MessageSquare className="size-6" />
                )}
            </button>

            {open && (
                <div
                    role="dialog"
                    aria-label={title}
                    onKeyDown={(e) => e.key === 'Escape' && setOpen(false)}
                    className={cn(
                        'fixed right-5 bottom-24 z-40 flex h-[32rem] max-h-[calc(100vh-8rem)] w-[calc(100vw-2.5rem)] max-w-sm flex-col overflow-hidden rounded-2xl border bg-card shadow-elevated edge-highlight',
                        panelClass,
                    )}
                >
                    <header className="flex items-center gap-2 border-b bg-gradient-to-b from-muted/40 to-transparent px-4 py-3">
                        <Sparkles className="size-4 text-primary" />
                        <h2 className="flex-1 font-serif text-base font-semibold">
                            {title}
                        </h2>
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            aria-label="Close"
                            className="flex size-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            <X className="size-4" />
                        </button>
                    </header>

                    <div
                        ref={scrollRef}
                        className="flex-1 space-y-3 overflow-y-auto p-4"
                    >
                        {messages.length === 0 && (
                            <>
                                <p className="text-sm text-muted-foreground">
                                    {greeting}
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {suggestions.map((s) => (
                                        <button
                                            key={s}
                                            type="button"
                                            onClick={() => send(s)}
                                            className="rounded-full border px-3 py-1 text-left text-xs text-muted-foreground transition-colors hover:border-primary/40 hover:text-foreground"
                                        >
                                            {s}
                                        </button>
                                    ))}
                                </div>
                            </>
                        )}

                        {messages.map((m, i) => (
                            <div
                                key={i}
                                className={cn(
                                    'flex',
                                    m.role === 'user' && 'justify-end',
                                )}
                            >
                                <div
                                    className={cn(
                                        'max-w-[85%] rounded-2xl px-3 py-2 text-sm leading-relaxed break-words whitespace-pre-line',
                                        m.role === 'user'
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-muted',
                                    )}
                                >
                                    {m.content}
                                </div>
                            </div>
                        ))}

                        {loading && (
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Spinner className="size-3" /> Thinking…
                            </div>
                        )}
                    </div>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            send(input);
                        }}
                        className="flex items-center gap-2 border-t p-3"
                    >
                        <Input
                            autoFocus
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            placeholder="Type your question…"
                            maxLength={1000}
                        />
                        <Button
                            type="submit"
                            size="icon"
                            disabled={loading || input.trim() === ''}
                            aria-label="Send"
                        >
                            <Send className="size-4" />
                        </Button>
                    </form>
                </div>
            )}
        </>
    );
}
