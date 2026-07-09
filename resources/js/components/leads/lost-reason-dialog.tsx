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
import { Textarea } from '@/components/ui/textarea';
import { stage as stageRoute } from '@/routes/admin/leads';

import type { Lead } from './types';

export function LostReasonDialog({
    lead,
    onClose,
}: {
    lead: Lead | null;
    onClose: () => void;
}) {
    const [reason, setReason] = useState('');
    const [seen, setSeen] = useState(lead);

    if (seen !== lead) {
        setSeen(lead);
        setReason('');
    }

    const confirm = () => {
        if (!lead) {
            return;
        }

        router.post(
            stageRoute(lead.uuid).url,
            { stage: 'lost', lost_reason: reason },
            { preserveScroll: true, onSuccess: onClose },
        );
    };

    return (
        <Dialog open={lead !== null} onOpenChange={(o) => !o && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Mark lead as lost</DialogTitle>
                    <DialogDescription>
                        Optionally record why {lead?.name ?? 'this lead'} didn’t
                        convert.
                    </DialogDescription>
                </DialogHeader>
                <Textarea
                    rows={3}
                    placeholder="Reason (optional)"
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                />
                <DialogFooter>
                    <Button variant="ghost" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button variant="destructive" onClick={confirm}>
                        Mark lost
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
