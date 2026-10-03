<?php

namespace App\Notifications;

use App\Models\TicketEscalation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketEscalated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TicketEscalation $escalation) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->escalation->ticket;

        return (new MailMessage)
            ->error()
            ->subject(__('[:reference] Escalated to level :level: :subject', ['reference' => $ticket->reference, 'level' => $this->escalation->level, 'subject' => $ticket->subject]))
            ->line($this->message())
            ->line(__('Reason: :reason', ['reason' => $this->escalation->reason]))
            ->action(__('View ticket'), route('tickets.show', $ticket));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $ticket = $this->escalation->ticket;

        return [
            'ticket_id' => $ticket->id,
            'reference' => $ticket->reference,
            'subject' => $ticket->subject,
            'message' => $this->message(),
            'icon' => 'ti-arrow-big-up-lines',
        ];
    }

    /**
     * Get the human readable message.
     */
    private function message(): string
    {
        return __(':reference was escalated to level :level.', ['reference' => $this->escalation->ticket->reference, 'level' => $this->escalation->level]);
    }
}
