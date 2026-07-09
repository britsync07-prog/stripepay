import { Gauge, RefreshCw, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Section } from '@/components/consultation/section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { aiErrorMessage, postJson } from '@/lib/fetch-json';
import { cn } from '@/lib/utils';
import { score as scoreRoute } from '@/routes/admin/leads';

type BandTone = 'success' | 'warning' | 'outline';

export type LeadScore = {
    score: number | null;
    band: 'hot' | 'warm' | 'cold' | null;
    band_label: string | null;
    band_tone: BandTone | null;
    summary: string | null;
    flags: string[];
    scored_at: string | null;
};

// Card-only presentation: the dial ring colour follows the backend tone.
const RING: Record<BandTone, string> = {
    success: 'text-success',
    warning: 'text-warning',
    outline: 'text-muted-foreground',
};

export function LeadScoreCard({
    uuid,
    initial,
}: {
    uuid: string;
    initial: LeadScore;
}) {
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState<LeadScore>(initial);

    const run = async () => {
        setLoading(true);

        try {
            const data = await postJson<LeadScore>(scoreRoute(uuid).url, {});
            setResult(data);
        } catch (error) {
            toast.error(
                aiErrorMessage(
                    error,
                    'Could not score this lead. Please try again.',
                ),
            );
        } finally {
            setLoading(false);
        }
    };

    const scored = result.score !== null;
    const tone = result.band_tone;

    return (
        <Section
            title="Lead score"
            badge={<Gauge className="size-4 text-primary" />}
        >
            {!scored && (
                <p className="mb-3 text-sm text-muted-foreground">
                    Estimate how likely this lead is to convert, with reasoning
                    and risk flags to help you prioritise.
                </p>
            )}

            {scored && tone && (
                <div className="mb-4 space-y-3">
                    <div className="flex items-center gap-4">
                        <div
                            className={cn(
                                'flex size-16 shrink-0 flex-col items-center justify-center rounded-full border-2',
                                RING[tone],
                            )}
                        >
                            <span className="font-mono text-xl font-semibold tabular-nums">
                                {result.score}
                            </span>
                            <span className="text-[9px] tracking-wide uppercase opacity-70">
                                /100
                            </span>
                        </div>
                        <div className="min-w-0">
                            <Badge variant={tone} className="gap-1">
                                <Sparkles className="size-3" />
                                {result.band_label} lead
                            </Badge>
                            {result.summary && (
                                <p className="mt-1.5 text-sm text-muted-foreground">
                                    {result.summary}
                                </p>
                            )}
                        </div>
                    </div>

                    {result.flags.length > 0 && (
                        <div className="flex flex-wrap gap-1.5">
                            {result.flags.map((flag) => (
                                <Badge
                                    key={flag}
                                    variant="secondary"
                                    className="text-[10px]"
                                >
                                    {flag}
                                </Badge>
                            ))}
                        </div>
                    )}

                    {result.scored_at && (
                        <p className="text-xs text-muted-foreground">
                            Scored {result.scored_at}
                        </p>
                    )}
                </div>
            )}

            <Button
                variant={scored ? 'ghost' : 'outline'}
                size="sm"
                onClick={run}
                disabled={loading}
            >
                {loading ? (
                    <Spinner className="mr-1" />
                ) : scored ? (
                    <RefreshCw className="size-4" />
                ) : (
                    <Gauge className="size-4" />
                )}
                {scored ? 'Re-score' : 'Score this lead'}
            </Button>
        </Section>
    );
}
