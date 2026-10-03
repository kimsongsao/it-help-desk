@props(['attachments'])

@if ($attachments->isNotEmpty())
    <div {{ $attributes->class('mt-3') }}>
        @foreach ($attachments->filter->isAudio() as $attachment)
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-red-lt"><i class="ti ti-microphone"></i> {{ $attachment->is_voice_note ? __('Voice note') : $attachment->original_name }}</span>
                <audio controls preload="none" class="voice-player" src="{{ route('attachments.show', $attachment) }}"></audio>
            </div>
        @endforeach

        @php($images = $attachments->filter->isImage())
        @if ($images->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach ($images as $attachment)
                    <a href="{{ route('attachments.show', $attachment) }}" target="_blank" rel="noopener" title="{{ $attachment->original_name }}">
                        <img src="{{ route('attachments.show', $attachment) }}" alt="{{ $attachment->original_name }}" class="attachment-thumb" loading="lazy">
                    </a>
                @endforeach
            </div>
        @endif

        <div class="d-flex flex-wrap gap-2">
            @foreach ($attachments->reject(fn ($attachment) => $attachment->isAudio() || $attachment->isImage()) as $attachment)
                <a href="{{ route('attachments.show', [$attachment, 'download' => 1]) }}" class="file-chip text-reset text-decoration-none pe-3">
                    <i class="ti {{ $attachment->icon() }} text-secondary"></i>
                    <span>{{ $attachment->original_name }}</span>
                    <small class="text-secondary">{{ $attachment->humanSize() }}</small>
                    <i class="ti ti-download text-secondary"></i>
                </a>
            @endforeach
        </div>
    </div>
@endif
