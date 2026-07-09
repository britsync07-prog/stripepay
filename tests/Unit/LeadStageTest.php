<?php

namespace Tests\Unit;

use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeadStageTest extends TestCase
{
    public function test_only_won_and_lost_are_terminal(): void
    {
        $this->assertFalse(LeadStage::New->isTerminal());
        $this->assertFalse(LeadStage::Contacted->isTerminal());
        $this->assertFalse(LeadStage::Quoted->isTerminal());
        $this->assertTrue(LeadStage::Won->isTerminal());
        $this->assertTrue(LeadStage::Lost->isTerminal());
    }

    public function test_options_carry_value_label_and_tone_for_every_case(): void
    {
        $options = LeadStage::options();

        $this->assertCount(5, $options);
        $this->assertSame(
            array_map(fn (LeadStage $s) => $s->value, LeadStage::cases()),
            array_column($options, 'value'),
        );
        foreach ($options as $option) {
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('tone', $option);
            $this->assertNotSame('', $option['tone']);
        }
    }

    public function test_follow_up_is_overdue_only_when_past_and_open(): void
    {
        $overdue = new ConsultationRequest([
            'lead_stage' => LeadStage::Contacted,
            'next_follow_up_at' => Carbon::now()->subDay(),
        ]);
        $this->assertTrue($overdue->isFollowUpOverdue());
    }

    public function test_a_future_follow_up_is_not_overdue(): void
    {
        $future = new ConsultationRequest([
            'lead_stage' => LeadStage::Contacted,
            'next_follow_up_at' => Carbon::now()->addDay(),
        ]);
        $this->assertFalse($future->isFollowUpOverdue());
    }

    public function test_a_terminal_lead_is_never_flagged_overdue(): void
    {
        foreach ([LeadStage::Won, LeadStage::Lost] as $stage) {
            $lead = new ConsultationRequest([
                'lead_stage' => $stage,
                'next_follow_up_at' => Carbon::now()->subWeek(),
            ]);
            $this->assertFalse($lead->isFollowUpOverdue(), "{$stage->value} should not be overdue");
        }
    }

    public function test_no_follow_up_date_is_not_overdue(): void
    {
        $lead = new ConsultationRequest(['lead_stage' => LeadStage::New]);
        $this->assertFalse($lead->isFollowUpOverdue());
    }

    public function test_is_lead_reflects_the_stage_presence(): void
    {
        $this->assertTrue((new ConsultationRequest(['lead_stage' => LeadStage::New]))->isLead());
        $this->assertFalse((new ConsultationRequest)->isLead());
    }
}
