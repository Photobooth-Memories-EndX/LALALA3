<!-- Syllabus Modal for Training Schedule -->
<div class="modal fade" id="syllabusModal" tabindex="-1" aria-labelledby="syllabusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header border-bottom border-light-subtle pb-3 px-4 pt-4 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-book fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h4 class="h4 fw-bold mb-0 text-dark" id="syllabusModalTitle">Training Syllabus</h4>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold small" id="syllabusModalCategory">
                                Category
                            </span>
                        </div>
                        <div class="text-muted small d-flex align-items-center gap-2 flex-wrap" id="syllabusModalMeta">
                            <span><i class="ti ti-user me-1 text-primary"></i><span id="syllabusModalInstructor">-</span></span>
                            <span>&bull;</span>
                            <span><i class="ti ti-clock me-1 text-primary"></i><span id="syllabusModalDuration">Session Schedule</span></span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-4 py-3" style="max-height: 65vh; overflow-y: auto;">
                <!-- Overview Card -->
                <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mb-3" id="syllabusModalOverviewCard">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-info-circle text-primary fs-2 flex-shrink-0 mt-0-5"></i>
                        <div>
                            <div class="fw-bold text-dark small mb-1">Course Overview</div>
                            <p class="text-muted small mb-0" id="syllabusModalOverview">
                                {{ isset($training) && $training->description ? $training->description : 'Description overview of the training session.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Meta Details Row: Target Participants & Calculated Duration -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 h-100" id="syllabusModalTargetCard">
                            <div class="d-flex align-items-start gap-2">
                                <i class="ti ti-users text-primary fs-2 flex-shrink-0 mt-0-5"></i>
                                <div>
                                    <div class="fw-bold text-dark small mb-1">Target Participants</div>
                                    <div class="text-muted small mb-0" id="syllabusModalTarget">
                                        {{ isset($training) && $training->target_employee ? $training->target_employee : 'All relevant employees' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 h-100" id="syllabusModalDurationCard">
                            <div class="d-flex align-items-start gap-2">
                                <i class="ti ti-clock-hour-4 text-primary fs-2 flex-shrink-0 mt-0-5"></i>
                                <div>
                                    <div class="fw-bold text-dark small mb-1">Calculated Duration</div>
                                    <div class="text-muted small mb-0">
                                        <span class="fw-semibold text-dark" id="syllabusModalCalculatedDuration">
                                            @if(isset($training) && $training->start_time && $training->end_time)
                                                {{ \Carbon\Carbon::parse($training->start_time)->diffInHours(\Carbon\Carbon::parse($training->end_time)) . ' Hours' }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                        <span class="text-muted extra-small d-block" id="syllabusModalTimeRange"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Requirements Card (Prerequisites) -->
                <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mb-3 {{ (isset($training) && ($training->requirements || $training->prerequisites)) ? '' : 'd-none' }}" id="syllabusModalRequirementsCard">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-checklist text-primary fs-2 flex-shrink-0 mt-0-5"></i>
                        <div class="w-100">
                            <div class="fw-bold text-dark small mb-1">Requirements</div>
                            <div class="text-muted small mb-0" id="syllabusModalRequirements">
                                {{ isset($training) ? ($training->requirements ?? $training->prerequisites ?? '') : '' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Learning Objectives Card (Hidden if empty) -->
                <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mb-4 d-none" id="syllabusModalObjectivesCard">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-target text-primary fs-2 flex-shrink-0 mt-0-5"></i>
                        <div class="w-100">
                            <div class="fw-bold text-dark small mb-1">Learning Objectives</div>
                            <div class="text-muted small mb-0" id="syllabusModalObjectives"></div>
                        </div>
                    </div>
                </div>

                <!-- Structured Learning Topics / Syllabus Points -->
                <div class="mb-2">
                    <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3">
                        <i class="ti ti-list-check text-primary"></i>
                        <span>Syllabus Modules & Topics</span>
                    </h5>

                    <div class="d-flex flex-column gap-2" id="syllabusModalTopicsContainer">
                        <!-- Dynamically populated via JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top border-light-subtle px-4 py-3 bg-light-subtle d-flex justify-content-between align-items-center">
                <a href="#" class="btn btn-outline-primary px-3 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-1" id="btnDownloadSyllabusPdf" target="_blank">
                    <i class="ti ti-download fs-2"></i>
                    <span>Download Syllabus PDF</span>
                </a>
                <button type="button" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold" style="background-color: #003b6d; border-color: #003b6d;" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Dynamic Syllabus Modal Handler
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-view-syllabus');
        if (!btn) return;

        const modalTitle = document.getElementById('syllabusModalTitle');
        const modalCat = document.getElementById('syllabusModalCategory');
        const modalInst = document.getElementById('syllabusModalInstructor');
        const modalDur = document.getElementById('syllabusModalDuration');
        const modalOver = document.getElementById('syllabusModalOverview');
        const overviewCard = document.getElementById('syllabusModalOverviewCard');
        const modalObj = document.getElementById('syllabusModalObjectives');
        const objCard = document.getElementById('syllabusModalObjectivesCard');
        const modalTarget = document.getElementById('syllabusModalTarget');
        const targetCard = document.getElementById('syllabusModalTargetCard');
        const modalReq = document.getElementById('syllabusModalRequirements');
        const reqCard = document.getElementById('syllabusModalRequirementsCard');
        const modalCalcDuration = document.getElementById('syllabusModalCalculatedDuration');
        const modalTimeRange = document.getElementById('syllabusModalTimeRange');
        const topicsContainer = document.getElementById('syllabusModalTopicsContainer');
        const btnDownloadPdf = document.getElementById('btnDownloadSyllabusPdf');

        const trainingId = btn.getAttribute('data-id') || '';
        const title = btn.getAttribute('data-title') || 'Training Syllabus';
        const category = btn.getAttribute('data-category') || 'General';
        const instructor = btn.getAttribute('data-instructor') || '-';
        const duration = btn.getAttribute('data-duration') || 'Session Schedule';
        const overview = btn.getAttribute('data-description') || '';
        const objectives = btn.getAttribute('data-objectives') || '';
        const requirements = btn.getAttribute('data-requirements') || btn.getAttribute('data-prerequisites') || '';
        const targetEmployee = btn.getAttribute('data-target') || btn.getAttribute('data-target-employee') || '';
        const startTime = btn.getAttribute('data-start-time') || '';
        const endTime = btn.getAttribute('data-end-time') || '';
        let durationHours = btn.getAttribute('data-duration-hours') || '';

        // Calculate duration if not provided directly
        if (!durationHours && startTime && endTime) {
            const sParts = startTime.split(':');
            const eParts = endTime.split(':');
            if (sParts.length >= 2 && eParts.length >= 2) {
                const startMins = parseInt(sParts[0], 10) * 60 + parseInt(sParts[1], 10);
                const endMins = parseInt(eParts[0], 10) * 60 + parseInt(eParts[1], 10);
                let diffMins = endMins - startMins;
                if (diffMins < 0) diffMins += 24 * 60;
                const hrs = Math.floor(diffMins / 60);
                const mins = diffMins % 60;
                if (hrs > 0 && mins > 0) {
                    durationHours = `${hrs} Hours ${mins} Mins`;
                } else if (hrs > 0) {
                    durationHours = `${hrs} Hour${hrs > 1 ? 's' : ''}`;
                } else if (mins > 0) {
                    durationHours = `${mins} Mins`;
                }
            }
        }

        // Bind download PDF URL dynamically
        if (btnDownloadPdf) {
            if (trainingId) {
                btnDownloadPdf.href = `/training-schedule/${trainingId}/download-syllabus`;
                btnDownloadPdf.classList.remove('disabled');
            } else {
                btnDownloadPdf.href = '#';
                btnDownloadPdf.classList.add('disabled');
            }
        }

        if (modalTitle) modalTitle.textContent = title;
        if (modalCat) modalCat.textContent = category;
        if (modalInst) modalInst.textContent = instructor;
        if (modalDur) modalDur.textContent = duration;

        // Target Participant
        if (modalTarget) {
            modalTarget.textContent = targetEmployee.trim() ? targetEmployee : 'All relevant employees';
        }

        // Calculated Duration
        if (modalCalcDuration) {
            modalCalcDuration.textContent = durationHours || duration || '-';
        }
        if (modalTimeRange) {
            if (startTime && endTime) {
                modalTimeRange.textContent = `${startTime} - ${endTime} WIB`;
            } else if (duration && duration !== durationHours) {
                modalTimeRange.textContent = duration;
            } else {
                modalTimeRange.textContent = '';
            }
        }

        // Requirements
        if (modalReq && reqCard) {
            if (requirements.trim()) {
                reqCard.classList.remove('d-none');
                const reqLines = requirements.split(/\r?\n/).map(l => l.replace(/^[•\-\*]\s*/, '').trim()).filter(Boolean);
                if (reqLines.length > 1) {
                    modalReq.innerHTML = reqLines.map(line => `
                        <div class="d-flex align-items-start gap-2 mb-1">
                            <span class="fw-bold" style="color: #003b6d; line-height: 1.4;">•</span>
                            <span>${line}</span>
                        </div>
                    `).join('');
                } else {
                    modalReq.textContent = requirements;
                }
            } else {
                reqCard.classList.add('d-none');
            }
        }

        if (modalOver) {
            if (overview.trim()) {
                modalOver.textContent = overview;
                if (overviewCard) overviewCard.classList.remove('d-none');
            } else {
                if (overviewCard) overviewCard.classList.add('d-none');
            }
        }

        if (modalObj && objCard) {
            if (objectives.trim()) {
                objCard.classList.remove('d-none');
                const objLines = objectives.split(/\r?\n/).map(l => l.replace(/^[•\-\*]\s*/, '').trim()).filter(Boolean);
                if (objLines.length > 1) {
                    modalObj.innerHTML = objLines.map(line => `
                        <div class="d-flex align-items-start gap-2 mb-1">
                            <span class="fw-bold" style="color: #003b6d; line-height: 1.4;">•</span>
                            <span>${line}</span>
                        </div>
                    `).join('');
                } else {
                    modalObj.textContent = objectives;
                }
            } else {
                objCard.classList.add('d-none');
            }
        }

        // Parse syllabus data
        let topics = [];
        const rawSyllabus = btn.getAttribute('data-syllabus');
        if (rawSyllabus) {
            try {
                let parsed = typeof rawSyllabus === 'string' ? JSON.parse(rawSyllabus) : rawSyllabus;
                if (typeof parsed === 'string') {
                    parsed = JSON.parse(parsed);
                }
                if (Array.isArray(parsed) && parsed.length > 0) {
                    topics = parsed.map((item, idx) => ({
                        no: idx + 1,
                        title: item.topic || item.title || `Module ${idx + 1}`,
                        outline: item.description || item.outline || ''
                    }));
                }
            } catch (err) {
                console.error("Error parsing syllabus data attribute:", err);
            }
        }

        if (topicsContainer) {
            if (topics.length === 0) {
                topicsContainer.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-book-off fs-1 d-block mb-2 text-secondary"></i>
                        <p class="small mb-0">No syllabus points registered for this training schedule yet.</p>
                    </div>
                `;
            } else {
                let html = '';
                topics.forEach((t, index) => {
                    const isLast = (index === topics.length - 1);
                    const borderClass = isLast ? '' : 'border-bottom border-light-subtle';

                    // Parse outline / description into clean bullet points
                    let points = [];
                    if (Array.isArray(t.outline)) {
                        points = t.outline;
                    } else if (typeof t.outline === 'string' && t.outline.trim() !== '') {
                        if (t.outline.includes('\n')) {
                            points = t.outline.split(/\r?\n/).map(p => p.replace(/^[•\-\*]\s*/, '').trim()).filter(Boolean);
                        } else if (t.outline.includes(';') || t.outline.includes('. ')) {
                            points = t.outline.split(/;\s*|\.\s+(?=[A-Z])/).map(p => p.replace(/^[•\-\*]\s*/, '').trim()).filter(Boolean);
                        } else {
                            points = [t.outline.replace(/^[•\-\*]\s*/, '').trim()];
                        }
                    }

                    let pointsHtml = '';
                    if (points.length > 0) {
                        points.forEach(pt => {
                            pointsHtml += `
                                <div class="d-flex align-items-start gap-2 mb-1">
                                    <span class="fw-bold" style="color: #003b6d; line-height: 1.4;">•</span>
                                    <span class="text-muted small">${pt}</span>
                                </div>
                            `;
                        });
                    } else {
                        pointsHtml = `<div class="text-muted small">${t.outline || 'Standard module coverage.'}</div>`;
                    }

                    html += `
                        <div class="py-2-5 ${borderClass} syllabus-topic-item">
                            <div class="d-flex align-items-start gap-3">
                                <span class="badge rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0 mt-0-5" 
                                      style="width: 26px; height: 26px; font-size: 0.75rem; background-color: #003b6d;">
                                    ${t.no}
                                </span>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark fs-3 mb-1 syllabus-topic-title">
                                        ${t.title}
                                    </div>
                                    <div class="syllabus-topic-outline ps-1 pt-1">
                                        ${pointsHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                topicsContainer.innerHTML = html;
            }
        }
    });
});
</script>
