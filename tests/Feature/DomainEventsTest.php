<?php

namespace Tests\Feature;

use App\Actions\Payments\FlagPayment;
use App\Actions\Payments\RecordConsultationPayment;
use App\Enums\ConsultationWorkStatus;
use App\Events\ConsultationPaid;
use App\Events\ConsultationStatusChanged;
use App\Events\CustomPlanRequested;
use App\Events\ExpertAssigned;
use App\Events\PaymentFlagged;
use App\Listeners\NotifyAdminsOfPaidConsultation;
use App\Models\ConsultationRequest;
use App\Models\Expert;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\NewConsultationPaidNotification;
use App\Payments\VerifiedPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DomainEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
        Role::findOrCreate('client');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_recording_a_payment_dispatches_consultation_paid(): void
    {
        Event::fake([ConsultationPaid::class]);

        $consultation = ConsultationRequest::create(['amount' => 5000, 'currency' => 'USD', 'status' => 'pending']);

        RecordConsultationPayment::run($consultation, 'stripe', new VerifiedPayment(
            reference: 'cs_evt', amountMinor: 5000, currency: 'USD', payerEmail: 'b@example.com',
        ));

        Event::assertDispatched(ConsultationPaid::class, fn ($e) => $e->consultation->is($consultation));
    }

    public function test_flagging_a_payment_dispatches_payment_flagged(): void
    {
        Event::fake([PaymentFlagged::class]);

        $consultation = ConsultationRequest::create(['amount' => 5000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()]);
        $payment = Payment::create([
            'consultation_request_id' => $consultation->id, 'gateway' => 'stripe', 'reference' => 'r1',
            'amount' => 5000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now(),
        ]);

        FlagPayment::run($payment, 'refunded');

        Event::assertDispatched(PaymentFlagged::class, fn ($e) => $e->reason === 'refunded');
    }

    public function test_status_change_dispatches_event(): void
    {
        Event::fake([ConsultationStatusChanged::class]);

        $consultation = ConsultationRequest::create(['amount' => 5000, 'currency' => 'USD', 'status' => 'paid', 'work_status' => 'new']);

        $this->actingAs($this->admin)
            ->patch(route('admin.consultations.status', $consultation), ['work_status' => 'in_progress'])
            ->assertRedirect();

        Event::assertDispatched(
            ConsultationStatusChanged::class,
            fn ($e) => $e->to === ConsultationWorkStatus::InProgress && $e->from === ConsultationWorkStatus::New,
        );
    }

    public function test_assigning_an_expert_dispatches_event(): void
    {
        Event::fake([ExpertAssigned::class]);

        $consultation = ConsultationRequest::create(['amount' => 5000, 'currency' => 'USD', 'status' => 'paid']);
        $expert = Expert::create(['name' => 'Mara', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.assign', $consultation), ['expert_id' => $expert->id])
            ->assertRedirect();

        Event::assertDispatched(ExpertAssigned::class, fn ($e) => $e->expert?->is($expert));
    }

    public function test_custom_plan_request_dispatches_event(): void
    {
        Event::fake([CustomPlanRequested::class]);

        $this->post(route('custom-plan.store'), [
            'name' => 'Jane', 'email' => 'jane@example.com',
            'message' => 'We need cross-border advisory.',
        ])->assertRedirect();

        Event::assertDispatched(CustomPlanRequested::class);
    }

    public function test_listener_notifies_admins_when_a_consultation_is_paid(): void
    {
        Notification::fake();

        $consultation = ConsultationRequest::create(['amount' => 5000, 'currency' => 'USD', 'status' => 'paid']);

        // Fire the real listener against the event — the wiring end-to-end.
        (new NotifyAdminsOfPaidConsultation)->handle(new ConsultationPaid($consultation));

        Notification::assertSentTo($this->admin, NewConsultationPaidNotification::class);
    }
}
