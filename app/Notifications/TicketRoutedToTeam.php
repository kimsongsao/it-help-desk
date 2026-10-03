<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketRoutedToTeam extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

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
        return (new MailMessage)
            ->subject(__('[:reference] New in :team queue: :subject', [
                'reference' => $this->ticket->reference,
                'team' => $this->ticket->team->name,
                'subject' => $this->ticket->subject,
            ]))
            ->line($this->message())
            ->action(__('View ticket'), route('tickets.show', $this->ticket));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'reference' => $this->ticket->reference,
            'subject' => $this->ticket->subject,
            'message' => $this->message(),
            'icon' => 'ti-users-group',
        ];
    }

    /**
     * Get the human readable message.
     */
    private function message(): string
    {
        return __(':reference is waiting in the :team queue.', ['reference' => $this->ticket->reference, 'team' => $this->ticket->team->name]);
    }
}
