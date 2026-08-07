@props([
    'id' => 'certificatePreviewModal',
])

@once
    @push('style')
        <link rel="stylesheet" href="{{ asset('css/certificate-preview-modal.css') }}">
    @endpush
    @push('script')
        <script src="{{ asset('js/certificate-preview-modal.js') }}"></script>
    @endpush
@endonce

<div class="certificate-preview-modal" id="{{ $id }}" role="dialog" aria-hidden="true" aria-label="Certificate Preview">
    <div class="certificate-preview-modal-header">
        <h3 class="certificate-preview-modal-title">Certificate Preview</h3>
        <button type="button" class="certificate-preview-modal-close" aria-label="Close certificate preview">
            <i class="ti ti-x" aria-hidden="true"></i>
        </button>
    </div>

    <div class="certificate-preview-modal-body">
        <iframe class="certificate-preview-native-frame" title="Certificate PDF Document" src="about:blank"></iframe>
        <div class="certificate-preview-modal-state" aria-live="polite">
            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
            <span>Loading certificate preview...</span>
        </div>
    </div>

    <div class="certificate-preview-modal-footer">
        <a class="btn btn-primary certificate-preview-modal-action certificate-preview-full-link" href="#" target="_blank" rel="noopener">
            <span>View Full Certificate</span>
            <i class="ti ti-arrow-right" aria-hidden="true"></i>
        </a>
        <a class="btn btn-outline-secondary certificate-preview-modal-action certificate-preview-download-link" href="#" download>
            <i class="ti ti-download" aria-hidden="true"></i>
            <span>Download PDF</span>
        </a>
    </div>
</div>
