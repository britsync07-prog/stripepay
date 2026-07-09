import { Check, Copy, FileText, Mail, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Section } from '@/components/consultation/section';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { aiErrorMessage, postJson } from '@/lib/fetch-json';
import { aiDraft } from '@/routes/admin/consultations';

type Kind = 'proposal' | 'email';

export function AiAssistCard({ uuid }: { uuid: string }) {
    const [loading, setLoading] = useState<Kind | null>(null);
    const [draft, setDraft] = useState('');
    const [copied, setCopied] = useState(false);

    const generate = async (kind: Kind) => {
        setLoading(kind);

        try {
            const data = await postJson<{ draft: string }>(aiDraft(uuid).url, {
                kind,
            });
            setDraft(data.draft);
            setCopied(false);
        } catch (error) {
            toast.error(
                aiErrorMessage(
                    error,
                    'Could not generate a draft. Please try again.',
                ),
            );
        } finally {
            setLoading(null);
        }
    };

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(draft);
            setCopied(true);
            toast.success('Draft copied to clipboard');
            setTimeout(() => setCopied(false), 2000);
        } catch {
            toast.error('Could not copy — please select and copy manually.');
        }
    };

    return (
        <Section
            title="AI assist"
            badge={<Sparkles className="size-4 text-primary" />}
        >
            <p className="mb-3 text-sm text-muted-foreground">
                Generate a first draft from this consultation's details, then
                edit before you send.
            </p>
            <div className="flex flex-wrap gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => generate('proposal')}
                    disabled={loading !== null}
                >
                    {loading === 'proposal' ? (
                        <Spinner className="mr-1" />
                    ) : (
                        <FileText className="size-4" />
                    )}
                    Draft proposal
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => generate('email')}
                    disabled={loading !== null}
                >
                    {loading === 'email' ? (
                        <Spinner className="mr-1" />
                    ) : (
                        <Mail className="size-4" />
                    )}
                    Draft follow-up email
                </Button>
            </div>

            {draft && (
                <div className="mt-4 space-y-2">
                    <Textarea
                        value={draft}
                        onChange={(e) => setDraft(e.target.value)}
                        rows={12}
                        className="resize-y font-mono text-xs leading-relaxed"
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
        </Section>
    );
}
