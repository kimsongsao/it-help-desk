<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachments;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCommentRequest extends FormRequest
{
    use ValidatesAttachments;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('comment', $this->route('ticket'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * A reply needs a message, a file or a voice note.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required_without_all:attachments,voice_note', 'nullable', 'string', 'max:10000'],
            'is_internal' => [$this->user()->can('commentInternal', $this->route('ticket')) ? 'boolean' : 'prohibited'],
            ...$this->attachmentRules(),
        ];
    }
}
