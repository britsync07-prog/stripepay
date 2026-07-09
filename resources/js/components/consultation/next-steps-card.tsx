import { Compass, RefreshCw, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Section } from '@/components/consultation/section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { aiErrorMessage, postJson } from '@/lib/fetch-json';
import { nextSteps } from '@/routes/admin/consultations';

type Priority = 'now' | 'soon' | 'later';

type Step = {
    title: string;
    detail: string;
    priority: Priority;
};

type Suggestion = {
    stage: string;
    summary: string;
    steps: Step[];
};

const PRIORITY: Record<
    Priority,
    { label: string; variant: 'destructive' | 'secondary' | 'outline' }
> = {
    now: { label: 'Now', variant: 'destructive' },
    soon: { label: 'Soon', variant: 'secondary' },
    later: { label: 'Later', variant: 'outline' },
};

export function NextStepsCard({ uuid }: { uuid: string }) {
    const [loading, setLoading] = useState(false);
    const [suggestion, setSuggestion] = useState<Suggestion | null>(null);

    const suggest = async () => {
        setLoading(true);

        try {
            const data = await postJson<Suggestion>(nextSteps(uuid).url, {});
            setSuggestion(data);
        } catch (error) {
            toast.error(
                aiErrorMessage(
                    error,
                    'Could not suggest next steps. Please try again.',
                ),
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <Section
            title="Next steps"
            badge={<Compass className="size-4 text-primary" />}
        >
            {!suggestion && (
                <p className="mb-3 text-sm text-muted-foreground">
                    New here? Get AI guidance on exactly what to do next for
                    this consultation at its current stage.
                </p>
            )}

            {suggestion && (
                <div className="mb-4 space-y-3">
                    <div className="flex items-center gap-2">
                        <Badge variant="outline" className="gap-1">
                            <Sparkles className="size-3 text-primary" />
                            {suggestion.stage}
                        </Badge>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {suggestion.summary}
                    </p>
                    <ol className="space-y-3">
                        {(suggestion.steps ?? []).map((step, index) => {
                            const priority =
                                PRIORITY[step.priority] ?? PRIORITY.later;

                            return (
                                <li
                                    key={index}
                                    className="relative rounded-xl border bg-muted/30 p-3 pl-10"
                                >
                                    <span className="absolute top-3 left-3 flex size-5 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary tabular-nums">
                                        {index + 1}
                                    </span>
                                    <div className="flex items-start justify-between gap-2">
                                        <p className="text-sm font-medium">
                                            {step.title}
                                        </p>
                                        <Badge
                                            variant={priority.variant}
                                            className="shrink-0 text-[10px]"
                                        >
                                            {priority.label}
                                        </Badge>
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {step.detail}
                                    </p>
                                </li>
                            );
                        })}
                    </ol>
                </div>
            )}

            <Button
                variant={suggestion ? 'ghost' : 'outline'}
                size="sm"
                onClick={suggest}
                disabled={loading}
            >
                {loading ? (
                    <Spinner className="mr-1" />
                ) : suggestion ? (
                    <RefreshCw className="size-4" />
                ) : (
                    <Compass className="size-4" />
                )}
                {suggestion ? 'Refresh guidance' : 'Suggest next steps'}
            </Button>
        </Section>
    );
}
