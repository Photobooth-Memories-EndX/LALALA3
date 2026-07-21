@once
    @push('styles')
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
        <style>
            [data-hero-background] .compact-hero-upload,
            [data-hero-background] .compact-hero-save {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                min-width: 42px;
                padding: 0;
                border: 1px solid rgba(255, 255, 255, .28);
                border-radius: 50%;
                background: rgba(255, 255, 255, .18);
                color: #fff;
                box-shadow: 0 8px 24px rgba(5, 15, 35, .18);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                transition: background-color .2s ease, transform .2s ease, border-color .2s ease;
            }

            [data-hero-background] .compact-hero-upload:hover,
            [data-hero-background] .compact-hero-save:hover {
                color: #fff;
                background: rgba(255, 255, 255, .3);
                border-color: rgba(255, 255, 255, .5);
                transform: translateY(-1px);
            }

            [data-hero-background] .compact-hero-upload i,
            [data-hero-background] .compact-hero-save i {
                font-size: 20px;
                line-height: 1;
            }

            [data-hero-background] .compact-hero-save:disabled {
                cursor: wait;
                opacity: .9;
            }

            [data-hero-background] .compact-hero-save .spinner-border {
                width: 18px;
                height: 18px;
                border-width: 2px;
            }

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
                background: #0f172a;
            }

            .hero-crop-preview::after {
                content: "";
                position: absolute;
                inset: 0;
                pointer-events: none;
                background:
                    radial-gradient(circle at 82% 20%, rgba(255, 255, 255, .12), transparent 32%),
                    linear-gradient(90deg, rgba(8, 22, 52, .9), rgba(18, 58, 118, .74));
            }

            .hero-crop-zoom {
                accent-color: #2563eb;
            }

            [data-bs-theme="dark"] .hero-crop-modal .modal-content,
            body.dark-mode .hero-crop-modal .modal-content {
                background: #142238;
                color: #f8fafc;
            }

            @media (max-width: 767.98px) {
                [data-hero-background] .compact-hero-upload,
                [data-hero-background] .compact-hero-save {
                    width: 40px;
                    height: 40px;
                    min-width: 40px;
                }

                .hero-crop-stage {
                    min-height: 280px;
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
                            <span id="heroCropDimensions">Hero</span> design.
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
