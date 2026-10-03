<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;

trait ValidatesAttachments
{
    /**
     * Get the validation rules for uploaded files and a recorded voice note.
     *
     * @return array<string, array<int, string>>
     */
    protected function attachmentRules(): array
    {
        $config = config('helpdesk.attachments');

        return [
            'attachments' => ['nullable', 'array', "max:{$config['max_files']}"],
            'attachments.*' => ['file', "max:{$config['max_size']}", 'extensions:'.implode(',', $config['extensions'])],
            'voice_note' => ['nullable', 'file', "max:{$config['max_size']}", 'mimetypes:'.implode(',', $config['voice_mime_types'])],
        ];
    }

    /**
     * Get the uploaded attachments.
     *
     * @return list<UploadedFile>
     */
    public function uploadedAttachments(): array
    {
        return array_values($this->file('attachments', []));
    }

    /**
     * Get the attribute names used in validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'attachments.*' => __('attachment'),
            'voice_note' => __('voice note'),
        ];
    }
}
