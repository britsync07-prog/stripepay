<?php

namespace App\Notifications;

use App\Models\ConsultationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Acknowledges a client's custom-plan request by email. Sent on submission to
 * the (not-yet-provisioned) buyer's email — a receipt confirming we received
 * the request and will follow up with a tailored scope, price and payment link.
 * Mail-only: the client has no account yet, so there is nothing to store against.
 */
class CustomPlanReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ConsultationRequest $request) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We received your request — '.config('app.name'))
            ->greeting('Hello'.($this->request->name ? ', '.$this->request->name : '').'!')
            ->line('Thanks for reaching out. We have received your request for a tailored plan and our team is reviewing it now.')
            ->line('We will follow up shortly with a proposed scope, a price, and a secure payment link.')
            ->line('If there is anything you would like to add in the meantime, just reply to this email.');
    }
}
