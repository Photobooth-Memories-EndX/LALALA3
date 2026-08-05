@php
    $pageDurationValue = old('page_duration', (int) ($content->page_duration ?: 15));
    $totalPageValue = old('total_page', (int) ($content->total_page ?: 0));
    $isDocument = old('type', $content->type) === 'Document';
@endphp

<div id="document-duration-fields"
     class="mt-3"
     style="{{ $isDocument ? '' : 'display: none;' }}">
    <div class="mb-3">
        <label class="form-label required" for="total_page">Total Page</label>
        <input type="number"
               class="form-control @error('total_page') is-invalid @enderror"
               name="total_page"
               id="total_page"
               value="{{ $totalPageValue }}"
               min="1"
               max="100000"
               step="1"
               inputmode="numeric"
               placeholder="30">
        @error('total_page')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-hint">Saved Total Page value. You may adjust it manually.</small>
        <small id="edit-document-page-status" class="form-hint d-block mt-2" aria-live="polite"></small>
        <div id="edit-detected-page-actions" class="d-none flex-wrap align-items-center gap-2 mt-2">
            <span class="badge bg-azure-lt text-azure">
                Detected PDF Pages: <strong id="edit-detected-page-count">0</strong>
            </span>
            <button type="button"
                    class="btn btn-sm btn-outline-secondary"
                    id="edit-use-detected-page-count">
                Use Detected Page Count
            </button>
        </div>
    </div>

    <div class="mb-0">
        <label class="form-label required" for="page_duration">Page Duration (Seconds)</label>
        <input type="number"
               class="form-control @error('page_duration') is-invalid @enderror"
               name="page_duration"
               id="page_duration"
               value="{{ $pageDurationValue }}"
               min="1"
               max="300"
               step="1"
               inputmode="numeric"
               placeholder="15">
        @error('page_duration')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="document-duration-estimate mt-2" aria-live="polite">
            <div class="text-secondary fw-semibold mb-1">Estimated Duration</div>
            <div class="small text-secondary">
                Total Page: <span id="edit-total-page-estimate">{{ $totalPageValue }}</span>
            </div>
            <div class="small text-secondary">
                Page Duration: <span id="edit-page-duration-estimate">{{ $pageDurationValue }}</span> sec
            </div>
            <div class="small text-secondary">
                Estimated: <strong id="edit-duration-estimate">00:00:00</strong>
            </div>
            <div id="edit-duration-sync-status"
                 class="small mt-2 text-warning"
                 role="status"
                 aria-live="polite">
                <i id="edit-duration-sync-status-icon" class="ti ti-alert-triangle me-1" aria-hidden="true"></i>
                <span id="edit-duration-sync-status-text">Duration not synchronized</span>
            </div>
        </div>
    </div>
</div>
