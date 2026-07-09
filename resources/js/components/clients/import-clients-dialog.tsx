import { router } from '@inertiajs/react';
import { Upload } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { importMethod } from '@/routes/admin/clients';

export function ImportClientsDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [file, setFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!file) {
            return;
        }

        setSubmitting(true);
        router.post(
            importMethod().url,
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setFile(null);
                    onOpenChange(false);
                },
                onFinish: () => setSubmitting(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Import clients</DialogTitle>
                    <DialogDescription>
                        Upload a CSV or Excel file. The first row must be a
                        header with columns — <strong>name</strong> and{' '}
                        <strong>email</strong> are required;{' '}
                        <strong>company</strong>, <strong>country</strong>{' '}
                        (2‑letter code) and <strong>phone</strong> are optional.
                        Rows are matched by email, so re‑importing updates
                        instead of duplicating.
                    </DialogDescription>
                </DialogHeader>

                <form
                    id="import-clients"
                    onSubmit={submit}
                    className="grid gap-3"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="import-file">File (.csv, .xlsx)</Label>
                        <Input
                            id="import-file"
                            type="file"
                            accept=".csv,.txt,.xlsx,.xls"
                            onChange={(e) =>
                                setFile(e.target.files?.[0] ?? null)
                            }
                        />
                    </div>
                </form>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        form="import-clients"
                        disabled={!file || submitting}
                    >
                        {submitting ? (
                            <Spinner className="size-4" />
                        ) : (
                            <Upload className="size-4" />
                        )}
                        Import
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
