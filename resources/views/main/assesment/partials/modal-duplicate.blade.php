@php
    $modalPlaylists = $trainingplans ?? \App\Models\TrainingPlan::latest()->get();
@endphp

<div class="modal fade" id="modalDuplicateAssessment" tabindex="-1" aria-labelledby="modalDuplicateAssessmentLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form id="formDuplicateAssessment" method="POST" action="" class="modal-content rounded-4 border-0 shadow">
            @csrf

            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold d-flex align-items-center" id="modalDuplicateAssessmentLabel">
                    <i class="fas fa-copy text-warning mr-2 me-2"></i>
                    Duplicate Assessment
                </h4>
                <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                <p class="text-muted mb-3">
                    Are you sure you want to duplicate this assessment?
                </p>

                <div class="p-3 bg-light rounded-3 border mb-3 duplicate-target-box">
                    <div class="text-muted small mb-1">Assessment Target:</div>
                    <div class="fw-bold text-dark duplicate-target-title" id="duplicateAssessmentTitle" style="word-break: break-word;">
                        -
                    </div>
                </div>

                {{-- Context selection: Only shown when type === 'content' --}}
                <div id="duplicateContextSection" class="d-none mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">Target Context</label>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target_context" id="duplicate_target_content" value="content" checked>
                            <label class="form-check-label text-dark fw-medium" for="duplicate_target_content">
                                Direct Content Assessment
                            </label>
                            <div class="text-muted small">Assessment akan digunakan pada halaman Direct Content.</div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target_context" id="duplicate_target_playlist" value="playlist">
                            <label class="form-check-label text-dark fw-medium" for="duplicate_target_playlist">
                                Playlist Content Assessment
                            </label>
                            <div class="text-muted small">Assessment akan diduplikasi khusus untuk materi di dalam Playlist tertentu.</div>
                        </div>
                    </div>

                    <div id="duplicatePlaylistSelectWrapper" class="d-none mb-2">
                        <label class="form-label fw-bold small required" for="duplicate_trainingplan_id">Select Playlist</label>
                        <select name="trainingplan_id" id="duplicate_trainingplan_id" class="form-select form-control">
                            <option value="">-- Choose Playlist --</option>
                            @foreach ($modalPlaylists as $pl)
                                <option value="{{ $pl->id }}">{{ $pl->title }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="duplicatePlaylistError">Pilih Playlist tujuan untuk ujian ini.</div>
                    </div>
                </div>

                <div class="text-muted small d-flex align-items-start">
                    <i class="fas fa-info-circle mr-2 me-2 text-info mt-1"></i>
                    <span>A new assessment copy will be created with its configuration and questions preserved. Participant attempts and unique code will not be copied.</span>
                </div>
            </div>

            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" id="btnCancelDuplicate">
                    Cancel
                </button>
                <button type="submit" class="btn btn-warning text-dark font-weight-semibold" id="btnSubmitDuplicate">
                    <i class="fas fa-copy mr-1 me-1"></i>
                    <span class="btn-text">Duplicate Assessment</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- Bulk Duplicate Assessment Modal                                           --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="modalBulkDuplicateAssessment" tabindex="-1" aria-labelledby="modalBulkDuplicateAssessmentLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold d-flex align-items-center" id="modalBulkDuplicateAssessmentLabel">
                    <i class="fas fa-copy text-primary mr-2 me-2"></i>
                    Bulk Duplicate Assessments
                </h4>
                <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                <p class="text-muted mb-3">
                    Duplicate the selected active Content Assessments with their assessment structure. Existing user results, progress, and history will not be copied.
                </p>

                {{-- Section 1: Selection Mode --}}
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">Selection Mode</label>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulk_selection_mode" id="bulk_mode_selected" value="selected" checked>
                            <label class="form-check-label text-dark fw-medium" for="bulk_mode_selected">
                                Selected Assessments (<span id="bulkSelectedCountBadge">0</span>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulk_selection_mode" id="bulk_mode_all" value="all_active">
                            <label class="form-check-label text-dark fw-medium" for="bulk_mode_all">
                                All Active Content Assessments ({{ $totalActiveContentCount ?? 39 }})
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Target Context --}}
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">Target Context</label>
                    <div class="d-flex flex-column gap-2 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulk_target_context" id="bulk_target_content" value="content" checked>
                            <label class="form-check-label text-dark fw-medium" for="bulk_target_content">
                                Direct Content
                            </label>
                            <div class="text-muted small">Create Direct Content assessment copies.</div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulk_target_context" id="bulk_target_playlist" value="playlist">
                            <label class="form-check-label text-dark fw-medium" for="bulk_target_playlist">
                                Playlist Content
                            </label>
                            <div class="text-muted small">Duplicate assessments specifically for learning modules inside a Playlist.</div>
                        </div>
                    </div>

                    {{-- Playlist select dropdown (shown only when target_context = playlist) --}}
                    <div id="bulkPlaylistSelectWrapper" class="d-none mt-2 p-3 bg-light rounded-3 border">
                        <label class="form-label fw-bold small required" for="bulk_playlist_id">Select Playlist</label>
                        <select id="bulk_playlist_id" name="trainingplan_id" class="form-select form-control">
                            <option value="">-- Choose a playlist --</option>
                            @foreach ($modalPlaylists as $pl)
                                <option value="{{ $pl->id }}">{{ $pl->title }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback d-none text-danger small mt-1" id="bulkPlaylistError">Please select a valid playlist.</div>
                    </div>
                </div>

                {{-- Section 3: Live Preview Box --}}
                <div id="bulkPreviewSection" class="mb-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold small text-dark">Assessment Summary Preview</span>
                            <span id="bulkPreviewLoading" class="spinner-border spinner-border-sm text-primary d-none" role="status"></span>
                        </div>

                        <div class="row g-2 text-center" id="bulkPreviewStats">
                            <div class="col-4">
                                <div class="border rounded-2 p-2 bg-white">
                                    <div class="text-muted small">Selected</div>
                                    <div class="fs-4 fw-bold text-dark" id="previewTotal">0</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded-2 p-2 bg-white">
                                    <div class="text-muted small">Will be Created</div>
                                    <div class="fs-4 fw-bold text-success" id="previewWillCreate">0</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded-2 p-2 bg-white">
                                    <div class="text-muted small">Already Exists</div>
                                    <div class="fs-4 fw-bold text-warning" id="previewAlreadyExists">0</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 text-muted small" id="bulkPreviewNotice">
                            Existing assessments will remain unchanged. A new assessment copy will be created for each selected assessment.
                        </div>
                    </div>
                </div>

                {{-- Section 4: Post-execution Result Display --}}
                <div id="bulkExecutionResults" class="d-none mb-3">
                    <div class="alert alert-success d-flex align-items-start mb-2" id="bulkResultAlert">
                        <i class="fas fa-check-circle me-2 mr-2 mt-1 fs-5"></i>
                        <div>
                            <div class="fw-bold" id="bulkResultMessage">Assessments duplicated successfully.</div>
                            <div class="small mt-1" id="bulkResultDetails"></div>
                        </div>
                    </div>
                </div>

                {{-- Standard warning note --}}
                <div class="text-muted small d-flex align-items-start" id="bulkStandardWarning">
                    <i class="fas fa-info-circle mr-2 me-2 text-info mt-1"></i>
                    <span>This action will create new assessment records. Existing assessments, user results, progress, and history will remain unchanged.</span>
                </div>
            </div>

            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" id="btnCancelBulkDuplicate">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary font-weight-semibold" id="btnSubmitBulkDuplicate">
                    <i class="fas fa-copy mr-1 me-1"></i>
                    <span class="btn-text">Duplicate Assessments</span>
                </button>
            </div>
        </div>
    </div>
</div>
