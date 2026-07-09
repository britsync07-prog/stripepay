import { router } from '@inertiajs/react';
import {
    Download,
    Eye,
    FileText,
    Image as ImageIcon,
    Trash2,
    Upload,
} from 'lucide-react';
import { useState } from 'react';
import { useDropzone } from 'react-dropzone';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import type { MediaFile } from '@/types';

function withDownload(url: string): string {
    return `${url}${url.includes('?') ? '&' : '?'}download=1`;
}

/** Shorten a file name to ~maxchars, keeping the extension. */
function shortName(name: string, max = 20): string {
    if (name.length <= max) {
        return name;
    }

    const dot = name.lastIndexOf('.');
    const ext = dot > 0 ? name.slice(dot) : '';
    const keep = Math.max(1, max - ext.length - 1);

    return `${name.slice(0, keep)}…${ext}`;
}

function metaLine(file: MediaFile): string {
    return `${file.size}${file.created_at ? ` · ${file.created_at}` : ''}`;
}

export function AttachmentUpload({ uploadUrl }: { uploadUrl: string }) {
    const onDrop = (accepted: File[]) => {
        if (accepted.length === 0) {
            return;
        }

        router.post(
            uploadUrl,
            { files: accepted },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: (errors) => {
                    const first = Object.values(errors)[0];
                    toast.error(
                        typeof first === 'string'
                            ? first
                            : 'Upload failed — file may be too large or an unsupported type.',
                    );
                },
            },
        );
    };

    const onDropRejected = () => {
        toast.error('Some files were rejected — check the type and size.');
    };

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        onDropRejected,
    });

    return (
        <div
            {...getRootProps()}
            className={cn(
                'flex cursor-pointer flex-col items-center gap-1 rounded-lg border border-dashed p-4 text-center text-sm transition-colors',
                isDragActive
                    ? 'border-primary bg-primary/5'
                    : 'text-muted-foreground hover:bg-muted/50',
            )}
        >
            <input {...getInputProps()} />
            <Upload className="size-5" />
            <span>
                {isDragActive
                    ? 'Drop the files here…'
                    : 'Drop files or click to upload'}
            </span>
        </div>
    );
}

export function AttachmentList({
    items,
    downloadUrl,
    onDelete,
}: {
    items: MediaFile[];
    downloadUrl: (id: number) => string;
    onDelete?: (id: number) => void;
}) {
    const [active, setActive] = useState<MediaFile | null>(null);

    if (items.length === 0) {
        return (
            <p className="rounded-lg border border-dashed py-4 text-center text-sm text-muted-foreground">
                No files attached yet.
            </p>
        );
    }

    return (
        <>
            <ul className="grid grid-cols-1 gap-2">
                {items.map((f) => {
                    const Icon = f.is_image ? ImageIcon : FileText;
                    const url = downloadUrl(f.id);

                    return (
                        <li
                            key={f.id}
                            className="flex min-w-0 flex-wrap items-center gap-2 rounded-lg border bg-card p-2 transition-colors hover:border-primary/40"
                        >
                            <button
                                type="button"
                                onClick={() => setActive(f)}
                                className="flex min-w-0 flex-1 basis-40 items-center gap-2 text-left"
                            >
                                <span className="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted text-muted-foreground">
                                    {f.is_image ? (
                                        <img
                                            src={url}
                                            alt=""
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <Icon className="size-5" />
                                    )}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span
                                        className="block truncate text-sm font-medium"
                                        title={f.name}
                                    >
                                        {shortName(f.name)}
                                    </span>
                                    <span className="block truncate text-[11px] text-muted-foreground">
                                        {metaLine(f)}
                                    </span>
                                </span>
                            </button>
                            <div className="ml-auto flex shrink-0 items-center gap-0.5">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-8 text-muted-foreground hover:text-foreground"
                                    aria-label={`Preview ${f.name}`}
                                    onClick={() => setActive(f)}
                                >
                                    <Eye className="size-4" />
                                </Button>
                                <Button
                                    asChild
                                    variant="ghost"
                                    size="icon"
                                    className="size-8 text-muted-foreground hover:text-foreground"
                                    aria-label={`Download ${f.name}`}
                                >
                                    <a
                                        href={withDownload(url)}
                                        download={f.name}
                                    >
                                        <Download className="size-4" />
                                    </a>
                                </Button>
                                {onDelete && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-8 text-muted-foreground hover:text-destructive"
                                        aria-label={`Delete ${f.name}`}
                                        onClick={() => onDelete(f.id)}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </div>
                        </li>
                    );
                })}
            </ul>

            <AttachmentPreview
                file={active}
                url={active ? downloadUrl(active.id) : ''}
                open={active !== null}
                onOpenChange={(open) => !open && setActive(null)}
                onDelete={
                    onDelete
                        ? (id) => {
                              onDelete(id);
                              setActive(null);
                          }
                        : undefined
                }
            />
        </>
    );
}

function AttachmentPreview({
    file,
    url,
    open,
    onOpenChange,
    onDelete,
}: {
    file: MediaFile | null;
    url: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onDelete?: (id: number) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] max-w-[95vw] flex-col gap-4 sm:max-w-3xl">
                <DialogHeader className="min-w-0 text-left">
                    <DialogTitle className="truncate pr-6" title={file?.name}>
                        {file?.name ?? 'Attachment'}
                    </DialogTitle>
                    <DialogDescription>
                        {file ? metaLine(file) : ''}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex min-h-0 flex-1 items-center justify-center overflow-auto rounded-lg border bg-muted/30">
                    {file?.is_image ? (
                        <img
                            src={url}
                            alt={file.name}
                            className="max-h-[70vh] max-w-full object-contain"
                        />
                    ) : file?.is_pdf ? (
                        <iframe
                            src={url}
                            title={file.name}
                            className="h-[70vh] w-full rounded-md"
                        />
                    ) : (
                        <div className="flex flex-col items-center gap-2 p-10 text-center text-sm text-muted-foreground">
                            <FileText className="size-10" />
                            <span>No inline preview for this file type.</span>
                            {file?.mime_type && (
                                <span className="text-xs">
                                    {file.mime_type}
                                </span>
                            )}
                        </div>
                    )}
                </div>

                <DialogFooter className="flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    {onDelete && file ? (
                        <Button
                            type="button"
                            variant="destructive"
                            className="w-full sm:w-auto"
                            onClick={() => onDelete(file.id)}
                        >
                            <Trash2 className="size-4" /> Delete
                        </Button>
                    ) : (
                        <span className="hidden sm:block" />
                    )}
                    {file && (
                        <Button asChild className="w-full sm:w-auto">
                            <a href={withDownload(url)} download={file.name}>
                                <Download className="size-4" /> Download
                            </a>
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
