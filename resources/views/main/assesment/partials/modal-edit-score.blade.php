<div class="modal modal-blur fade"
     id="modal-edit-score"
     tabindex="-1"
     role="dialog"
     aria-labelledby="modalEditScoreTitle"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 440px;">
        <form id="form-edit-score" method="POST" action="#" class="modal-content rounded-4 border-0 shadow">
            @csrf
            @method('PUT')

            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold d-flex align-items-center text-dark" id="modalEditScoreTitle">
                    <i class="fas fa-edit text-warning me-2"></i>
                    Edit Score
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                <div id="editScoreAlert" class="alert alert-danger d-none mb-3 py-2 px-3 small" role="alert"></div>

                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-0">Participant</label>
                        <div class="fw-bold text-dark" id="editScoreParticipantName" style="word-break: break-word;">-</div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-0">ID</label>
                            <div class="fw-semibold text-secondary" id="editScoreParticipantId">-</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted mb-0">Current Score</label>
                            <div>
                                <span class="badge bg-blue-lt text-blue fw-bold" id="editScoreCurrentScoreBadge">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold required" for="editScoreInput">
                        New Score
                    </label>
                    <input type="number"
                           class="form-control rounded-3"
                           id="editScoreInput"
                           name="score"
                           min="0"
                           max="100"
                           step="any"
                           placeholder="Enter score (0-100)"
                           required>
                    <div class="invalid-feedback" id="editScoreInputError"></div>
                    <div class="form-text small text-muted mt-1">
                        Enter a valid score between 0 and 100. Result (Pass/Failed) will update automatically.
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal" data-dismiss="modal" id="btnCancelEditScore">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold d-inline-flex align-items-center" id="btnSubmitEditScore">
                    <i class="fas fa-save me-1"></i>
                    <span class="btn-text">Save Score</span>
                </button>
            </div>
        </form>
    </div>
</div>
