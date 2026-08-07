@props([
    'id' => 'certificateHoverPreview',
])

@once
    <link rel="stylesheet" href="{{ asset('css/certificate-hover-preview.css') }}">
    <script src="{{ asset('js/certificate-hover-preview.js') }}"></script>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/certificate-hover-preview.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/certificate-hover-preview.js') }}"></script>
    @endpush
@endonce

<aside class="certificate-hover-preview" id="{{ $id }}" role="dialog" aria-hidden="true" aria-label="Certificate Hover Preview">
    <div class="certificate-hover-preview-header">
        <h3 class="certificate-hover-preview-title">Certificate Preview</h3>
        <button type="button" class="certificate-hover-preview-close" aria-label="Close certificate preview">
            <i class="ti ti-x" aria-hidden="true"></i>
        </button>
    </div>

    <div class="certificate-hover-preview-body">
        <iframe class="certificate-hover-preview-frame" title="Certificate PDF Document" src="about:blank"></iframe>
        <div class="certificate-hover-preview-state" aria-live="polite">
            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
            <span>Loading certificate preview...</span>
        </div>
    </div>

    <div class="certificate-hover-preview-footer">
        <a class="btn btn-primary certificate-hover-preview-action certificate-hover-preview-full-link" href="#" target="_blank" rel="noopener">
            <span>View Full Certificate</span>
            <i class="ti ti-arrow-right" aria-hidden="true"></i>
        </a>
        <a class="btn btn-outline-secondary certificate-hover-preview-action certificate-hover-preview-download-link" href="#" download>
            <i class="ti ti-download" aria-hidden="true"></i>
            <span>Download PDF</span>
        </a>
    </div>
</aside>
