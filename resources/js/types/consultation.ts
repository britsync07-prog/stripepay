export type Consultation = {
    uuid: string;
    reference: string;
    plan: string;
    assigned_expert: string | null;
    description: string | null;
    features: string[];
    status: string;
    status_label: string;
    work_status: string;
    work_status_label: string;
    is_lead: boolean;
    lead_stage: string | null;
    lead_stage_label: string | null;
    lead_stage_tone:
        | 'success'
        | 'secondary'
        | 'outline'
        | 'destructive'
        | 'warning'
        | null;
    amount: string;
    is_paid: boolean;
    is_custom: boolean;
    unpriced: boolean;
    name: string | null;
    email: string | null;
    phone: string | null;
    country: string | null;
    created_at: string | null;
    paid_at: string | null;
    date: string | null;
    project_uuid: string | null;
    invoice_uuid: string | null;
};

/**
 * The portal-facing consultation shape — the backend strips internal CRM
 * pipeline fields before sending to clients (ConsultationData::fromModelForClient).
 */
export type ClientConsultation = Omit<
    Consultation,
    'is_lead' | 'lead_stage' | 'lead_stage_label' | 'lead_stage_tone'
>;

export type MediaFile = {
    id: number;
    name: string;
    size: string;
    is_image: boolean;
    is_pdf: boolean;
    mime_type: string | null;
    created_at: string | null;
};
