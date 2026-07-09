import { Check, Copy, Mail, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Panel } from '@/components/panel';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { aiErrorMessage, postJson } from '@/lib/fetch-json';
import { updateDraft as updateDraftRoute } from '@/routes/admin/projects';

export function UpdateDraftCard({ uuid }: { uuid: string }) {
    const [loading, setLoading] = useState(false);
    const [draft, setDraft] = useState('');
    const [copied, setCopied] = useState(false);

    const generate = async () => {
        setLoading(true);

        try {
            const data = await postJson<{ draft: string }>(
                updateDraftRoute(uuid).url,
                {},
            );
            setDraft(data.draft);
            setCopied(false);
        } catch (error) {
            toast.error(
                aiErrorMessage(
                    error,
                    'Could not draft an update. Please try again.',
                ),
            );
        } finally {
            setLoading(false);
        }
    };

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(draft);
            setCopied(true);
            toast.success('Update copied to clipboard');
            setTimeout(() => setCopied(false), 2000);
        } catch {
            toast.error('Could not copy — please select and copy manually.');
        }
    };

    return (
        <Panel
            eyebrow="AI assist"
            title="Client update"
            action={<Sparkles className="size-4 text-primary" />}
        >
            <p className="mb-3 text-sm text-muted-foreground">
                Draft a progress-update email from the current milestone state,
                then edit before you send.
            </p>

            <Button
                variant="outline"
                size="sm"
                onClick={generate}
                disabled={loading}
            >
                {loading ? (
                    <Spinner className="mr-1" />
                ) : (
                    <Mail className="size-4" />
                )}
                Draft update
            </Button>

            {draft && (
                <div className="mt-4 space-y-2">
                    <Textarea
                        value={draft}
                        onChange={(e) => setDraft(e.target.value)}
                        rows={10}
                        className="resize-y text-sm leading-relaxed"
                    />
                    <div className="flex justify-end">
                        <Button variant="ghost" size="sm" onClick={copy}>
                            {copied ? (
                                <Check className="size-4 text-success" />
                            ) : (
                                <Copy className="size-4" />
                            )}
                            {copied ? 'Copied' : 'Copy'}
                        </Button>
                    </div>
                </div>
            )}
        </Panel>
    );
}
