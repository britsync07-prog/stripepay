import { useForm } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';

import { Section } from '@/components/consultation/section';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { status as updateStatusRoute } from '@/routes/admin/consultations';
import type { SelectOption } from '@/types';

export function WorkStatusCard({
    uuid,
    workStatus,
    statusOptions,
}: {
    uuid: string;
    workStatus: string;
    statusOptions: SelectOption[];
}) {
    const form = useForm({ work_status: workStatus, message: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.patch(updateStatusRoute(uuid).url, {
            preserveScroll: true,
            onSuccess: () => form.setData('message', ''),
        });
    };

    return (
        <Section title="Work status" accent>
            <form onSubmit={submit} className="grid gap-3">
                <div className="grid gap-1.5">
                    <Label className="text-xs">Status</Label>
                    <Select
                        value={form.data.work_status}
                        onValueChange={(v) => form.setData('work_status', v)}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {statusOptions.map((o) => (
                                <SelectItem key={o.value} value={o.value}>
                                    {o.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <Input
                    value={form.data.message}
                    onChange={(e) => form.setData('message', e.target.value)}
                    placeholder="Coordination note (optional)…"
                />
                <Button
                    type="submit"
                    disabled={form.processing}
                    className="justify-self-start"
                >
                    {form.processing ? (
                        <Spinner className="mr-2" />
                    ) : (
                        <RefreshCw className="size-4" />
                    )}
                    Update status
                </Button>
            </form>
        </Section>
    );
}
