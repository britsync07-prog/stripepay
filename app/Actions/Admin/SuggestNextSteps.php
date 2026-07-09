<?php

namespace App\Actions\Admin;

use App\Models\ConsultationRequest;
use App\Services\AI\Ai;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

class SuggestNextSteps
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest): JsonResponse
    {
        if (! Ai::configured()) {
            return response()->json(['message' => 'AI assistance is not configured.'], 503);
        }

        $suggestion = Ai::generateJson(
            $this->prompt($consultationRequest),
            $this->system(),
            $this->schema(),
        );

        return response()->json($suggestion);
    }

    private function system(): string
    {
        $firm = config('app.name');

        return implode(' ', [
            "You are the operations lead at {$firm} coaching Fadwa, a brand-new admin, on how to handle a consultation.",
            'She does not yet know the workflow, so tell her exactly what to do next for THIS consultation given its current state.',
            'Recommend only actions from the provided toolbox — never invent tools or steps.',
            'Skip anything already done or not applicable to this state.',
            'Order steps by priority (do the blocking action first).',
            'Keep each step to one short, plain sentence a new hire can act on immediately. No jargon, no filler.',
        ]);
    }

    private function prompt(ConsultationRequest $c): string
    {
        $state = implode("\n", $this->stateLines($c));
        $toolbox = implode("\n", $this->toolbox());

        return <<<TXT
        Here is the consultation's current state:

        {$state}

        These are the only admin actions available for this consultation:

        {$toolbox}

        Tell Fadwa the next steps to move this consultation forward from where it is now.
        TXT;
    }

    /** @return array<int,string> */
    private function stateLines(ConsultationRequest $c): array
    {
        $c->loadMissing('plan', 'expert', 'project', 'invoice');

        $country = $c->countryName();

        return array_values(array_filter([
            'Reference: '.$c->reference,
            'Offer: '.$c->offerTitle(),
            'Origin: '.($c->isCustom() ? 'custom request (admin prices it)' : 'plan purchase'),
            $c->name ? 'Prospect: '.$c->name : 'Prospect: unknown (no name captured)',
            $c->company ? 'Company: '.$c->company : null,
            $country ? 'Country: '.$country : null,
            'Contact on file: '.$c->contactSummary(),
            'Payment status: '.$c->status->label().($c->isPaid() ? ' (settled)' : ''),
            'Price: '.($c->isUnpriced() ? 'NOT SET YET (unpriced custom lead)' : $c->formattedAmount()),
            $c->isExpired() ? 'Payment link: EXPIRED (past pay-by date)' : null,
            'Work status: '.$c->work_status->label(),
            $c->isLead()
                ? 'CRM lead stage: '.$c->lead_stage->label().' (source: '.($c->lead_source ?? 'unknown').')'
                : 'Not tracked as a CRM lead',
            $c->isLead() && $c->next_follow_up_at
                ? 'Follow-up: '.$c->next_follow_up_at->toDateString().' ('.$c->followUpBucket().')'
                : ($c->isLead() ? 'Follow-up: none scheduled' : null),
            $c->lost_reason ? 'Lost reason: '.$c->lost_reason : null,
            'Assigned expert: '.($c->expert?->name ?? 'none assigned'),
            'Delivery project: '.($c->project ? 'exists ('.$c->project->status->label().')' : 'not created yet'),
            'Invoice: '.($c->invoice ? 'issued' : 'none yet'),
            'Attachments from client: '.$c->getMedia('attachments')->count(),
        ]));
    }

    /** @return array<int,string> */
    private function toolbox(): array
    {
        return [
            '- Set the price (only for an unpriced custom lead)',
            '- Send the payment link to the client',
            '- Log a note, or record a contact (phone/video/email) with the client',
            '- Move the CRM lead stage forward (New → Contacted → Quoted; Won happens automatically on payment; Lost needs a reason)',
            '- Schedule a follow-up date so the lead is not forgotten',
            '- Assign an expert (this also creates/links the delivery project)',
            '- Update the work status (New → In progress → Resolved → Closed)',
            '- Open the delivery project to add or complete milestones',
            '- View or download the invoice (created automatically once paid)',
            '- Use AI assist to draft a proposal or a follow-up email',
        ];
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'stage' => ['type' => 'STRING'],
                'summary' => ['type' => 'STRING'],
                'steps' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'title' => ['type' => 'STRING'],
                            'detail' => ['type' => 'STRING'],
                            'priority' => ['type' => 'STRING', 'enum' => ['now', 'soon', 'later']],
                        ],
                        'required' => ['title', 'detail', 'priority'],
                    ],
                ],
            ],
            'required' => ['stage', 'summary', 'steps'],
        ];
    }
}
