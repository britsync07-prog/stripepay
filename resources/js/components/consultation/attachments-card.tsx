import { router } from '@inertiajs/react';

import { AttachmentList, AttachmentUpload } from '@/components/attachments';
import { CountBadge, Section } from '@/components/consultation/section';
import {
    destroy as deleteAttachment,
    show as downloadAttachment,
    store as uploadAttachments,
} from '@/routes/admin/consultations/attachments';
import type { MediaFile } from '@/types';

export function AttachmentsCard({
    uuid,
    attachments,
}: {
    uuid: string;
    attachments: MediaFile[];
}) {
    return (
        <Section
            title="Request attachments"
            badge={<CountBadge count={attachments.length} />}
        >
            <p className="mb-3 text-xs text-muted-foreground">
                Files from the booking &amp; intake. Delivery files (contracts,
                deliverables) live on the project’s Document center.
            </p>
            <AttachmentUpload uploadUrl={uploadAttachments(uuid).url} />
            <div className="mt-3">
                <AttachmentList
                    items={attachments}
                    downloadUrl={(id) =>
                        downloadAttachment({
                            consultationRequest: uuid,
                            media: id,
                        }).url
                    }
                    onDelete={(id) =>
                        router.delete(
                            deleteAttachment({
                                consultationRequest: uuid,
                                media: id,
                            }).url,
                            { preserveScroll: true },
                        )
                    }
                />
            </div>
        </Section>
    );
}
