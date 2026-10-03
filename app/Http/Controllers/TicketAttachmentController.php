<?php

namespace App\Http\Controllers;

use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    /**
     * Serve an attachment to users who may see it. Images, audio and PDFs open inline unless a download is requested.
     */
    public function show(Request $request, TicketAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->ticket);

        if ($attachment->comment?->is_internal) {
            Gate::authorize('commentInternal', $attachment->ticket);
        }

        $disk = Storage::disk($attachment->disk);
        $headers = ['Content-Type' => $attachment->mime_type, 'X-Content-Type-Options' => 'nosniff'];

        $isInline = ! $request->boolean('download')
            && ($attachment->isImage() || $attachment->isAudio() || $attachment->mime_type === 'application/pdf')
            && $attachment->mime_type !== 'image/svg+xml';

        return $isInline
            ? $disk->response($attachment->path, $attachment->original_name, $headers)
            : $disk->download($attachment->path, $attachment->original_name, $headers);
    }
}
