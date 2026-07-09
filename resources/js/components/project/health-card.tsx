import { Activity, RefreshCw, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Panel } from '@/components/panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { aiErrorMessage, postJson } from '@/lib/fetch-json';
import { health as healthRoute } from '@/routes/admin/projects';
import type { ProjectHealth } from '@/types';

export function ProjectHealthCard({
    uuid,
    initial,
}: {
    uuid: string;
    initial: ProjectHealth;
}) {
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState<ProjectHealth>(initial);

    const run = async () => {
        setLoading(true);

        try {
            const data = await postJson<ProjectHealth>(
                healthRoute(uuid).url,
                {},
            );
            setResult(data);
        } catch (error) {
            toast.error(
                aiErrorMessage(
                    error,
                    'Could not assess this project. Please try again.',
                ),
            );
        } finally {
            setLoading(false);
        }
    };

    const assessed = result.health !== null;

    return (
        <Panel
            eyebrow="AI"
            title="Delivery health"
            action={<Activity className="size-4 text-primary" />}
        >
            {!assessed && (
                <p className="mb-3 text-sm text-muted-foreground">
                    Check whether this project is on track, at risk or stalled —
                    with the reasons behind it.
                </p>
            )}

            {assessed && result.health_tone && (
                <div className="mb-4 space-y-3">
                    <Badge variant={result.health_tone} className="gap-1">
                        <Sparkles className="size-3" />
                        {result.health_label}
                    </Badge>

                    {result.summary && (
                        <p className="text-sm text-muted-foreground">
                            {result.summary}
                        </p>
                    )}

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

                    {result.checked_at && (
                        <p className="text-xs text-muted-foreground">
                            Checked {result.checked_at}
                        </p>
                    )}
                </div>
            )}

            <Button
                variant={assessed ? 'ghost' : 'outline'}
                size="sm"
                onClick={run}
                disabled={loading}
            >
                {loading ? (
                    <Spinner className="mr-1" />
                ) : assessed ? (
                    <RefreshCw className="size-4" />
                ) : (
                    <Activity className="size-4" />
                )}
                {assessed ? 'Re-assess' : 'Assess health'}
            </Button>
        </Panel>
    );
}
