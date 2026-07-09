<?php

namespace App\Data\Activity;

use App\Models\ConsultationRequest;
use App\Models\Invoice;
use App\Models\Payout;
use App\Models\Project;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\Data;

/** A single audit-trail row for the admin Activity page. */
class ActivityLogData extends Data
{
    public function __construct(
        public int $id,
        public string $log_name,
        public string $description,
        public ?string $type,
        public ?string $message,
        public ?string $from,
        public ?string $to,
        public string $causer,
        public ?string $subject,
        public ?string $subject_url,
        public string $at,
        public string $at_exact,
    ) {}

    public static function fromModel(Activity $a): self
    {
        [$subject, $url] = self::resolveSubject($a);

        return new self(
            id: $a->id,
            log_name: $a->log_name ?? 'system',
            description: $a->description,
            type: $a->properties['type'] ?? null,
            message: $a->properties['message'] ?? null,
            from: $a->properties['from_label'] ?? null,
            to: $a->properties['to_label'] ?? null,
            causer: $a->causer?->name ?? 'System',
            subject: $subject,
            subject_url: $url,
            at: $a->created_at?->diffForHumans() ?? '',
            at_exact: $a->created_at?->toDayDateTimeString() ?? '',
        );
    }

    /**
     * @return array{0: ?string, 1: ?string} The subject's label and admin URL.
     */
    private static function resolveSubject(Activity $a): array
    {
        $subject = $a->subject;

        return match (true) {
            $subject instanceof ConsultationRequest => [$subject->reference, route('admin.consultation', $subject, false)],
            $subject instanceof Project => [$subject->reference, route('admin.projects.show', $subject, false)],
            $subject instanceof Invoice => [$subject->reference, route('admin.invoices.show', $subject, false)],
            $subject instanceof Payout => ['Payout #'.$subject->id, route('admin.payouts', [], false)],
            default => [null, null],
        };
    }
}
