@once
    @push('styles')
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
        <style>
            .hero-crop-modal .modal-content {
                overflow: hidden;
                border: 1px solid rgba(148, 163, 184, .22);
                border-radius: 20px;
                box-shadow: 0 28px 80px rgba(15, 23, 42, .28);
            }

            .hero-crop-modal .modal-header,
            .hero-crop-modal .modal-footer {
                padding: 18px 22px;
            }

            .hero-crop-stage {
                min-height: 360px;
                max-height: 62vh;
                overflow: hidden;
                border-radius: 14px;
                background:
                    linear-gradient(45deg, rgba(148, 163, 184, .12) 25%, transparent 25%),
                    linear-gradient(-45deg, rgba(148, 163, 184, .12) 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, rgba(148, 163, 184, .12) 75%),
                    linear-gradient(-45deg, transparent 75%, rgba(148, 163, 184, .12) 75%);
                background-position: 0 0, 0 8px, 8px -8px, -8px 0;
                background-size: 16px 16px;
            }

            .hero-crop-stage img {
                display: block;
                max-width: 100%;
            }

            .hero-crop-preview {
                position: relative;
                width: 100%;
                aspect-ratio: var(--hero-crop-aspect-ratio);
                overflow: hidden;
                border: 1px solid rgba(148, 163, 184, .24);
                border-radius: 22px;
                background: #53565A;
            }

            .hero-crop-preview::after {
                content: "";
                position: absolute;
                inset: 0;
                pointer-events: none;
                background: linear-gradient(
                    90deg,
                    rgba(3, 59, 92, .32) 0%,
                    rgba(5, 80, 125, .26) 48%,
                    rgba(105, 179, 231, .18) 100%
                );
            }

            .hero-crop-zoom {
                accent-color: #05507d;
            }

            .hero-crop-modal .btn-primary {
                border-color: #05507d;
                background: #05507d;
                color: #fff;
            }

            .hero-crop-modal .btn-primary:hover,
            .hero-crop-modal .btn-primary:focus {
                border-color: #6AAED6;
                background: #6AAED6;
                color: #05507D;
            }

            [data-bs-theme="dark"] .hero-crop-modal .modal-content,
            body.dark-mode .hero-crop-modal .modal-content {
                background: #53565A;
                color: #D9D9D6;
            }

            @media (max-width: 991.98px) {
                .hero-crop-preview::after {
                    background: linear-gradient(
                        90deg,
                        rgba(3, 59, 92, .38) 0%,
                        rgba(5, 80, 125, .32) 48%,
                        rgba(105, 179, 231, .24) 100%
                    );
                }
            }

            @media (max-width: 767.98px) {
                .hero-crop-stage {
                    min-height: 280px;
                }

                .hero-crop-preview::after {
                    background: linear-gradient(
                        90deg,
                        rgba(3, 59, 92, .44) 0%,
                        rgba(5, 80, 125, .38) 48%,
                        rgba(105, 179, 231, .30) 100%
                    );
                }
            }
        </style>
    @endpush

    <div class="modal fade hero-crop-modal"
         id="heroBackgroundCropModal"
         tabindex="-1"
         role="dialog"
         aria-labelledby="heroBackgroundCropTitle"
         aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h3 class="modal-title mb-1" id="heroBackgroundCropTitle">Crop Hero Background</h3>
                        <div class="text-muted small">
                            Drag the image and adjust zoom. The fixed crop frame matches the
                            <span id="heroCropDimensions">1600 × 320 Hero</span> design.
                        </div>
                    </div>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            data-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body px-4 py-2">
                    <div class="row g-4">
                        <div class="col-lg-9">
                            <div class="hero-crop-stage">
                                <img id="heroCropImage" src="" alt="Background crop area">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-semibold" for="heroCropZoom">Preview</label>
                            <div class="hero-crop-preview mb-4" id="heroCropPreview"></div>

                            <label class="form-label fw-semibold" for="heroCropZoom">Zoom</label>
                            <input type="range"
                                   class="form-range hero-crop-zoom"
                                   id="heroCropZoom"
                                   min="0.1"
                                   max="3"
                                   step="0.01"
                                   value="1">

                            <button type="button" class="btn btn-outline-secondary w-100 mt-3" id="heroCropReset">
                                <i class="ti ti-refresh me-1"></i>
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button"
                            class="btn btn-link text-muted"
                            data-bs-dismiss="modal"
                            data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="heroCropApply">
                        <i class="ti ti-crop me-1"></i>
                        Save Background
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    @endpush
@endonce
