@php
    $certificateType = old('certificate_type', 'content');
    $minimumProgress = old('minimum_progress', 100);
    $minimumScore = old('minimum_score', 80);
    $issuerName = old('issuer_name', 'OWL Training Management');
    $isAssessmentRequired = old('is_assessment_required', false);
    $isActive = old('is_active', 1);
    $useDefaultTemplate = old('use_default_template', true);
@endphp

<div class="form-group">
    <label class="form-label">Certificate Type</label>
    <select name="certificate_type" class="form-control certificate-type-select" required>
        <option value="content" {{ $certificateType === 'content' ? 'selected' : '' }}>Content</option>
        <option value="playlist" {{ $certificateType === 'playlist' ? 'selected' : '' }}>Playlist</option>
        <option value="global" {{ $certificateType === 'global' ? 'selected' : '' }}>Global</option>
    </select>
</div>

<div class="form-group">
    <label class="form-label certificate-item-label">Target Name</label>
    <select name="item_id"
            class="form-control certificate-item-select"
            data-selected-content=""
            data-selected-trainingplan=""
            data-selected-assessment=""
            required>
        <option value="">Pilih data</option>
    </select>
</div>

<div class="certificate-playlist-contents alert alert-secondary" style="display:none;">
    <strong>Playlist Content</strong>
    <ol class="certificate-playlist-content-list mb-0 mt-2"></ol>
</div>

<div class="form-group">
    <label class="custom-control custom-checkbox">
        <input type="checkbox" name="is_assessment_required" value="1"
               class="custom-control-input certificate-assessment-required"
               {{ $isAssessmentRequired ? 'checked' : '' }}>
        <span class="custom-control-label">Wajib menggunakan assessment?</span>
    </label>
</div>

<div class="certificate-assessment-fields">
    <div class="form-group">
        <label class="form-label">Assessment</label>
        <select name="assessment_id" class="form-control certificate-assessment-select" data-selected="">
            <option value="">Pilih assessment</option>
        </select>
        <small class="text-muted">Score diambil otomatis dari assessment result final terbaik milik user.</small>
    </div>

    <div class="alert alert-info certificate-info-alert">
        User harus menyelesaikan assessment dan mencapai minimum score agar sertifikat bisa digenerate.
    </div>

    <div class="form-group">
        <label class="form-label">Minimum Assessment Score</label>
        <input type="number" name="minimum_score" class="form-control certificate-minimum-score"
               min="0" max="100" value="{{ $minimumScore }}">
    </div>
</div>

<div class="form-group certificate-progress-field">
    <label class="form-label">Minimum Learning Progress</label>
    <input type="number" name="minimum_progress" class="form-control"
           min="0" max="100" value="{{ $minimumProgress }}" required>
</div>

<div class="form-group">
    <label class="form-label">Issuer Name</label>
    <select name="issuer_name" class="form-control" required>
        @foreach(['OWL Training Management', 'PT Krakatau Posco', 'HR Training Management'] as $issuer)
            <option value="{{ $issuer }}" {{ $issuerName === $issuer ? 'selected' : '' }}>{{ $issuer }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label class="custom-control custom-checkbox">
        <input type="checkbox" name="use_default_template" value="1"
               class="custom-control-input certificate-default-template"
               {{ $useDefaultTemplate ? 'checked' : '' }}>
        <span class="custom-control-label">Use System Default Certificate</span>
    </label>
</div>

{{-- <div class="certificate-custom-template-fields">
    <div class="form-group">
        <label class="form-label">Masukan Photo untuk Preview</label>
        <input type="file" name="certificate_template" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
    </div>
    <div class="form-group">
        <label class="form-label">Custom Background</label>
        <input type="file" name="custom_background" class="form-control" accept="image/*">
    </div>
</div> --}}

<div class="certificate-custom-template-fields">

    <div class="certificate-current-template-info mb-2" style="display:none;">
        <div class="alert alert-info py-2 d-flex align-items-center mb-0">
            <i class="ti ti-photo-check me-2"></i>
            <span class="flex-grow-1">Custom template sudah ada. Upload baru hanya jika ingin mengganti.</span>
            <a href="#" class="certificate-preview-custom-link ms-2 text-info" target="_blank">
                <i class="ti ti-external-link"></i> Preview
            </a>
        </div>
    </div>

    <div class="form-group">
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

<div class="form-group">
    <label class="form-label">Certificate Status</label>
    <select name="is_active" class="form-control">
        <option value="1" {{ (int) $isActive === 1 ? 'selected' : '' }}>ON</option>
        <option value="0" {{ (int) $isActive === 0 ? 'selected' : '' }}>OFF</option>
    </select>
</div>
