@php
    $isEdit = isset($assesment) && $assesment;
    $type = old('type', $isEdit ? $assesment->type : '');
    $contentFor = old('content_for', $isEdit ? ($assesment->content_for ?? 'content') : 'content');
    $titleVal = old('title', $isEdit ? $assesment->title : '');
    $contentId = old('content_id', $isEdit ? $assesment->content_id : '');
    $trainingplanId = old('trainingplan_id', $isEdit ? $assesment->trainingplan_id : '');
    $uniqueCode = old('unique_code', $isEdit ? $assesment->unique_code : '');
    $passingGrade = old('passing_grade', $isEdit ? $assesment->passing_grade : '');
@endphp

<div class="owl-section-block mb-4">
    <div class="owl-section-header mb-3 pb-2 border-bottom">
        <div class="d-flex align-items-center">
            <span class="owl-section-number me-2">1</span>
            <h3 class="owl-section-title mb-0 fw-bold">Assessment Configuration</h3>
        </div>
        <div class="text-muted small ms-4 ps-1">
            Basic assessment identity and scoring criteria.
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label required">Assessment Type</label>
            <x-select name="type" id="assessment_type" onchange="setRequired(this);">
                <option value="">-choose-</option>
                <option value="content" {{ ($type == 'content') ? 'selected' : '' }}>Assesment for content</option>
                <option value="trainingplan" {{ ($type == 'trainingplan') ? 'selected' : '' }}>Assesment for playlist</option>
                <option value="global" {{ ($type == 'global') ? 'selected' : '' }}>Global Assesment</option>
            </x-select>
        </div>

        <div class="col-md-6">
            <label class="form-label required">Title</label>
            <x-input type="text" name="title" id="assessment_title_input" placeholder="Enter title" value="{{ $titleVal }}" oninput="updatePageTitle(this.value);" />
        </div>
    </div>

    {{-- Conditional: Content For (Only for type = content) --}}
    <div class="mb-3 {{ ($type == 'content') ? '' : 'd-none' }}" id="content_for_wrapper">
        <label class="form-label required">Content For</label>
        <div class="d-flex gap-3">
            <label class="form-check form-check-inline mb-0">
                <input class="form-check-input content-for-radio" type="radio" name="content_for" id="content_for_content" value="content" {{ ($contentFor === 'content') ? 'checked' : '' }} onchange="updateAssessmentTypeConfiguration();">
                <span class="form-check-label">Content</span>
            </label>
            <label class="form-check form-check-inline mb-0">
                <input class="form-check-input content-for-radio" type="radio" name="content_for" id="content_for_playlist" value="playlist" {{ ($contentFor === 'playlist') ? 'checked' : '' }} onchange="updateAssessmentTypeConfiguration();">
                <span class="form-check-label">Playlist</span>
            </label>
        </div>
        <div class="form-text text-muted small mt-1">
            Pilih <strong>Content</strong> untuk ujian Direct Content, atau <strong>Playlist</strong> jika ujian ditampilkan khusus pada materi di Playlist.
        </div>
    </div>

    {{-- Conditional Selects --}}
    <div class="mb-3 {{ ($type == 'content') ? '' : 'd-none' }}" id="content_id_wrapper">
        <label class="form-label required">Learning Content</label>
        <x-select name="content_id" id="content_id">
            <option value="">-choose-</option>
            @foreach ($contents as $item)
                <option value="{{ $item->id }}" {{ ($contentId == $item->id) ? 'selected' : '' }}>{{ $item->title }}</option>
            @endforeach
        </x-select>
    </div>

    <div class="mb-3 {{ ($type == 'trainingplan' || ($type == 'content' && $contentFor == 'playlist')) ? '' : 'd-none' }}" id="trainingplan_id_wrapper">
        <label class="form-label required" id="trainingplan_id_label">{{ ($type == 'content' && $contentFor == 'playlist') ? 'Playlist' : 'Training Plan' }}</label>
        <x-select name="trainingplan_id" id="trainingplan_id">
            <option value="">-choose-</option>
            @foreach ($trainingplan as $item)
                <option value="{{ $item->id }}" {{ ($trainingplanId == $item->id) ? 'selected' : '' }}>{{ $item->title }}</option>
            @endforeach
        </x-select>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Unique Code (Optional)</label>
            <x-input type="text" name="unique_code" placeholder="Enter unique code" value="{{ $uniqueCode }}" />
        </div>

        <div class="col-md-6">
            <label class="form-label required">Passing Grade</label>
            <x-input type="number" name="passing_grade" placeholder="Enter passing grade" value="{{ $passingGrade }}" />
        </div>
    </div>
</div>
