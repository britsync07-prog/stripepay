import { router } from '@inertiajs/react';

import { AttachmentList, AttachmentUpload } from '@/components/attachments';
import { Panel } from '@/components/panel';
import { show as downloadConsultationAttachment } from '@/routes/admin/consultations/attachments';
import {
    destroy as destroyDocument,
    show as downloadDocument,
    store as uploadDocuments,
} from '@/routes/admin/projects/documents';
import type { MediaFile, ProjectDetail } from '@/types';

export function DocumentsPanel({
    project,
    documents,
    consultationAttachments,
}: {
    project: ProjectDetail;
    documents: MediaFile[];
    consultationAttachments: MediaFile[];
}) {
    return (
        <Panel
            eyebrow="Documents"
            title="Document center"
            description="Contracts, deliverables and reports shared with the client."
        >
            <div className="space-y-4">
                <AttachmentUpload
                    uploadUrl={uploadDocuments(project.uuid).url}
                />
                <AttachmentList
                    items={documents}
                    downloadUrl={(id) =>
                        downloadDocument([project.uuid, id]).url
                    }
                    onDelete={(id) =>
                        router.delete(destroyDocument([project.uuid, id]).url, {
                            preserveScroll: true,
                        })
                    }
                />

                {consultationAttachments.length > 0 &&
                    project.consultation_uuid && (
                        <div className="space-y-2 border-t pt-4">
                            <p className="text-xs font-medium text-muted-foreground">
                                From the request
                                {project.consultation_reference
                                    ? ` (${project.consultation_reference})`
                                    : ''}{' '}
                                · read-only
                            </p>
                            <AttachmentList
                                items={consultationAttachments}
                                downloadUrl={(id) =>
                                    downloadConsultationAttachment({
                                        consultationRequest:
                                            project.consultation_uuid!,
                                        media: id,
                                    }).url
                                }
                            />
                        </div>
                    )}
            </div>
        </Panel>
    );
}
