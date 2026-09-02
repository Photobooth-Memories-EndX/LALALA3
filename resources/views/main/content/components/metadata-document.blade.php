@php
    $content = $content ?? null;
    $mode = $mode ?? ($content ? 'edit' : 'create');
    $isLocked = $isLocked ?? ($content && $content->isPublished());

    $durationVal = old('duration');
    if ($durationVal === null && $content) {
        $durationVal = \App\Support\DurationFormatter::format($content->duration, '');
    }

    $hasRequireOld = old('require_page_duration') !== null;
    $requirePageDuration = $hasRequireOld
        ? (bool) old('require_page_duration')
        : ($content ? ((int) ($content->page_duration ?? 30) > 0) : true);

    if ($requirePageDuration) {
        $pageDurationValue = old('page_duration', (int) ($content?->page_duration ?: 30));
        if ($pageDurationValue < 1) {
            $pageDurationValue = 30;
        }
    } else {
        $pageDurationValue = 0;
    }

    $totalPageValue = old('total_page', old('document_total_pages', (int) ($content?->total_page ?: 0)));

    $initialTotalSeconds = $requirePageDuration ? ((int) $totalPageValue * (int) $pageDurationValue) : 0;
    $initialEstimatedResult = $requirePageDuration
        ? \App\Support\DurationFormatter::format($initialTotalSeconds, '00:00:00')
        : 'Self-paced';
    $isInitialSynced = !empty($durationVal) && ($durationVal === ($requirePageDuration ? $initialEstimatedResult : '00:00:00'));
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
               placeholder="Auto-detected from PDF or enter manually"
               {{ $isLocked ? 'disabled' : '' }}>
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
                <button type="button" class="btn btn-sm btn-outline-secondary" id="edit-use-detected-page-count" {{ $isLocked ? 'disabled' : '' }}>
                    Use Detected Page Count
                </button>
            </div>
        @endif
    </div>

    {{-- 2. Page Duration (Seconds) --}}
    <div class="col-md-6">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label mb-0 required" for="page_duration">Page Duration (Seconds)</label>
            <label class="form-check form-switch mb-0 cursor-pointer" title="Toggle between Timed Pagination Mode and Continuous Scroll Mode">
                <input type="hidden" name="require_page_duration" value="0">
                <input class="form-check-input"
                       type="checkbox"
                       id="require_page_duration"
                       name="require_page_duration"
                       value="1"
                       {{ $requirePageDuration ? 'checked' : '' }}
                       {{ $isLocked ? 'disabled' : '' }}>
                <span class="form-check-label small fw-medium">Require Page Duration</span>
            </label>
        </div>

        <input type="number"
               class="form-control @error('page_duration') is-invalid @enderror"
               name="page_duration"
               id="page_duration"
               value="{{ $pageDurationValue }}"
               min="{{ $requirePageDuration ? 1 : 0 }}"
               max="300"
               step="1"
               inputmode="numeric"
               placeholder="30"
               {{ ($requirePageDuration && !$isLocked) ? '' : 'readonly' }}
               {{ $isLocked ? 'disabled' : '' }}
               style="{{ ($requirePageDuration && !$isLocked) ? '' : 'background-color: var(--tblr-gray-200, #e9ecef); cursor: not-allowed;' }}">
        @error('page_duration')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small id="page-duration-hint" class="form-hint" style="{{ $requirePageDuration ? '' : 'display: none;' }}">Minimum reading time for each PDF page.</small>
        <div id="document-scroll-mode-notice" class="document-scroll-mode-notice mt-2" style="{{ $requirePageDuration ? 'display: none;' : '' }}">
            <i class="ti ti-info-circle fs-2 me-1" aria-hidden="true"></i>
            <div>
                Page duration is disabled. This document will use Scroll Mode.
            </div>
        </div>
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
                        {{ ($isInitialSynced || $isLocked) ? 'disabled' : '' }}>
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
            autocomplete="off"
            {{ $isLocked ? 'disabled' : '' }}>
        @error('duration')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-hint">This field is automatically generated from Total Page &times; Page Duration after synchronization and cannot be edited manually.</small>
    </div>
</div>
