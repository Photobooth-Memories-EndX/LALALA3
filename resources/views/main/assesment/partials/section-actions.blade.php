@php
    $isEdit = isset($assesment) && $assesment;
@endphp

<div class="owl-section-block pt-3 border-top">
    <div class="owl-section-header mb-3 pb-2 border-bottom">
        <div class="d-flex align-items-center">
            <span class="owl-section-number me-2">5</span>
            <h3 class="owl-section-title mb-0 fw-bold">Assessment Actions</h3>
        </div>
        <div class="text-muted small ms-4 ps-1">
            Save or cancel changes.
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('assesment.index') }}" class="btn btn-light px-4">
            Cancel
        </a>

        @if ($isEdit)
            <button type="button" class="btn btn-primary px-5" id="btn-submit-form-update-assessment">
                <i class="fas fa-save me-2"></i>
                Update Assessment
            </button>
        @else
            <x-button class="btn btn-primary px-5">
                <i class="fas fa-save me-2"></i>
                Create Assessment
            </x-button>
        @endif
    </div>
</div>
