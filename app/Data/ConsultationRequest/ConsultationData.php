<?php

namespace App\Data\ConsultationRequest;

use App\Models\ConsultationRequest;
use Spatie\LaravelData\Data;

class ConsultationData extends Data
{
    public function __construct(
        public string $uuid,
        public string $reference,
        public string $plan,
        public ?string $assigned_expert,
        public ?string $description,
        public array $features,
        public string $status,
        public string $status_label,
        public string $work_status,
        public string $work_status_label,
        public bool $is_lead,
        public ?string $lead_stage,
        public ?string $lead_stage_label,
        public ?string $lead_stage_tone,
        public string $amount,
        public bool $is_paid,
        public bool $is_custom,
        public bool $unpriced,
        public ?string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $country,
        public ?string $created_at,
        public ?string $paid_at,
        public ?string $date,
        public ?string $project_uuid,
        public ?string $invoice_uuid,
    ) {}

    /** The portal-facing shape — internal CRM pipeline fields are never sent to clients. */
    public static function fromModelForClient(ConsultationRequest $c): self
    {
        return self::fromModel($c)->except('is_lead', 'lead_stage', 'lead_stage_label', 'lead_stage_tone');
    }

    public static function fromModel(ConsultationRequest $c): self
    {
        return new self(
            uuid: $c->uuid,
            reference: $c->reference ?? strtoupper(substr($c->uuid, 0, 8)),
            plan: $c->offerTitle(),
            assigned_expert: $c->expert?->name,
            description: $c->plan?->description ?? $c->message,
            features: $c->plan?->features ?? [],
            status: $c->status->value,
            status_label: $c->status->label(),
            work_status: $c->work_status->value,
            work_status_label: $c->work_status->label(),
            is_lead: $c->isLead(),
            lead_stage: $c->lead_stage?->value,
            lead_stage_label: $c->lead_stage?->label(),
            lead_stage_tone: $c->lead_stage?->badge(),
            amount: $c->formattedAmount(),
            is_paid: $c->isPaid(),
            is_custom: $c->isCustom(),
            unpriced: $c->isCustom() && (int) $c->amount <= 0,
            name: $c->name,
            email: $c->email,
            phone: $c->phone,
            country: $c->countryName(),
            created_at: $c->created_at?->toFormattedDayDateString(),
            paid_at: $c->paid_at?->toFormattedDayDateString(),
            date: ($c->paid_at ?? $c->created_at)?->toFormattedDayDateString(),
            project_uuid: $c->relationLoaded('project') ? $c->project?->uuid : null,
            invoice_uuid: $c->relationLoaded('invoice') ? $c->invoice?->uuid : null,
        );
    }
}
