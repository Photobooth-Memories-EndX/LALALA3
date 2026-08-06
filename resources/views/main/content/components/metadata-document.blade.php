@php
    $content = $content ?? null;
    $mode = $mode ?? ($content ? 'edit' : 'create');

    $durationVal = old('duration');
    if ($durationVal === null && $content) {
        $durationVal = \App\Support\DurationFormatter::format($content->duration, '');
    }

    $pageDurationValue = old('page_duration', (int) ($content?->page_duration ?: 15));
    $totalPageValue = old('total_page', old('document_total_pages', (int) ($content?->total_page ?: 0)));

    $initialTotalSeconds = (int) $totalPageValue * (int) $pageDurationValue;
    $initialEstimatedResult = \App\Support\DurationFormatter::format($initialTotalSeconds, '00:00:00');
    $isInitialSynced = !empty($durationVal) && ($durationVal === $initialEstimatedResult);
@endphp

<div id="document-duration-fields" class="row g-3">
    {{-- 1. Total Page --}}
    <div class="col-md-6">
        <label class="form-label required" for="{{ $mode === 'edit' ? 'total_page' : 'document_total_pages' }}">Total Page</label>
        <input type="number"
               class="form-control @error('total_page') is-invalid @enderror @error('document_total_pages') is-invalid @enderror"
               name="{{ $mode === 'edit' ? 'total_page' : 'document_total_pages' }}"
               id="{{ $mode === 'edit' ? 'total_page' : 'document_total_pages' }}"
               value="{{ $totalPageValue ?: '' }}"
               min="1"
               max="100000"
               step="1"
               inputmode="numeric"
               placeholder="Auto-detected from PDF or enter manually">
        @error('total_page')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @error('document_total_pages')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-hint">Auto-detected from uploaded PDF file. You may adjust it manually if needed.</small>
        
        @if ($mode === 'edit')
            <small id="edit-document-page-status" class="form-hint d-block mt-2" aria-live="polite"></small>
            <div id="edit-detected-page-actions" class="d-none flex-wrap align-items-center gap-2 mt-2">
                <span class="badge bg-azure-lt text-azure">
                    Detected PDF Pages: <strong id="edit-detected-page-count">0</strong>
                </span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="edit-use-detected-page-count">
                    Use Detected Page Count
                </button>
            </div>
        @endif
    </div>

    {{-- 2. Page Duration (Seconds) --}}
    <div class="col-md-6">
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
        <small class="form-hint">Minimum reading time for each PDF page.</small>
    </div>

    {{-- 3 & 4. Compact Estimated Duration Information Card --}}
    <div class="col-12">
        <div class="card card-sm border bg-surface-secondary shadow-none my-1" aria-live="polite">
            <div class="card-body py-2 px-3">
                <div class="row align-items-center g-2">
                    {{-- Left Column: Header & Inputs Info --}}
                    <div class="col-md-7 col-12">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="text-body font-weight-bold small">Estimated Duration</span>
                            <span class="badge bg-primary-lt" style="font-size: 10px; padding: 2px 6px;">Auto-Calculated</span>
                        </div>
                        <div class="text-secondary small">
                            Total Page: <strong id="edit-total-page-estimate" class="text-body">{{ $totalPageValue }}</strong>
                            <span class="mx-1 text-muted">•</span>
                            Page Duration: <strong id="edit-page-duration-estimate" class="text-body">{{ $pageDurationValue }}</strong> sec
                        </div>
                    </div>

                    {{-- Right Column: Estimated Result --}}
                    <div class="col-md-5 col-12 text-md-end text-start">
                        <div class="text-muted small" style="font-size: 11px;">Estimated Result</div>
                        <div id="edit-duration-estimate" class="fs-2 font-weight-bold text-primary lh-1">{{ $initialEstimatedResult }}</div>
                    </div>
                </div>
            </div>

            {{-- Compact Footer Bar --}}
            <div class="card-footer py-2 px-3 bg-surface-secondary d-flex align-items-center justify-content-between flex-wrap gap-2 border-top">
                <div id="edit-duration-sync-status" class="small {{ $isInitialSynced ? 'text-success' : 'text-warning' }} d-inline-flex align-items-center" role="status" aria-live="polite">
                    <i id="edit-duration-sync-status-icon" class="ti {{ $isInitialSynced ? 'ti-circle-check' : 'ti-alert-triangle' }} me-1 fs-3" aria-hidden="true"></i>
                    <span id="edit-duration-sync-status-text" class="fw-medium">{{ $isInitialSynced ? 'Duration synchronized' : 'Duration not synchronized' }}</span>
                </div>

                <button type="button"
                        class="btn btn-primary btn-sm edit-duration-sync-button py-1 px-3 ms-auto"
                        id="edit-duration-synchronize"
                        aria-label="Synchronize Duration"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="Synchronize Duration — copies Estimated Duration into Duration field."
                        {{ $isInitialSynced ? 'disabled' : '' }}>
                    <i class="ti ti-refresh me-1" aria-hidden="true"></i> Synchronize Duration
                </button>
            </div>
        </div>
    </div>

    {{-- 5. Duration (HH:MM:SS) - READ ONLY / DISABLED visual style --}}
    <div class="col-12 mt-2">
        <label class="form-label" for="doc_duration">Duration (HH:MM:SS) <span class="badge bg-secondary-lt text-secondary ms-1">Auto Generated</span></label>
        <input type="text"
            class="form-control @error('duration') is-invalid @enderror"
            style="background-color: var(--tblr-gray-200, #e9ecef); border-color: var(--tblr-border-color, #ced4da); color: var(--tblr-body-color, #495057); cursor: not-allowed; opacity: 1;"
            placeholder="00:00:00"
            name="duration"
            id="doc_duration"
            value="{{ $durationVal }}"
            readonly
            aria-readonly="true"
            autocomplete="off">
        @error('duration')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-hint">This field is automatically generated from Total Page &times; Page Duration after synchronization and cannot be edited manually.</small>
    </div>
</div>
