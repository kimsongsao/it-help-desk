<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TicketAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

#[Fillable(['disk', 'path', 'original_name', 'mime_type', 'size', 'is_voice_note'])]
class TicketAttachment extends Model
{
    /** @use HasFactory<TicketAttachmentFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_voice_note' => 'boolean',
        ];
    }

    /**
     * Remove the stored file when the attachment is deleted.
     */
    protected static function booted(): void
    {
        static::deleted(function (TicketAttachment $attachment): void {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    /**
     * Get the ticket the file is attached to.
     *
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the reply the file is attached to, if any.
     *
     * @return BelongsTo<TicketComment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'ticket_comment_id');
    }

    /**
     * Get the user who uploaded the file.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Determine if the file can be previewed as an image.
     */
    public function isImage(): bool
    {
        return Str::startsWith($this->mime_type, 'image/');
    }

    /**
     * Determine if the file can be played as audio.
     */
    public function isAudio(): bool
    {
        return $this->is_voice_note || Str::startsWith($this->mime_type, 'audio/');
    }

    /**
     * Get the file size in a human readable form.
     */
    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    /**
     * Get the Tabler icon that represents the file type.
     */
    public function icon(): string
    {
        return match (true) {
            $this->isImage() => 'ti-photo',
            $this->isAudio() => 'ti-microphone',
            $this->mime_type === 'application/pdf' => 'ti-file-type-pdf',
            Str::contains($this->mime_type, ['spreadsheet', 'excel', 'csv']) => 'ti-file-spreadsheet',
            Str::contains($this->mime_type, ['word', 'document']) => 'ti-file-type-doc',
            Str::contains($this->mime_type, ['zip', 'compressed']) => 'ti-file-zip',
            default => 'ti-file',
        };
    }
}
