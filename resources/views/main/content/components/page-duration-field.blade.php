@php
    $content = $content ?? null;
    $pageDurationValue = old('page_duration', (int) ($content?->page_duration ?: 15));
@endphp

<div id="document-page-duration-row"
     class="mt-3"
     style="{{ old('type', $content?->type) === 'Document' ? '' : 'display: none;' }}">
    <label class="form-label required" for="page_duration">Page Duration (Seconds)</label>
    <input type="number"
           class="form-control @error('page_duration') is-invalid @enderror"
           name="page_duration"
           id="page_duration"
           value="{{ $pageDurationValue }}"
           min="1"
           max="300"
           step="1"
           placeholder="15">
    @error('page_duration')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <input type="hidden"
           name="document_total_pages"
           id="document_total_pages"
           value="{{ old('document_total_pages') }}">
    <small class="form-hint">
        Minimum reading time for each PDF page. Calculated duration:
        <span id="document-calculated-duration" class="fw-semibold">—</span>
    </small>
    <small id="document-page-count-status" class="form-hint d-block" aria-live="polite"></small>
</div>
