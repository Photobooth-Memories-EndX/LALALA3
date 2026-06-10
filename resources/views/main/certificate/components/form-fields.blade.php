@php
    $isEdit = isset($setting);

    $certificateType = old('certificate_type', $setting->certificate_type ?? 'content');
    $minimumProgress = old('minimum_progress', $setting->minimum_progress ?? 100);
    $minimumScore = old('minimum_score', $setting->minimum_score ?? 80);
    $issuerName = old('issuer_name', $setting->issuer_name ?? 'OWL Training Management');
    $isAssessmentRequired = old('is_assessment_required', $setting->is_assessment_required ?? false);
    $isActive = old('is_active', $setting->is_active ?? 1);
    $useDefaultTemplate = old('use_default_template', $setting->use_default_template ?? true);
@endphp

<div class="form-group">
    <label class="form-label">Certificate Type</label>

    <select name="certificate_type"
            class="form-control certificate-type-select"
            required>
        <option value="content" {{ $certificateType === 'content' ? 'selected' : '' }}>
            Content
        </option>
        <option value="playlist" {{ $certificateType === 'playlist' ? 'selected' : '' }}>
            Playlist
        </option>
        <option value="global" {{ $certificateType === 'global' ? 'selected' : '' }}>
            Global
        </option>
    </select>
</div>

<div class="form-group">
    <label class="form-label certificate-item-label">
        Nama Content / Playlist / Global
    </label>

    <select name="item_id"
            class="form-control certificate-item-select"
            data-selected-content="{{ $setting->content_id ?? '' }}"
            data-selected-trainingplan="{{ $setting->trainingplan_id ?? '' }}"
            data-selected-assessment="{{ $setting->assessment_id ?? '' }}"
            required>
        <option value="">Pilih data</option>
    </select>
</div>

<div class="form-group">
    <label class="form-label">Assessment</label>

    <select name="assessment_id"
            class="form-control certificate-assessment-select"
            data-selected="{{ $setting->assessment_id ?? '' }}">
        <option value="">Assessment Optional</option>
    </select>
</div>

<div class="form-group">
    <label class="custom-control custom-checkbox">
        <input type="checkbox"
               name="is_assessment_required"
               value="1"
               class="custom-control-input certificate-assessment-required"
               {{ $isAssessmentRequired ? 'checked' : '' }}>

        <span class="custom-control-label">
            Wajib menyelesaikan assessment?
        </span>
    </label>
</div>

<div class="alert alert-info certificate-info-alert">
    Untuk mendapatkan sertifikat, user harus menyelesaikan learning progress dan lulus assessment sesuai minimum score.
</div>

<div class="form-group">
    <label class="form-label">Minimum Learning Progress</label>

    <input type="number"
           name="minimum_progress"
           class="form-control"
           min="0"
           max="100"
           value="{{ $minimumProgress }}"
           required>
</div>

<div class="form-group">
    <label class="form-label">Minimum Assessment Score</label>

    <input type="number"
           name="minimum_score"
           class="form-control certificate-minimum-score"
           min="0"
           max="100"
           value="{{ $minimumScore }}">
</div>

<div class="form-group">
    <label class="form-label">Issuer Name</label>

    <select name="issuer_name" class="form-control" required>
        <option value="OWL Training Management" {{ $issuerName === 'OWL Training Management' ? 'selected' : '' }}>
            OWL Training Management
        </option>
        <option value="PT Krakatau Posco" {{ $issuerName === 'PT Krakatau Posco' ? 'selected' : '' }}>
            PT Krakatau Posco
        </option>
        <option value="HR Training Management" {{ $issuerName === 'HR Training Management' ? 'selected' : '' }}>
            HR Training Management
        </option>
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

<div class="certificate-custom-template-fields">
<div class="form-group">
    <label class="form-label">Logo</label>
    <input type="file"
           name="issuer_logo"
           class="form-control"
           accept="image/*">

    @if($isEdit && !empty($setting->issuer_logo))
        <small class="text-muted d-block mt-1">
            Current: {{ $setting->issuer_logo }}
        </small>
    @endif
</div>

<div class="form-group">
    <label class="form-label">Custom Template / Design</label>

    <input type="file"
           name="certificate_template"
           class="form-control"
           accept=".jpg,.jpeg,.png,.webp,.pdf">

    @if($isEdit && !empty($setting->certificate_template))
        <small class="text-muted d-block mt-1">
            Current: {{ $setting->certificate_template }}
        </small>
    @endif
</div>

<div class="form-group">
    <label class="form-label">Certificate Status</label>

    <select name="is_active" class="form-control">
        <option value="1" {{ (int) $isActive === 1 ? 'selected' : '' }}>
            ON
        </option>
        <option value="0" {{ (int) $isActive === 0 ? 'selected' : '' }}>
            OFF
        </option>
    </select>
</div>

<div class="form-group">
    <label class="form-label">Custom Background</label>
    <input type="file" name="background_image" class="form-control" accept="image/*">
    @if($isEdit && !empty($setting->background_image))
        <small class="text-muted d-block mt-1">Current: {{ $setting->background_image }}</small>
    @endif
</div>
</div>
