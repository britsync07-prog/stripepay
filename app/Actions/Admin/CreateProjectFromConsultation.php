<?php

namespace App\Actions\Admin;

use App\Enums\ProjectStatus;
use App\Models\ConsultationRequest;
use App\Models\Project;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateProjectFromConsultation
{
    use AsAction;

    public function handle(ConsultationRequest $consultation, ?User $causer = null): Project
    {
        $paid = $consultation->isPaid();
        $existing = Project::where('consultation_request_id', $consultation->id)->first();

        if ($existing) {
            $updates = [];

            if ($consultation->expert_id && $existing->expert_id !== $consultation->expert_id) {
                $updates['expert_id'] = $consultation->expert_id;
            }

            // The client account is created only on payment — link it once it exists.
            if ($consultation->user_id && $existing->user_id !== $consultation->user_id) {
                $updates['user_id'] = $consultation->user_id;
            }

            // A project scoped pre-payment (Planning) goes live once the client pays.
            if ($paid && $existing->status === ProjectStatus::Planning) {
                $updates['status'] = ProjectStatus::Active;
            }

            if ($updates) {
                $existing->update($updates);
            }

            return $existing;
        }

        $project = Project::create([
            'consultation_request_id' => $consultation->id,
            'user_id' => $consultation->user_id,
            'expert_id' => $consultation->expert_id,
            'title' => $consultation->offerTitle(),
            // Pre-payment projects sit in Planning; paid ones go straight to Active.
            'status' => $paid ? ProjectStatus::Active : ProjectStatus::Planning,
        ]);

        activity('project')
            ->causedBy($causer)
            ->performedOn($project)
            ->withProperties([
                'type' => 'created',
                'message' => "Project created from {$consultation->reference}",
            ])
            ->log('Project created from consultation');

        return $project;
    }
}
