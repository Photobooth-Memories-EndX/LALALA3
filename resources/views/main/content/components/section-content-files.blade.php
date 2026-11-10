@php
    $content = $content ?? null;
    $mode = $mode ?? ($content ? 'edit' : 'create');
    $stepNumber = $stepNumber ?? 3;
    $isLocked = $isLocked ?? ($content && $content->isPublished());

    // Existing Content File Data (Edit Mode)
    $existingFileName = null;
    $existingFileType = null;
    $existingFileSizeFormatted = null;
    $existingFileUrl = null;
    $hasExistingFile = false;
    $isPdfFile = false;

    if ($mode === 'edit' && $content && !empty($content->file)) {
        $_cf = json_decode($content->file ?? '[]', true) ?: [];
        $_fn = is_array($_cf) ? ($_cf[0] ?? null) : null;
        if ($_fn) {
            $existingFileName = $_fn;
            $existingPath = public_path('storage/files/content/' . $content->id . '/' . $_fn);
            $existingFileUrl = asset('storage/files/content/' . $content->id . '/' . $_fn);
            
            $ext = strtolower(pathinfo($_fn, PATHINFO_EXTENSION));
            $isPdfFile = ($ext === 'pdf');
            if ($isPdfFile) {
                $existingFileType = 'PDF Document';
            } elseif (in_array($ext, ['mp4', 'avi', 'mov', 'webm'])) {
                $existingFileType = 'Video File (' . strtoupper($ext) . ')';
            } else {
                $existingFileType = strtoupper($ext) . ' File';
            }

            if (file_exists($existingPath)) {
                $hasExistingFile = true;
                $bytes = filesize($existingPath);
                if ($bytes >= 1073741824) {
                    $existingFileSizeFormatted = number_format($bytes / 1073741824, 1) . ' GB';
                } elseif ($bytes >= 1048576) {
                    $existingFileSizeFormatted = number_format($bytes / 1048576, 1) . ' MB';
                } elseif ($bytes >= 1024) {
                    $existingFileSizeFormatted = number_format($bytes / 1024, 1) . ' KB';
                } else {
                    $existingFileSizeFormatted = $bytes . ' B';
                }
            }
        }
    }

    // Existing Thumbnail Data (Edit Mode)
    $existingThumbName = null;
    $existingThumbUrl = null;
    $existingThumbSizeFormatted = null;
    $hasExistingThumb = false;

    if ($mode === 'edit' && $content) {
        $thumbLocalPath = public_path('storage/files/content/' . $content->id . '/thumb.jpg');
        if (file_exists($thumbLocalPath)) {
            $hasExistingThumb = true;
            $existingThumbName = ($content->type === 'YouTube') ? 'YouTube Thumbnail' : 'thumb.jpg';
            $existingThumbUrl = asset('storage/files/content/' . $content->id . '/thumb.jpg');
            $bytes = filesize($thumbLocalPath);
            if ($bytes >= 1048576) {
                $existingThumbSizeFormatted = number_format($bytes / 1048576, 1) . ' MB';
            } elseif ($bytes >= 1024) {
                $existingThumbSizeFormatted = number_format($bytes / 1024, 1) . ' KB';
            } else {
                $existingThumbSizeFormatted = $bytes . ' B';
            }
        }
    }
@endphp

@once
<style>
.min-w-0 {
    min-width: 0 !important;
}
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
.compact-dropzone .dz-upload-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    margin-left: auto;
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
            {{-- Content File Column --}}
            <div class="col-md-6 col-12 d-flex flex-column">
                @if ($mode === 'edit' && $hasExistingFile)
                    <div class="mb-2 flex-grow-1 d-flex flex-column">
                        <label class="form-label fw-semibold mb-1 text-muted small text-uppercase" style="letter-spacing: 0.04em;">Current Content File</label>
                        <div class="card card-sm border bg-surface-secondary flex-fill" style="border-radius: var(--tblr-border-radius, .375rem);">
                            <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2 h-100">
                                <div class="d-flex align-items-center gap-2 overflow-hidden flex-fill min-w-0">
                                    <div class="avatar bg-primary-lt text-primary rounded flex-shrink-0" style="width: 34px; height: 34px;">
                                        @if ($isPdfFile)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-danger" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                                <path d="M9 9l1 0" />
                                                <path d="M9 13l6 0" />
                                                <path d="M9 17l6 0" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z" />
                                                <path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="overflow-hidden min-w-0">
                                        <div class="fw-medium text-reset text-truncate small mb-0" title="{{ $existingFileName }}">
                                            {{ $existingFileName }}
                                        </div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            <span>{{ $existingFileType }}</span>
                                            @if ($existingFileSizeFormatted)
                                                <span class="mx-1">•</span>
                                                <span>{{ $existingFileSizeFormatted }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1 ms-auto flex-shrink-0">
                                    <a href="{{ $existingFileUrl }}" class="btn btn-outline-primary btn-sm px-2 py-1" download="{{ $existingFileName }}" target="_blank" title="Download File">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                            <path d="M7 11l5 5l5 -5" />
                                            <path d="M12 4l0 12" />
                                        </svg>
                                        <span class="d-none d-sm-inline ms-1">Download</span>
                                    </a>
                                    @if (!$isLocked)
                                    <a href="#" class="btn btn-outline-danger btn-sm px-2 py-1 btn-remove-content" data-url="{{ route('contentdelete.deletecontentajax', $content->slug) }}" title="Remove File">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M4 7l16 0" />
                                            <path d="M10 11l0 6" />
                                            <path d="M14 11l0 6" />
                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                        </svg>
                                        <span class="d-none d-sm-inline ms-1">Remove</span>
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <label class="form-label fw-semibold mb-1">
                    {{ ($mode === 'edit' && $hasExistingFile) ? 'Replace Content File' : 'Content File' }}
                </label>
                <div class="dropzone compact-dropzone {{ $isLocked ? 'is-locked' : '' }}" id="document-dropzone">
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
                        <span class="text-muted small">{{ $isLocked ? '(Upload locked while published)' : 'or drop file here' }}</span>
                    </div>
                    <div class="fallback"></div>
                </div>
                @php
                    $initialContentType = old('type', $content->type ?? 'Document');
                    $initialFileHelp = ($initialContentType === 'Video')
                        ? 'Format: MP4, AVI, MOV, MKV, WEBM — maks. 1 file, 500 MB'
                        : 'Format: PDF — maks. 1 file, 10 MB';
                @endphp
                <small class="text-muted d-block mt-1" id="content-file-help-text" style="font-size: 11px;">{{ $initialFileHelp }}</small>
            </div>

            {{-- Thumbnail File Column --}}
            <div class="col-md-6 col-12 d-flex flex-column">
                <input type="hidden" name="remove_thumbnail" id="remove_thumbnail" value="0">
                @if ($mode === 'edit' && $hasExistingThumb)
                    <div class="mb-2 flex-grow-1 d-flex flex-column" id="current-thumbnail-wrapper">
                        <label class="form-label fw-semibold mb-1 text-muted small text-uppercase" style="letter-spacing: 0.04em;">Current Thumbnail</label>
                        <div class="card card-sm border bg-surface-secondary flex-fill" style="border-radius: var(--tblr-border-radius, .375rem);">
                            <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2 h-100">
                                <div class="d-flex align-items-center gap-2 overflow-hidden flex-fill min-w-0">
                                    <div class="avatar rounded border overflow-hidden flex-shrink-0" style="width: 48px; height: 34px; background-size: cover; background-position: center; background-image: url('{{ $existingThumbUrl }}');">
                                    </div>
                                    <div class="overflow-hidden min-w-0">
                                        <div class="fw-medium text-reset text-truncate small mb-0" title="{{ $existingThumbName }}">
                                            {{ $existingThumbName }}
                                        </div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            <span>{{ ($existingThumbName === 'YouTube Thumbnail' || (isset($content) && $content->type === 'YouTube')) ? 'YouTube Image' : 'JPEG Image' }}</span>
                                            @if ($existingThumbSizeFormatted)
                                                <span class="mx-1">•</span>
                                                <span>{{ $existingThumbSizeFormatted }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1 ms-auto flex-shrink-0">
                                    <a href="{{ $existingThumbUrl }}" class="btn btn-outline-primary btn-sm px-2 py-1" download="{{ $existingThumbName ?? 'thumbnail.jpg' }}" target="_blank" title="Download Thumbnail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                            <path d="M7 11l5 5l5 -5" />
                                            <path d="M12 4l0 12" />
                                        </svg>
                                        <span class="d-none d-sm-inline ms-1">Download</span>
                                    </a>
                                    @if (!$isLocked)
                                    <a href="#" class="btn btn-outline-danger btn-sm px-2 py-1 btn-remove-thumb" data-url="{{ (isset($content) && !empty($content->slug)) ? route('contentdelete.deletethumbajax', $content->slug) : '' }}" title="Remove Thumbnail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M4 7l16 0" />
                                            <path d="M10 11l0 6" />
                                            <path d="M14 11l0 6" />
                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                        </svg>
                                        <span class="d-none d-sm-inline ms-1">Remove</span>
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <label class="form-label fw-semibold mb-1" id="label-thumbnail-file">
                    {{ ($mode === 'edit' && $hasExistingThumb) ? 'Replace Thumbnail' : 'Thumbnail' }}
                </label>
                <div class="dropzone compact-dropzone {{ $isLocked ? 'is-locked' : '' }}" id="thumbnail-dropzone">
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
                        <span class="text-muted small">{{ $isLocked ? '(Upload locked while published)' : 'or drop file here' }}</span>
                    </div>
                    <div class="fallback"></div>
                </div>
                <small class="text-muted d-block mt-1" id="thumbnail-help-text" style="font-size: 11px;">Format: JPG, JPEG, PNG, WEBP — maks. 1 file, 1 MB</small>
            </div>
        </div>
    </div>
</div>

