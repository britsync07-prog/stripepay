<?php

namespace App\Notifications;

use App\Models\ConsultationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails a client their custom payment link. Sent to an existing client (mail +
 * database) or routed on-demand to a not-yet-provisioned buyer's email (mail).
 */
class PaymentLinkNotification extends Notification
{
    use Queueable;

    public function __construct(public ConsultationRequest $request) {}

    public function via(object $notifiable): array
    {
        // Anonymous (on-demand) recipients have no record to store against.
        return $notifiable instanceof AnonymousNotifiable
            ? ['mail']
            : ['mail', 'database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $quote = $this->request->isQuotation();

        return [
            'type' => 'payment_link',
            'title' => $quote ? 'Quotation ready' : 'Payment requested',
            'message' => ($quote ? $this->request->quote_number.' — ' : '')
                .$this->request->offerTitle().' — '.$this->request->formattedAmount(),
            'url' => $quote
                ? route('consultation.quote', $this->request, false)
                : route('consultation.checkout', $this->request, false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // A formal quotation links to the document (review + accept + pay); a
        // bare payment link goes straight to checkout.
        if ($this->request->isQuotation()) {
            $mail = (new MailMessage)
                ->subject('Your quotation '.$this->request->quote_number.' — '.$this->request->offerTitle())
                ->greeting('Hello'.($this->request->name ? ', '.$this->request->name : '').'!')
                ->line('Your quotation **'.$this->request->quote_number.'** for **'.$this->request->offerTitle().'** is ready to review.')
                ->line('Total due: **'.$this->request->formattedAmount().'**')
                ->action('View quotation', $this->request->quoteUrl());
        } else {
            $mail = (new MailMessage)
                ->subject('Your payment link — '.$this->request->offerTitle())
                ->greeting('Hello'.($this->request->name ? ', '.$this->request->name : '').'!')
                ->line('Here is your secure payment link for **'.$this->request->offerTitle().'**.')
                ->line('Amount due: **'.$this->request->formattedAmount().'**')
                ->action('Pay '.$this->request->formattedAmount(), $this->request->checkoutUrl());
        }

        if ($this->request->expires_at) {
            $label = $this->request->isQuotation() ? 'This quotation is valid until ' : 'This link is valid until ';
            $mail->line($label.$this->request->expires_at->toFormattedDayDateString().'.');
        }

        return $mail->line('If you were not expecting this, you can safely ignore this email.');
    }
}
