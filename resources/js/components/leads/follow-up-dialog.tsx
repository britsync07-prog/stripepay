import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { followUp } from '@/routes/admin/leads';

import type { Lead } from './types';

export function FollowUpDialog({
    lead,
    onClose,
}: {
    lead: Lead | null;
    onClose: () => void;
}) {
    const [date, setDate] = useState(lead?.next_follow_up_at ?? '');
    const [seen, setSeen] = useState(lead);

    if (seen !== lead) {
        setSeen(lead);
        setDate(lead?.next_follow_up_at ?? '');
    }

    const save = (value: string) => {
        if (!lead) {
            return;
        }

        router.post(
            followUp(lead.uuid).url,
            { next_follow_up_at: value || null },
            { preserveScroll: true, onSuccess: onClose },
        );
    };

    return (
        <Dialog open={lead !== null} onOpenChange={(o) => !o && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Schedule follow-up</DialogTitle>
                    <DialogDescription>
                        Set when to next reach out to{' '}
                        {lead?.name ?? 'this lead'}. Overdue leads are flagged
                        on the board.
                    </DialogDescription>
                </DialogHeader>
                <Input
                    type="date"
                    value={date}
                    onChange={(e) => setDate(e.target.value)}
                />
                <DialogFooter>
                    {lead?.next_follow_up_at && (
                        <Button
                            variant="ghost"
                            className="mr-auto"
                            onClick={() => save('')}
                        >
                            Clear
                        </Button>
                    )}
                    <Button variant="ghost" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button onClick={() => save(date)}>Save</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
