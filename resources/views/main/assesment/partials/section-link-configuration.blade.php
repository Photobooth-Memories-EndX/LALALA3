<div class="owl-section-block mb-4">
    <div class="owl-section-header mb-3 pb-2 border-bottom">
        <div class="d-flex align-items-center">
            <span class="owl-section-number me-2">1</span>
            <h3 class="owl-section-title mb-0 fw-bold">Link Configuration</h3>
        </div>
        <div class="text-muted small ms-4 ps-1">
            Configure assessment link and access settings.
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label required">Link</label>
        <div class="input-icon">
            <span class="input-icon-addon">
                <i class="fas fa-link"></i>
            </span>
            <x-input type="text" name="slug" placeholder="Enter new link" value="{{ $assesment->slug }}" />
        </div>
        <small class="form-hint">No space and symbol allowed</small>
    </div>

    <div class="mb-3">
        <label class="form-label required">Batch No</label>
        <x-input type="number" name="batch" placeholder="Enter batch no" required value="{{ $assesment->batch }}" />
    </div>
</div>
