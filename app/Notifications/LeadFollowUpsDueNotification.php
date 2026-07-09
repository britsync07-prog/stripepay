<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Daily digest telling admins how many lead follow-ups are overdue or due today,
 * so the pipeline gets worked without anyone watching the board. One notification
 * per run (not per lead) keeps the bell calm. Database-only for now — add 'mail'
 * to via() once SMTP is configured and the digest will also be emailed.
 */
class LeadFollowUpsDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $dueToday,
        public int $overdue,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lead_follow_up',
            'title' => 'Lead follow-ups due',
            'message' => $this->summary(),
            'url' => route('admin.follow-ups', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Lead follow-ups due')
            ->greeting("Today's follow-ups")
            ->line($this->summary())
            ->action('Open follow-ups', route('admin.follow-ups'));
    }

    private function summary(): string
    {
        $bits = [];

        if ($this->overdue > 0) {
            $bits[] = $this->overdue.' overdue';
        }

        if ($this->dueToday > 0) {
            $bits[] = $this->dueToday.' due today';
        }

        $total = $this->overdue + $this->dueToday;

        return implode(' and ', $bits).' lead follow-up'.($total === 1 ? '' : 's').' to action.';
    }
}
