import { Deferred, useForm } from '@inertiajs/react';
import { MessageSquare, Send, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { CountBadge, Section } from '@/components/consultation/section';
import {
    TimelineList,
    TimelineSkeleton,
} from '@/components/consultation/timeline';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { CHANNEL_ICONS, TRANSITION } from '@/constants/consultation';
import { useAiEnabled } from '@/hooks/use-ai-enabled';
import { aiErrorMessage, postJson } from '@/lib/fetch-json';
import { summarizeNotes as summarizeNotesRoute } from '@/routes/admin';
import { notes as addNoteRoute } from '@/routes/admin/consultations';
import type { SelectOption, TimelineEvent } from '@/types';

type NotesSummary = {
    summary: string;
    action_items: string[];
    follow_up_in_days: number | null;
};

export function NotesCard({
    uuid,
    channelOptions,
    timeline,
}: {
    uuid: string;
    channelOptions: SelectOption[];
    timeline?: TimelineEvent[];
}) {
    const form = useForm<{
        message: string;
        channel: string;
        duration: string;
    }>({ message: '', channel: 'note', duration: '' });
    const isCall =
        form.data.channel === 'phone' || form.data.channel === 'video';
    const contacts = timeline?.filter((e) => !TRANSITION[e.type]) ?? [];
    const aiEnabled = useAiEnabled();
    const [summarizing, setSummarizing] = useState(false);

    const summarize = async () => {
        setSummarizing(true);

        try {
            const data = await postJson<NotesSummary>(
                summarizeNotesRoute().url,
                { notes: form.data.message },
            );
            const combined =
                data.summary +
                (data.action_items.length
                    ? ' — Actions: ' + data.action_items.join('; ')
                    : '');
            form.setData('message', combined);

            if (data.follow_up_in_days) {
                toast.info(
                    `Suggested follow-up: in ${data.follow_up_in_days} day${
                        data.follow_up_in_days === 1 ? '' : 's'
                    }.`,
                );
            }
        } catch (error) {
            toast.error(
                aiErrorMessage(error, 'Could not summarize. Please try again.'),
            );
        } finally {
            setSummarizing(false);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            channel: data.channel,
            message: data.message,
            duration:
                (data.channel === 'phone' || data.channel === 'video') &&
                data.duration
                    ? Number(data.duration)
                    : null,
        }));
        form.post(addNoteRoute(uuid).url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <Section
            title="Notes & contacts"
            badge={<CountBadge count={contacts.length} />}
        >
            <form onSubmit={submit} className="mb-4 grid gap-2">
                <ToggleGroup
                    type="single"
                    variant="outline"
                    size="sm"
                    value={form.data.channel}
                    onValueChange={(v) => v && form.setData('channel', v)}
                    className="flex-wrap justify-start"
                >
                    {channelOptions.map((c) => {
                        const Icon = CHANNEL_ICONS[c.value] ?? MessageSquare;

                        return (
                            <ToggleGroupItem
                                key={c.value}
                                value={c.value}
                                className="gap-1.5 px-2.5 text-xs"
                            >
                                <Icon className="size-3.5" /> {c.label}
                            </ToggleGroupItem>
                        );
                    })}
                </ToggleGroup>
                <div className="flex items-end gap-2">
                    <Input
                        value={form.data.message}
                        onChange={(e) =>
                            form.setData('message', e.target.value)
                        }
                        placeholder={
                            form.data.channel === 'note'
                                ? 'Add a note for the team…'
                                : 'Summary of the contact…'
                        }
                        className="flex-1"
                    />
                    {isCall && (
                        <Input
                            type="number"
                            min={1}
                            max={1440}
                            value={form.data.duration}
                            onChange={(e) =>
                                form.setData('duration', e.target.value)
                            }
                            placeholder="min"
                            aria-label="Duration in minutes"
                            className="w-16"
                        />
                    )}
                    {aiEnabled && (
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={summarize}
                            disabled={summarizing || !form.data.message}
                            title="Summarize these notes with AI"
                            aria-label="Summarize with AI"
                        >
                            {summarizing ? (
                                <Spinner className="size-4" />
                            ) : (
                                <Sparkles className="size-4" />
                            )}
                        </Button>
                    )}
                    <Button
                        type="submit"
                        disabled={form.processing || !form.data.message}
                    >
                        {form.processing ? (
                            <Spinner className="mr-1" />
                        ) : (
                            <Send className="size-4" />
                        )}
                        Log
                    </Button>
                </div>
            </form>

            <Deferred data="timeline" fallback={<TimelineSkeleton />}>
                <TimelineList
                    items={contacts}
                    empty="No notes or contacts logged yet."
                />
            </Deferred>
        </Section>
    );
}
