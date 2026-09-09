@php
    $trainingplan = $trainingplan ?? null;
    $stepNumber   = $stepNumber ?? 3;
    $isLocked     = $isLocked ?? ($trainingplan && $trainingplan->active === 'Active');

    $existingThumbUrl = $trainingplan?->thumbnail_url;
    $hasExistingThumb = !empty($existingThumbUrl);
    $existingThumbName = $trainingplan?->thumbnail ? basename($trainingplan->thumbnail) : 'thumbnail.jpg';
    $existingThumbSizeFormatted = null;

    if ($hasExistingThumb && $trainingplan?->thumbnail) {
        $localPath = public_path('storage/' . $trainingplan->thumbnail);
        if (file_exists($localPath)) {
            $bytes = filesize($localPath);
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
.owl-pv-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--tblr-muted, #6c757d);
    margin-bottom: 6px;
}
.content-preview-box {
    width: 100%;
    aspect-ratio: 16 / 9;
    border: 1px solid var(--tblr-border-color, rgba(98,105,118,.2));
    border-radius: 10px;
    background: var(--tblr-bg-surface-secondary, #f8fafc);
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.content-preview-empty {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #64748b;
    text-align: center;
    padding: 12px;
}
.content-preview-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    display: block;
    cursor: pointer;
    transition: transform 0.2s ease;
}
.content-preview-image:hover {
    transform: scale(1.02);
}
[data-bs-theme="dark"] .content-preview-box,
body.dark-mode .content-preview-box {
    background: #1f2937;
    border-color: rgba(255,255,255,.14);
}
[data-bs-theme="dark"] .content-preview-empty,
body.dark-mode .content-preview-empty {
    color: #9ca3af;
}
</style>
@endonce

<div class="card owl-main-card mb-3">
    <div class="card-header d-flex align-items-center">
        <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">{{ $stepNumber }}</span>
        <h3 class="card-title mb-0">Thumbnail &amp; Live Preview</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Left Column: Current Thumbnail & Upload Control --}}
            <div class="col-md-6 col-12 d-flex flex-column">
                <input type="hidden" name="remove_thumbnail" id="remove_thumbnail" value="0">

                @if ($hasExistingThumb)
                    <div class="mb-3 flex-grow-1 d-flex flex-column" id="current-thumbnail-wrapper">
                        <label class="form-label fw-semibold mb-1 text-muted small text-uppercase" style="letter-spacing: 0.04em;">Current Thumbnail</label>
                        <div class="card card-sm border bg-surface-secondary flex-fill" style="border-radius: var(--tblr-border-radius, .375rem);">
                            <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2 h-100">
                                <div class="d-flex align-items-center gap-2 overflow-hidden flex-fill min-w-0">
                                    <a data-fslightbox="playlist-thumb" href="{{ $existingThumbUrl }}" class="avatar rounded border overflow-hidden flex-shrink-0" style="width: 48px; height: 34px; background-size: cover; background-position: center; background-image: url('{{ $existingThumbUrl }}');" title="Click to view full image">
                                    </a>
                                    <div class="overflow-hidden min-w-0">
                                        <div class="fw-medium text-reset text-truncate small mb-0" title="{{ $existingThumbName }}">
                                            {{ $existingThumbName }}
                                        </div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            <span>Thumbnail Image</span>
                                            @if ($existingThumbSizeFormatted)
                                                <span class="mx-1">•</span>
                                                <span>{{ $existingThumbSizeFormatted }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1 ms-auto flex-shrink-0">
                                    <a href="{{ $existingThumbUrl }}" class="btn btn-outline-primary btn-sm px-2 py-1 d-inline-flex align-items-center gap-1" download="{{ $existingThumbName }}" target="_blank" title="Download Thumbnail">
                                        <i class="ti ti-download"></i>
                                        <span class="d-none d-sm-inline">Download</span>
                                    </a>
                                    @if (!$isLocked)
                                        <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 btn-remove-playlist-thumb d-inline-flex align-items-center gap-1" id="btn-remove-playlist-thumb" title="Remove Thumbnail">
                                            <i class="ti ti-trash"></i>
                                            <span class="d-none d-sm-inline">Remove</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mb-1">
                    <label class="form-label fw-semibold mb-1" id="label-thumbnail-file">
                        {{ ($hasExistingThumb) ? 'Replace Thumbnail' : 'Upload Thumbnail' }}
                    </label>
                    <input type="file"
                           name="thumbnail"
                           id="playlist_thumbnail_input"
                           class="form-control @error('thumbnail') is-invalid @enderror"
                           accept="image/jpg,image/jpeg,image/png,image/webp"
                           {{ $isLocked ? 'disabled' : '' }}>
                    <small class="form-hint">
                        Formats: JPG, PNG, WEBP — max 2 MB. {{ $isLocked ? '(Upload locked while published)' : '' }}
                    </small>
                    @error('thumbnail')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Right Column: Live Preview (16:9) & Lightbox --}}
            <div class="col-md-6 col-12">
                <div class="owl-pv-label">
                    <i class="ti ti-photo"></i>
                    <span>Thumbnail Preview (16:9)</span>
                </div>
                <div id="playlistThumbnailPreview" class="content-preview-box">
                    @if ($hasExistingThumb)
                        <a data-fslightbox="playlist-thumb" href="{{ $existingThumbUrl }}" id="playlistThumbnailLink" class="w-100 h-100 d-flex align-items-center justify-content-center" title="Click to view full image in lightbox">
                            <img src="{{ $existingThumbUrl }}" class="content-preview-image" id="playlistThumbnailImg" alt="Thumbnail preview">
                        </a>
                        <div class="content-preview-empty" id="playlistThumbnailEmpty" style="display: none;">
                            <i class="ti ti-photo" style="font-size: 32px; opacity: .4;"></i>
                            <span>No thumbnail available</span>
                        </div>
                    @else
                        <div class="content-preview-empty" id="playlistThumbnailEmpty">
                            <i class="ti ti-photo" style="font-size: 32px; opacity: .4;"></i>
                            <span>No thumbnail available</span>
                        </div>
                        <a data-fslightbox="playlist-thumb" href="" id="playlistThumbnailLink" class="w-100 h-100 align-items-center justify-content-center" style="display: none;" title="Click to view full image in lightbox">
                            <img src="" class="content-preview-image" id="playlistThumbnailImg" alt="Thumbnail preview">
                        </a>
                    @endif
                </div>
                <small class="text-muted d-block mt-1" style="font-size: 11px;">
                    Click the image to view full preview (lightbox). Live preview updates automatically when a new file is chosen.
                </small>
            </div>
        </div>
    </div>
</div>
