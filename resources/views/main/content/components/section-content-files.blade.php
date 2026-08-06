@php
    $content = $content ?? null;
    $mode = $mode ?? ($content ? 'edit' : 'create');
    $stepNumber = $stepNumber ?? 2;
@endphp

@once
<style>
.compact-dropzone {
    min-height: 48px !important;
    padding: 6px 12px !important;
    border: 1.5px dashed var(--tblr-border-color, #dce1e7) !important;
    border-radius: var(--tblr-border-radius, .375rem) !important;
    background: var(--tblr-bg-surface-secondary, rgba(98, 105, 118, .03)) !important;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    cursor: pointer;
    transition: border-color .15s ease-in-out, background-color .15s ease-in-out;
}
.compact-dropzone:hover {
    border-color: var(--tblr-primary, #206bc4) !important;
    background: rgba(32, 107, 196, .04) !important;
}
.compact-dropzone.dz-started .dz-message {
    display: none !important;
}
.compact-dropzone .dz-preview {
    min-height: 0 !important;
    margin: 0 !important;
    padding: 4px 10px !important;
    background: var(--tblr-bg-surface, #fff) !important;
    border: 1px solid var(--tblr-border-color, #dce1e7) !important;
    border-radius: var(--tblr-border-radius, .375rem) !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-size: 12px !important;
    max-width: 100%;
}
.compact-dropzone .dz-preview .dz-image {
    display: none !important;
}
.compact-dropzone .dz-preview .dz-details {
    padding: 0 !important;
    opacity: 1 !important;
    position: static !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    background: transparent !important;
    color: inherit !important;
}
.compact-dropzone .dz-preview .dz-filename {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 200px;
}
.compact-dropzone .dz-preview .dz-filename span {
    font-weight: 500;
}
.compact-dropzone .dz-preview .dz-size {
    margin: 0 !important;
    font-size: 11px !important;
    color: var(--tblr-muted, #6c757d);
}
.compact-dropzone .dz-preview .dz-progress,
.compact-dropzone .dz-preview .dz-error-mark,
.compact-dropzone .dz-preview .dz-success-mark {
    display: none !important;
}
.compact-dropzone .dz-preview a.dz-remove {
    font-size: 12px !important;
    font-weight: 500 !important;
    color: var(--tblr-danger, #d63939) !important;
    text-decoration: none !important;
    margin-left: 6px !important;
    padding: 2px 6px;
    border-radius: 4px;
    background: rgba(214, 57, 57, .08);
}
.compact-dropzone .dz-preview a.dz-remove:hover {
    background: rgba(214, 57, 57, .18);
}

[data-bs-theme="dark"] .compact-dropzone,
body.dark-mode .compact-dropzone {
    background: rgba(255, 255, 255, .02) !important;
    border-color: rgba(255, 255, 255, .15) !important;
}
[data-bs-theme="dark"] .compact-dropzone .dz-preview,
body.dark-mode .compact-dropzone .dz-preview {
    background: #1f2937 !important;
    border-color: rgba(255, 255, 255, .15) !important;
}
</style>
@endonce



{{-- Combined Content Files Card --}}
<div class="card owl-main-card mb-3">
    <div class="card-header d-flex align-items-center">
        <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">{{ $stepNumber }}</span>
        <h3 class="card-title mb-0">Content Files</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Content File --}}
            <div class="col-md-6 col-12">
                <label class="form-label fw-semibold mb-1">
                    {{ $mode === 'edit' ? 'Replace Content File' : 'Content File' }}
                </label>
                <div class="dropzone compact-dropzone" id="document-dropzone">
                    <div class="dz-message m-0 d-flex align-items-center gap-2">
                        <span class="btn btn-outline-secondary btn-sm px-3 d-inline-flex align-items-center gap-1" style="height: 36px; pointer-events: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-upload" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                                <path d="M7 9l5 -5l5 5"></path>
                                <path d="M12 4l0 12"></path>
                            </svg>
                            <span>Choose File</span>
                        </span>
                        <span class="text-muted small">or drop file here</span>
                    </div>
                    <div class="fallback"></div>
                </div>
                <small class="text-muted d-block mt-1" style="font-size: 11px;">Format: PDF, MP4{{ $mode === 'create' ? ', AVI' : '' }} — maks. 1 file, 1 GB</small>
            </div>

            {{-- Thumbnail File --}}
            <div class="col-md-6 col-12">
                <label class="form-label fw-semibold mb-1">Thumbnail</label>
                <div class="dropzone compact-dropzone" id="thumbnail-dropzone">
                    <div class="dz-message m-0 d-flex align-items-center gap-2">
                        <span class="btn btn-outline-secondary btn-sm px-3 d-inline-flex align-items-center gap-1" style="height: 36px; pointer-events: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-photo-plus" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M15 8h.01"></path>
                                <path d="M12.5 21h-6.5a3 3 0 0 1 -3 -3v-12a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v6.5"></path>
                                <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l4 4"></path>
                                <path d="M14 14l1 -1c.67 -.644 1.45 -.863 2.2 -.653"></path>
                                <path d="M16 19h6"></path>
                                <path d="M19 16v6"></path>
                            </svg>
                            <span>Choose Thumbnail</span>
                        </span>
                        <span class="text-muted small">or drop file here</span>
                    </div>
                    <div class="fallback"></div>
                </div>
                <small class="text-muted d-block mt-1" style="font-size: 11px;">Format: JPG only — maks. 1 file, 1 GB</small>
            </div>
        </div>
    </div>
</div>
