import { Check, Copy, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { aiErrorMessage, postJson } from '@/lib/fetch-json';
import { followUpDraft as followUpDraftRoute } from '@/routes/admin/leads';

type Draft = { subject: string; body: string };

export function FollowUpDraftButton({
    uuid,
    leadName,
}: {
    uuid: string;
    leadName: string;
}) {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const [subject, setSubject] = useState('');
    const [body, setBody] = useState('');
    const [copied, setCopied] = useState(false);

    const generate = async () => {
        setLoading(true);

        try {
            const data = await postJson<Draft>(
                followUpDraftRoute(uuid).url,
                {},
            );
            setSubject(data.subject);
            setBody(data.body);
            setCopied(false);
            setOpen(true);
        } catch (error) {
            toast.error(
                aiErrorMessage(
                    error,
                    'Could not draft the follow-up. Please try again.',
                ),
            );
        } finally {
            setLoading(false);
        }
    };

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(
                `Subject: ${subject}\n\n${body}`,
            );
            setCopied(true);
            toast.success('Draft copied to clipboard');
            setTimeout(() => setCopied(false), 2000);
        } catch {
            toast.error('Could not copy — please select and copy manually.');
        }
    };

    return (
        <>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={generate}
                disabled={loading}
                title="Draft the chase email with AI"
                aria-label={`Draft a follow-up email for ${leadName}`}
            >
                {loading ? (
                    <Spinner className="size-4" />
                ) : (
                    <Sparkles className="size-4" />
                )}
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Follow-up draft — {leadName}</DialogTitle>
                        <DialogDescription>
                            Drafted from this lead's history. Edit, copy, and
                            send from your mailbox.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-3">
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Subject</Label>
                            <Input
                                value={subject}
                                onChange={(e) => setSubject(e.target.value)}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label className="text-xs">Message</Label>
                            <Textarea
                                value={body}
                                onChange={(e) => setBody(e.target.value)}
                                rows={10}
                                className="resize-y text-sm leading-relaxed"
                            />
                        </div>
                        <div className="flex justify-end">
                            <Button variant="outline" size="sm" onClick={copy}>
                                {copied ? (
                                    <Check className="size-4 text-success" />
                                ) : (
                                    <Copy className="size-4" />
                                )}
                                {copied ? 'Copied' : 'Copy'}
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
