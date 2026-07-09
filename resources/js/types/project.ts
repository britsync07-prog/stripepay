export type StatusOption = { value: string; label: string };

export type BadgeTone = 'success' | 'secondary' | 'outline' | 'destructive';

export type HealthTone = 'success' | 'warning' | 'destructive';

/** AI delivery-health snapshot (see ProjectHealthData). */
export type ProjectHealth = {
    health: string | null;
    health_label: string | null;
    health_tone: HealthTone | null;
    summary: string | null;
    flags: string[];
    checked_at: string | null;
};

/** Slim projection used by the projects index tables (see ProjectListData). */
export type ProjectListItem = {
    uuid: string;
    reference: string;
    title: string;
    client: string | null;
    expert: string | null;
    status: string;
    status_label: string;
    status_tone: BadgeTone;
    completion_percentage: number;
    milestones_count: number;
    health: string | null;
    health_label: string | null;
    health_tone: HealthTone | null;
    created_at: string | null;
};

/** Portal-facing projection — omits the internal AI health verdict. */
export type ClientProjectListItem = Omit<
    ProjectListItem,
    'health' | 'health_label' | 'health_tone'
>;

export type Milestone = {
    id: number;
    title: string;
    description: string | null;
    status: string;
    status_label: string;
    sort_order: number;
    due_date: string | null;
    due_on: string | null;
    completed_at: string | null;
    status_options: StatusOption[];
};

/** Full project detail (see ProjectData). */
export type ProjectDetail = {
    uuid: string;
    reference: string;
    title: string;
    client: string | null;
    expert: string | null;
    consultation_uuid: string | null;
    consultation_reference: string | null;
    status: string;
    status_label: string;
    status_tone: BadgeTone;
    completion_percentage: number;
    created_at: string | null;
    milestones: Milestone[];
};
