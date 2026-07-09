<?php

namespace App\Notifications;

use App\Models\ConsultationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A friendly touch-base email sent to a prospect when an admin adds them to the
 * pipeline (manual lead) — acknowledges the conversation and sets the
 * expectation of a follow-up. Queued, mail-only: a lead is not an account.
 */
class LeadCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ConsultationRequest $lead) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Thanks for connecting — '.config('app.name'))
            ->greeting('Hello'.($this->lead->name ? ', '.$this->lead->name : '').'!')
            ->line('Thank you for your interest in '.config('app.name').'. We have added you to our books and a member of our team will be in touch shortly to discuss how we can help.');

        if ($this->lead->company) {
            $mail->line('Company on file: **'.$this->lead->company.'**');
        }

        return $mail
            ->line('If you have any details you would like us to know in advance, just reply to this email.')
            ->line('We look forward to speaking with you.');
    }
}
