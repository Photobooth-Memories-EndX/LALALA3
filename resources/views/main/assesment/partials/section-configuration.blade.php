@php
    $isEdit = isset($assesment) && $assesment;
    $type = old('type', $isEdit ? $assesment->type : '');
    $contentFor = old('assasment_content_for', $isEdit ? ($assesment->assasment_content_for ?? '') : '');
    $titleVal = old('title', $isEdit ? $assesment->title : '');
    $contentId = old('content_id', $isEdit ? $assesment->content_id : '');
    $trainingplanId = old('trainingplan_id', $isEdit ? $assesment->trainingplan_id : '');
    $uniqueCode = old('unique_code', $isEdit ? $assesment->unique_code : '');
    $passingGrade = old('passing_grade', $isEdit ? $assesment->passing_grade : '');
    $playlistList = $trainingplans ?? $trainingplan ?? \App\Models\TrainingPlan::latest()->get();
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

    {{-- Row 1: Title (Full Width) --}}
    <div class="mb-3">
        <label class="form-label required">Title</label>
        <x-input type="text" name="title" id="assessment_title_input" placeholder="Enter title" value="{{ $titleVal }}" oninput="updatePageTitle(this.value);" />
    </div>

    {{-- Row 2: Assessment Type (Left) & Assessment Content For (Right) --}}
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

        <div class="col-md-6 {{ ($type == 'content') ? '' : 'd-none' }}" id="content_for_wrapper">
            <label class="form-label required" for="assasment_content_for">Assessment Content For</label>
            <x-select name="assasment_content_for" id="assasment_content_for" onchange="updateAssessmentTypeConfiguration();">
                <option value="">Select Assessment Content For</option>
                <option value="content" {{ ($contentFor === 'content') ? 'selected' : '' }}>Content</option>
                <option value="playlist" {{ ($contentFor === 'playlist') ? 'selected' : '' }}>Playlist</option>
            </x-select>
            {{-- <div class="form-text text-muted small mt-1 {{ empty($contentFor) ? 'd-none' : '' }}" id="assasment_content_for_hint">
                @if ($contentFor === 'content')
                    Assessment akan digunakan sebagai Direct Content dan tidak harus terhubung ke Playlist.
                @elseif ($contentFor === 'playlist')
                    Assessment akan digunakan pada materi di dalam Playlist. Playlist maupun Learning Content dapat dipilih atau dikosongkan.
                @endif
            </div> --}}
        </div>
    </div>

    {{-- Conditional: Playlist / Training Plan (Parent when assasment_content_for = playlist, or for type = trainingplan) --}}
    <div class="mb-3 {{ ($type == 'trainingplan' || ($type == 'content' && $contentFor == 'playlist')) ? '' : 'd-none' }}" id="trainingplan_id_wrapper">
        <label class="form-label {{ ($type == 'trainingplan') ? 'required' : '' }}" id="trainingplan_id_label">{{ ($type == 'content' && $contentFor == 'playlist') ? 'Playlist (Optional)' : 'Training Plan' }}</label>
        <select name="trainingplan_id" id="trainingplan_id" class="form-select form-control select2" data-placeholder="— Select Playlist (Optional / None) —">
            <option value="">— Select Playlist (Optional / None) —</option>
            @foreach ($playlistList as $item)
                <option value="{{ $item->id }}" {{ ((string) $trainingplanId === (string) $item->id) ? 'selected' : '' }}>{{ $item->title }}</option>
            @endforeach
        </select>
    </div>

    {{-- Conditional: Learning Content (Child when assasment_content_for = playlist, or direct when assasment_content_for = content) --}}
    <div class="mb-3 {{ ($type == 'content' && in_array($contentFor, ['content', 'playlist'])) ? '' : 'd-none' }}" id="content_id_wrapper">
        <label class="form-label" id="content_id_label">Learning Content (Optional)</label>
        @php
            $isPlaylistMode = ($type === 'content' && $contentFor === 'playlist');
            $activeContents = ($isPlaylistMode && !empty($trainingplanId)) ? ($playlistContents ?? collect()) : $contents;
        @endphp
        <div class="position-relative">
            <select name="content_id" id="content_id" class="form-select form-control select2" data-placeholder="— Select Learning Content (Optional / None) —">
                <option value="">— Select Learning Content (Optional / None) —</option>
                @foreach ($activeContents as $item)
                    <option value="{{ $item->id }}" {{ ((string) $contentId === (string) $item->id) ? 'selected' : '' }}>{{ $item->title }}</option>
                @endforeach
            </select>
            <div id="content_id_loading" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-4 d-none" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <script>
        window.__globalDirectContents = @json($contents->map(function($c) {
            return ['id' => $c->id, 'title' => $c->title];
        }));
    </script>

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
