<?php

namespace Tests\Unit\Enums;

use App\Enums\ConsultationWorkStatus as Status;
use Tests\TestCase;

/**
 * The consultation fulfilment state machine. Transition rules live on the enum
 * (not the Action), so they are unit-testable without a database.
 */
class ConsultationWorkStatusTest extends TestCase
{
    public function test_each_state_declares_its_legal_transitions(): void
    {
        $this->assertSame([Status::InProgress, Status::Cancelled], Status::New->allowedTransitions());
        $this->assertSame([Status::Resolved, Status::Closed, Status::Cancelled], Status::InProgress->allowedTransitions());
        $this->assertSame([Status::Closed, Status::InProgress, Status::Cancelled], Status::Resolved->allowedTransitions());
        $this->assertSame([Status::InProgress], Status::Closed->allowedTransitions());
        $this->assertSame([Status::InProgress], Status::Cancelled->allowedTransitions());
    }

    public function test_can_transition_to_accepts_only_legal_moves(): void
    {
        $this->assertTrue(Status::New->canTransitionTo(Status::InProgress));
        $this->assertTrue(Status::InProgress->canTransitionTo(Status::Resolved));
        $this->assertTrue(Status::Resolved->canTransitionTo(Status::InProgress)); // reopen
        $this->assertTrue(Status::Closed->canTransitionTo(Status::InProgress));   // reopen
    }

    public function test_can_transition_to_rejects_illegal_moves(): void
    {
        // New can't jump straight to Resolved/Closed.
        $this->assertFalse(Status::New->canTransitionTo(Status::Resolved));
        $this->assertFalse(Status::New->canTransitionTo(Status::Closed));
        // Nothing may transition to itself via canTransitionTo.
        $this->assertFalse(Status::InProgress->canTransitionTo(Status::InProgress));
        // Closed can't go directly back to Resolved.
        $this->assertFalse(Status::Closed->canTransitionTo(Status::Resolved));
    }

    public function test_transition_options_lead_with_the_current_state_and_dedupe(): void
    {
        $options = Status::InProgress->transitionOptions();

        // First option is always the current state (a no-op "keep") ...
        $this->assertSame('in_progress', $options[0]['value']);
        $this->assertSame('In progress', $options[0]['label']);

        // ... followed by the legal targets, with no duplicate values.
        $values = array_column($options, 'value');
        $this->assertSame(['in_progress', 'resolved', 'closed', 'cancelled'], $values);
        $this->assertSame($values, array_values(array_unique($values)));
    }

    public function test_options_exposes_every_case_as_value_label_pairs(): void
    {
        $this->assertSame([
            ['value' => 'new', 'label' => 'New'],
            ['value' => 'in_progress', 'label' => 'In progress'],
            ['value' => 'resolved', 'label' => 'Resolved'],
            ['value' => 'closed', 'label' => 'Closed'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
        ], Status::options());
    }

    public function test_progress_steps_are_the_linear_stages_only(): void
    {
        $this->assertSame([
            ['value' => 'new', 'label' => 'New'],
            ['value' => 'in_progress', 'label' => 'In progress'],
            ['value' => 'resolved', 'label' => 'Resolved'],
            ['value' => 'closed', 'label' => 'Closed'],
        ], Status::progressSteps());
    }

    public function test_a_consultation_can_be_cancelled_then_reopened(): void
    {
        $this->assertTrue(Status::InProgress->canTransitionTo(Status::Cancelled));
        $this->assertTrue(Status::New->canTransitionTo(Status::Cancelled));
        $this->assertTrue(Status::Cancelled->canTransitionTo(Status::InProgress));
        // A finished engagement isn't "cancelled".
        $this->assertFalse(Status::Closed->canTransitionTo(Status::Cancelled));
    }
}
