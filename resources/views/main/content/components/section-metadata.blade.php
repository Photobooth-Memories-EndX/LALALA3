@php
    $content = $content ?? null;
    $mode = $mode ?? ($content ? 'edit' : 'create');
    $stepNumber = $stepNumber ?? 3;
    $contentType = old('type', $content?->type ?? '');
    $isLocked = $isLocked ?? ($content && $content->isPublished());
@endphp

@once
<script>
document.addEventListener("DOMContentLoaded", function () {
    var typeSelect = document.getElementById('type');
    if (!typeSelect) return;

    function updateMetadataVisibility() {
        var selectedType = typeSelect.value || '{{ $contentType }}';

        var docSec = document.getElementById('metadata-section-document');
        var vidSec = document.getElementById('metadata-section-video');
        var ytSec = document.getElementById('metadata-section-youtube');
        var defSec = document.getElementById('metadata-section-default');

        var isDoc = (selectedType === 'Document');
        var isVid = (selectedType === 'Video');
        var isYt = (selectedType === 'YouTube');
        var isDef = (!isDoc && !isVid && !isYt);

        if (docSec) docSec.style.display = isDoc ? '' : 'none';
        if (vidSec) vidSec.style.display = isVid ? '' : 'none';
        if (ytSec) ytSec.style.display = isYt ? '' : 'none';
        if (defSec) defSec.style.display = isDef ? '' : 'none';

        var docDurationInput = document.getElementById('doc_duration');
        var vidDurationInput = document.getElementById('video_duration');
        var ytDurationInput = document.getElementById('youtube_duration');
        var defDurationInput = document.getElementById('default_duration');

        if (docDurationInput) docDurationInput.name = isDoc ? 'duration' : '_disabled_duration';
        if (vidDurationInput) vidDurationInput.name = isVid ? 'duration' : '_disabled_duration';
        if (ytDurationInput) ytDurationInput.name = isYt ? 'duration' : '_disabled_duration';
        if (defDurationInput) defDurationInput.name = isDef ? 'duration' : '_disabled_duration';

        if (isDoc && window.editDocumentDuration) {
            window.editDocumentDuration.syncType();
        }
    }

    typeSelect.addEventListener('change', updateMetadataVisibility);
    updateMetadataVisibility();
});
</script>
@endonce

<div class="card owl-main-card mb-3">
    <div class="card-header d-flex align-items-center">
        <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">{{ $stepNumber }}</span>
        <h3 class="card-title mb-0">Metadata Content</h3>
    </div>
    <div class="card-body">
        
        {{-- Type-Specific Metadata Sections --}}
        <div id="metadata-section-document" class="mb-3" style="{{ $contentType === 'Document' ? '' : 'display: none;' }}">
            @include('main.content.components.metadata-document', ['content' => $content, 'mode' => $mode, 'isLocked' => $isLocked])
        </div>

        <div id="metadata-section-video" class="mb-3" style="{{ $contentType === 'Video' ? '' : 'display: none;' }}">
            @include('main.content.components.metadata-video', ['content' => $content, 'mode' => $mode, 'isLocked' => $isLocked])
        </div>

        <div id="metadata-section-youtube" class="mb-3" style="{{ $contentType === 'YouTube' ? '' : 'display: none;' }}">
            @include('main.content.components.metadata-youtube', ['content' => $content, 'mode' => $mode, 'isLocked' => $isLocked])
        </div>

        <div id="metadata-section-default" class="mb-3" style="{{ (!in_array($contentType, ['Document', 'Video', 'YouTube'])) ? '' : 'display: none;' }}">
            @include('main.content.components.metadata-default', ['content' => $content, 'mode' => $mode, 'isLocked' => $isLocked])
        </div>

        {{-- Shared Metadata Partial (Learning Hours, Publish Date, Deadline) --}}
        @include('main.content.components.metadata-common', ['content' => $content, 'mode' => $mode, 'isLocked' => $isLocked])
    </div>
</div>
