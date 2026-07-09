<?php

namespace Tests\Feature;

use App\Actions\Payments\RecordConsultationPayment;
use App\Enums\ConsultationWorkStatus;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Notifications\ConsultationStatusUpdatedNotification;
use App\Notifications\NewConsultationPaidNotification;
use App\Notifications\PaymentFlaggedNotification;
use App\Payments\VerifiedPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealtimeNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_app_notifications_opt_into_the_broadcast_channel(): void
    {
        $user = User::factory()->create();
        $consultation = new ConsultationRequest(['amount' => 5000, 'currency' => 'USD']);

        $this->assertContains('broadcast', (new NewConsultationPaidNotification($consultation))->via($user));
        $this->assertContains('broadcast', (new PaymentFlaggedNotification($consultation, 'refunded'))->via($user));
        $this->assertContains('broadcast', (new ConsultationStatusUpdatedNotification($consultation, ConsultationWorkStatus::InProgress))->via($user));
    }

    public function test_recording_a_payment_broadcasts_to_admins(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('client');
        User::factory()->create()->assignRole('admin');

        Event::fake([BroadcastNotificationCreated::class]);

        $consultation = ConsultationRequest::create(['amount' => 5000, 'currency' => 'USD', 'status' => 'pending']);

        RecordConsultationPayment::run($consultation, 'stripe', new VerifiedPayment(
            reference: 'cs_broadcast', amountMinor: 5000, currency: 'USD', payerEmail: 'b@example.com',
        ));

        Event::assertDispatched(BroadcastNotificationCreated::class);
    }
}
