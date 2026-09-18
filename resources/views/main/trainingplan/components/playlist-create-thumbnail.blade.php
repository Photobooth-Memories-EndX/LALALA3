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
        <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">2</span>
        <h3 class="card-title mb-0">Thumbnail &amp; Live Preview</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Left Column: Upload Control --}}
            <div class="col-md-6 col-12 d-flex flex-column justify-content-between">
                <div class="mb-1">
                    <label class="form-label fw-semibold mb-1" id="label-thumbnail-file">
                        Upload Thumbnail
                    </label>
                    <input type="file"
                           name="thumbnail"
                           id="playlist_thumbnail_input"
                           class="form-control @error('thumbnail') is-invalid @enderror"
                           accept="image/jpg,image/jpeg,image/png,image/webp">
                    <small class="form-hint">
                        Formats: JPG, PNG, WEBP — max 2 MB.
                    </small>
                    @error('thumbnail')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <div class="mt-3" id="remove-selected-thumb-wrapper" style="display: none;">
                        <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" id="btn-remove-selected-thumb" title="Remove selected image">
                            <i class="ti ti-trash"></i>
                            <span>Remove Selected Image</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right Column: Live Preview (16:9) & Lightbox --}}
            <div class="col-md-6 col-12">
                <div class="owl-pv-label">
                    <i class="ti ti-photo"></i>
                    <span>Thumbnail Preview (16:9)</span>
                </div>
                <div id="playlistThumbnailPreview" class="content-preview-box">
                    <div class="content-preview-empty" id="playlistThumbnailEmpty">
                        <i class="ti ti-photo" style="font-size: 32px; opacity: .4;"></i>
                        <span>No thumbnail selected</span>
                    </div>
                    <a data-fslightbox="playlist-thumb" href="" id="playlistThumbnailLink" class="w-100 h-100 align-items-center justify-content-center" style="display: none;" title="Click to view full image in lightbox">
                        <img src="" class="content-preview-image" id="playlistThumbnailImg" alt="Thumbnail preview">
                    </a>
                </div>
                <small class="text-muted d-block mt-1" style="font-size: 11px;">
                    Click the image to view full preview (lightbox). Live preview updates automatically when a file is chosen.
                </small>
            </div>
        </div>
    </div>
</div>