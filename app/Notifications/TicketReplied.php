<?php

namespace App\Notifications;

use App\Models\TicketComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketReplied extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TicketComment $comment) {}

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
        $ticket = $this->comment->ticket;

        return (new MailMessage)
            ->subject(__('[:reference] New reply: :subject', ['reference' => $ticket->reference, 'subject' => $ticket->subject]))
            ->line($this->message())
            ->line($this->comment->body)
            ->action(__('View ticket'), route('tickets.show', $ticket));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $ticket = $this->comment->ticket;

        return [
            'ticket_id' => $ticket->id,
            'reference' => $ticket->reference,
            'subject' => $ticket->subject,
            'message' => $this->message(),
            'icon' => 'ti-message',
        ];
    }

    /**
     * Get the human readable message.
     */
    private function message(): string
    {
        return __(':name replied to :reference.', ['name' => $this->comment->author->name, 'reference' => $this->comment->ticket->reference]);
    }
}
