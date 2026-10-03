<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class StoreTicketAttachments
{
    /**
     * Store uploaded files privately and attach them to the ticket, or to one of its replies.
     *
     * @param  list<UploadedFile>  $files
     * @return Collection<int, TicketAttachment>
     */
    public function handle(Ticket $ticket, User $uploader, array $files, ?UploadedFile $voiceNote = null, ?TicketComment $comment = null): Collection
    {
        $uploads = collect($files)->map(fn (UploadedFile $file): array => [$file, false]);

        if ($voiceNote !== null) {
            $uploads->push([$voiceNote, true]);
        }

        return $uploads->map(function (array $upload) use ($ticket, $uploader, $comment): TicketAttachment {
            [$file, $isVoiceNote] = $upload;
            $disk = config('helpdesk.attachments.disk');

            $attachment = $ticket->attachments()->make([
                'disk' => $disk,
                'path' => $file->store("attachments/{$ticket->id}", $disk),
                'original_name' => $isVoiceNote ? __('Voice note').'.'.($file->extension() ?: 'webm') : $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'is_voice_note' => $isVoiceNote,
            ]);
            $attachment->comment()->associate($comment);
            $attachment->uploader()->associate($uploader);
            $attachment->save();

            return $attachment;
        });
    }
}
