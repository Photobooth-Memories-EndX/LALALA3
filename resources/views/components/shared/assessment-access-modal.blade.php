<div class="modal fade assessment-access-modal" id="assessmentAccessModal" tabindex="-1" role="dialog"
     aria-labelledby="assessmentAccessModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content assessment-access-modal__content">

            {{-- Header --}}
            <div class="assessment-access-modal__header">
                <div class="assessment-access-modal__header-left">
                    <div class="assessment-access-modal__icon-box">
                        <i class="ti ti-player-play"></i>
                    </div>
                    <div class="assessment-access-modal__title-group">
                        <h5 class="assessment-access-modal__title" id="assessmentAccessModalTitle">Start Assessment</h5>
                        <span class="assessment-access-modal__subtitle">Confirmation</span>
                    </div>
                </div>
                <button type="button" class="assessment-access-modal__close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="assessment-access-modal__body">
                <div class="assessment-access-modal__card">
                    <span class="assessment-access-modal__badge" id="assessmentAccessModalTypeBadge">ASSESSMENT GLOBAL</span>
                    <h4 class="assessment-access-modal__info-title" id="assessmentAccessModalName">-</h4>
                    <p class="assessment-access-modal__info-subtitle" id="assessmentAccessModalSubText">-</p>
                    
                    <hr class="assessment-access-modal__divider">

                    <div class="assessment-access-modal__grid">
                        <div class="assessment-access-modal__grid-item">
                            <span class="assessment-access-modal__label">DATE TIME</span>
                            <p class="assessment-access-modal__value" id="assessmentAccessModalFromDate">-</p>
                            <p class="assessment-access-modal__value-sub" id="assessmentAccessModalToDate">-</p>
                        </div>
                        <div class="assessment-access-modal__grid-item">
                            <span class="assessment-access-modal__label">PASSING GRADE</span>
                            <p class="assessment-access-modal__value" id="assessmentAccessModalPassingGrade">-</p>
                        </div>
                    </div>

                    <div class="assessment-access-modal__grid assessment-access-modal__grid--attempt">
                        <div class="assessment-access-modal__grid-item">
                            <span class="assessment-access-modal__label">ATTEMPT</span>
                            <p class="assessment-access-modal__value" id="assessmentAccessModalAttempt">-</p>
                        </div>
                        <div class="assessment-access-modal__grid-item">
                            <span class="assessment-access-modal__label">BATCH</span>
                            <p class="assessment-access-modal__value" id="assessmentAccessModalBatch">-</p>
                        </div>
                    </div>
                </div>

                <p class="assessment-access-modal__question">Apakah Anda yakin ingin memulai assessment ini?</p>
            </div>

            {{-- Footer --}}
            <div class="assessment-access-modal__footer">
                <button type="button" class="assessment-access-modal__cancel-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnStartAssessmentAccess" class="assessment-access-modal__start-btn">
                    <i class="ti ti-player-play"></i>
                    <span>Start Test</span>
                </button>
            </div>

        </div>
    </div>
</div>
