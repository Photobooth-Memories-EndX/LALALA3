@php
    $certificateType = old('certificate_type', 'content');
    $minimumProgress = old('minimum_progress', 100);
    $minimumScore = old('minimum_score', 80);
    $issuerName = old('issuer_name', 'OWL Training Management');
    $institution = old('institution', 'PT KRAKATAU POSCO');
    $issuedDate = old('issued_date');
    $expiredDate = old('expired_date');
    $isAssessmentRequired = old('is_assessment_required', false);
    $isActive = old('is_active', 1);
    $useDefaultTemplate = old('use_default_template', true);
@endphp

<div class="form-group mb-3">
    <label class="form-label">Certificate Type</label>
    <select name="certificate_type" class="form-control certificate-type-select" required>
        <option value="content" {{ $certificateType === 'content' ? 'selected' : '' }}>Content</option>
        <option value="playlist" {{ $certificateType === 'playlist' ? 'selected' : '' }}>Playlist</option>
        <option value="global" {{ $certificateType === 'global' ? 'selected' : '' }}>Global</option>
        <option value="training_offline" {{ in_array($certificateType, ['training_offline', 'offline']) ? 'selected' : '' }}>Training Offline</option>
    </select>
</div>

<div class="form-group mb-3">
    <label class="form-label certificate-item-label">Target Name</label>
    <select name="item_id"
            class="form-control certificate-item-select"
            data-selected-content=""
            data-selected-trainingplan=""
            data-selected-offline=""
            data-selected-assessment=""
            required>
        <option value="">Pilih data</option>
    </select>
</div>

<div class="certificate-playlist-contents alert alert-secondary mb-3" style="display:none;">
    <strong>Playlist Content</strong>
    <ol class="certificate-playlist-content-list mb-0 mt-2"></ol>
</div>

<div class="form-group mb-3">
    <label class="custom-control custom-checkbox">
        <input type="checkbox" name="is_assessment_required" value="1"
               class="custom-control-input certificate-assessment-required"
               {{ $isAssessmentRequired ? 'checked' : '' }}>
        <span class="custom-control-label">Wajib menggunakan assessment?</span>
    </label>
</div>

<div class="certificate-assessment-fields">
    <div class="form-group mb-3">
        <label class="form-label">Assessment</label>
        <select name="assessment_id" class="form-control certificate-assessment-select" data-selected="">
            <option value="">Select Assessment</option>
        </select>
        <small class="text-muted">The score is automatically derived from the user's best final assessment result.</small>
    </div>

    <div class="alert alert-info certificate-info-alert mb-3">
        Users must complete the assessment and achieve the minimum score in order for the certificate to be generated.
    </div>

    <div class="form-group mb-3">
        <label class="form-label">Minimum Assessment Score</label>
        <input type="number" name="minimum_score" class="form-control certificate-minimum-score"
               min="0" max="100" value="{{ $minimumScore }}">
    </div>
</div>

<div class="form-group mb-3 certificate-progress-field">
    <label class="form-label">Minimum Learning Progress</label>
    <input type="number" name="minimum_progress" class="form-control"
           min="0" max="100" value="{{ $minimumProgress }}" required>
</div>

<div class="form-group mb-3">
    <label class="form-label">Issuer Name</label>
    <select name="issuer_name" class="form-control" required>
        @foreach(['OWL Training Management', 'PT Krakatau Posco', 'HR Training Management'] as $issuer)
            <option value="{{ $issuer }}" {{ $issuerName === $issuer ? 'selected' : '' }}>{{ $issuer }}</option>
        @endforeach
    </select>
</div>

<div class="form-group mb-3">
    <label class="custom-control custom-checkbox">
        <input type="checkbox" name="use_default_template" value="1"
               class="custom-control-input certificate-default-template"
               {{ $useDefaultTemplate ? 'checked' : '' }}>
        <span class="custom-control-label">Use System Default Certificate</span>
    </label>
</div>

<div class="certificate-custom-template-fields mb-3">
    <div class="certificate-current-template-info mb-2" style="display:none;">
        <div class="alert alert-info py-2 d-flex align-items-center mb-0">
            <i class="ti ti-photo-check me-2"></i>
            <span class="flex-grow-1">Custom template sudah ada. Upload baru hanya jika ingin mengganti.</span>
            <a href="#" class="certificate-preview-custom-link ms-2 text-info" target="_blank">
                <i class="ti ti-external-link"></i> Preview
            </a>
        </div>
    </div>

    <div class="form-group mb-3">
        <label class="form-label">
            Upload Custom Certificate Template
        </label>

        <input
            type="file"
            name="custom_background"
            class="form-control"
            accept=".jpg,.jpeg,.png,.webp">

        <small class="form-text text-muted">
            Portrait 848 × 1200 px disarankan. Format: jpg, jpeg, png, webp.
        </small>
        <br>
        <small class="text-muted">
            Maksimal ukuran file 2 MB.
        </small>
    </div>
</div>

<div class="hr-text text-uppercase text-muted my-3 fw-bold">Certificate Information</div>

<div class="form-group mb-3">
    <label class="form-label">Certificate Institution</label>
    <div class="input-group">
        <input type="text"
               name="institution"
               class="form-control certificate-institution-input"
               placeholder="PT KRAKATAU POSCO"
               value="{{ $institution }}">
        <button type="button" class="btn btn-outline-secondary btn-default-institution">Default</button>
    </div>
    <small class="form-text text-muted">Klik Default untuk mengisi 'PT KRAKATAU POSCO' atau masukkan instansi lain secara manual.</small>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label class="form-label">Certificate Issued Date</label>
            <input type="date"
                   name="issued_date"
                   class="form-control certificate-issued-date"
                   value="{{ $issuedDate }}">
            <small class="form-text text-muted">Format: DD-MM-YYYY (opsional).</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label class="form-label">Certificate Expired Date</label>
            <input type="date"
                   name="expired_date"
                   class="form-control certificate-expired-date"
                   value="{{ $expiredDate }}">
            <small class="form-text text-muted">Format: DD-MM-YYYY (opsional).</small>
        </div>
    </div>
</div>

<div class="form-group mb-3">
    <label class="form-label">Certificate Status</label>
    <select name="is_active" class="form-control certificate-status-select">
        <option value="1" {{ (int) $isActive === 1 ? 'selected' : '' }}>ON</option>
        <option value="0" {{ (int) $isActive === 0 ? 'selected' : '' }}>OFF</option>
    </select>
    <small class="form-text text-muted">Certificate Available: ON = YES, OFF = NO.</small>
</div>
