<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
    <div class="card-body p-0">

        @php
            $contentfile    = json_decode($content->file ?? 'null');
            $getcontentfile = is_array($contentfile) ? ($contentfile[0] ?? '') : '';
        @endphp

        <div id="contentContainer" class="learning-player">

            @if ($content->type == 'Video')
                <video id="player" playsinline controls
                    @if (!$getcontentfile) style="display:none;" @endif>
                    @if ($getcontentfile)
                        <source src="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile) }}"
                                type="video/mp4">
                    @endif
                    Your browser does not support the video tag.
                </video>

            @elseif ($content->type == 'YouTube')
                <div id="player"
                     data-plyr-provider="youtube"
                     data-plyr-embed-id="{{ $content->link }}">
                </div>

            @elseif (in_array($content->type, ['Document', 'SOP/Regulation']))
                <div class="d-flex align-items-center justify-content-center p-5 text-muted">
                    <div class="text-center">
                        <i class="fas fa-file-alt fa-3x mb-3 text-primary opacity-50"></i>
                        <div class="fw-semibold">Document Content</div>
                        <div class="small mt-1">This chapter is a document type.</div>
                    </div>
                </div>

            @else
                <div class="d-flex align-items-center justify-content-center p-5 text-muted">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning opacity-50"></i>
                        <div class="fw-semibold">No media available</div>
                    </div>
                </div>
            @endif

            @if (in_array($content->type, ['Video', 'YouTube']))
                @php
                    $time_duration = explode(':', $content->duration ?? '0:00');
                    $minutes = (int) ($time_duration[0] ?? 0);
                    $seconds = (int) ($time_duration[1] ?? 0);
                @endphp
                <input type="hidden" id="player_duration" value="{{ ($minutes * 60) + $seconds }}">
            @endif

            <input type="hidden" id="player_progress" value="{{ $content_user->progress ?? 0 }}">
            <input type="hidden" data-url="{{ route('content.user.progress', $content->slug) }}" id="player_paused">

        </div>
    </div>
</div>
