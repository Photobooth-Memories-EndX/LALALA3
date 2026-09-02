@php
    $content = $content ?? null;
    $isLocked = $isLocked ?? ($content && $content->isPublished());

    $durationVal = old('duration');
    if ($durationVal === null && $content) {
        $durationVal = \App\Support\DurationFormatter::format($content->duration, '');
    }
@endphp

<div class="row g-3">
    <div class="col-12">
        <label class="form-label" for="video_duration">Video Duration (HH:MM:SS)</label>
        <input type="text" class="form-control @error('duration') is-invalid @enderror" placeholder="00:06:26"
            name="duration" id="video_duration"
            value="{{ $durationVal }}"
            pattern="^(\d+:[0-5]\d|\d{2}:[0-5]\d:[0-5]\d)$"
            autocomplete="off"
            {{ $isLocked ? 'disabled' : '' }}>
        @error('duration')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-hint">Format: HH:MM:SS. Automatically detected when a local video file is attached.</small>
    </div>
</div>
