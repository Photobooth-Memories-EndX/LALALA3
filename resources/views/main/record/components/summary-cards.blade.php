@php
    $totalVal = (int) ($total_learning ?? ($totalLearning ?? ($total_participants ?? 0)));
    $ongoingVal = (int) ($ongoing ?? 0);
    $finishedVal = (int) ($finished ?? ($finish ?? 0));
    $hoursVal = (float) ($total_learning_hours ?? ($learningHours ?? ($totalLearningHours ?? 0)));

    if ($hoursVal == 0) {
        $formattedHours = '0';
    } elseif (floor($hoursVal) == $hoursVal) {
        $formattedHours = number_format($hoursVal, 0);
    } elseif (round($hoursVal, 1) == round($hoursVal, 2)) {
        $formattedHours = number_format($hoursVal, 1);
    } else {
        $formattedHours = number_format($hoursVal, 2);
    }
@endphp

<style>
    .summary-cards-row .stat-kpi-card {
        border-radius: 12px !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .summary-cards-row .stat-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08) !important;
    }
    .summary-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.25rem;
    }
    .summary-icon-total {
        background-color: #ede9fe;
        color: #7c3aed;
    }
    .summary-icon-ongoing {
        background-color: #fef3c7;
        color: #d97706;
    }
    .summary-icon-finished {
        background-color: #ecfdf5;
        color: #059669;
    }
    .summary-icon-hours {
        background-color: #e0f2fe;
        color: #0284c7;
    }

    /* Dark Mode Theme Support */
    [data-bs-theme="dark"] .summary-cards-row .stat-kpi-card,
    body.dark-mode .summary-cards-row .stat-kpi-card,
    body.theme-dark .summary-cards-row .stat-kpi-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] .summary-cards-row .summary-card-value,
    body.dark-mode .summary-cards-row .summary-card-value,
    body.theme-dark .summary-cards-row .summary-card-value,
    [data-bs-theme="dark"] .summary-cards-row .text-dark,
    body.dark-mode .summary-cards-row .text-dark,
    body.theme-dark .summary-cards-row .text-dark {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] .summary-cards-row .summary-card-label,
    body.dark-mode .summary-cards-row .summary-card-label,
    body.theme-dark .summary-cards-row .summary-card-label,
    [data-bs-theme="dark"] .summary-cards-row .text-muted,
    body.dark-mode .summary-cards-row .text-muted,
    body.theme-dark .summary-cards-row .text-muted {
        color: #94a3b8 !important;
    }
    [data-bs-theme="dark"] .summary-icon-total,
    body.dark-mode .summary-icon-total,
    body.theme-dark .summary-icon-total {
        background-color: rgba(124, 58, 237, 0.2) !important;
        color: #c4b5fd !important;
    }
    [data-bs-theme="dark"] .summary-icon-ongoing,
    body.dark-mode .summary-icon-ongoing,
    body.theme-dark .summary-icon-ongoing {
        background-color: rgba(217, 119, 6, 0.2) !important;
        color: #fcd34d !important;
    }
    [data-bs-theme="dark"] .summary-icon-finished,
    body.dark-mode .summary-icon-finished,
    body.theme-dark .summary-icon-finished {
        background-color: rgba(5, 150, 105, 0.2) !important;
        color: #6ee7b7 !important;
    }
    [data-bs-theme="dark"] .summary-icon-hours,
    body.dark-mode .summary-icon-hours,
    body.theme-dark .summary-icon-hours {
        background-color: rgba(2, 132, 199, 0.2) !important;
        color: #7dd3fc !important;
    }
</style>

<!-- 4 STANDARDIZED SUMMARY CARDS -->
<div class="row g-3 mb-4 summary-cards-row">
    <!-- Card 1: Total Learning Data -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
            <div class="d-flex align-items-center">
                <div class="summary-icon-box summary-icon-total me-3">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted fw-semibold summary-card-label" style="font-size: 0.8rem; line-height: 1.2;">Total Learning Data</div>
                    <div class="fw-bold fs-3 text-dark mt-1 summary-card-value text-truncate" style="line-height: 1.1;">{{ number_format($totalVal) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Ongoing Course -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
            <div class="d-flex align-items-center">
                <div class="summary-icon-box summary-icon-ongoing me-3">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted fw-semibold summary-card-label" style="font-size: 0.8rem; line-height: 1.2;">Ongoing Course</div>
                    <div class="fw-bold fs-3 text-dark mt-1 summary-card-value text-truncate" style="line-height: 1.1;">{{ number_format($ongoingVal) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Finished Course -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
            <div class="d-flex align-items-center">
                <div class="summary-icon-box summary-icon-finished me-3">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted fw-semibold summary-card-label" style="font-size: 0.8rem; line-height: 1.2;">Finished Course</div>
                    <div class="fw-bold fs-3 text-dark mt-1 summary-card-value text-truncate" style="line-height: 1.1;">{{ number_format($finishedVal) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Total Learning Hours -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
            <div class="d-flex align-items-center">
                <div class="summary-icon-box summary-icon-hours me-3">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted fw-semibold summary-card-label" style="font-size: 0.8rem; line-height: 1.2;">Total Learning Hours</div>
                    <div class="fw-bold fs-3 text-dark mt-1 summary-card-value text-truncate" style="line-height: 1.1;">{{ $formattedHours }} Hours</div>
                </div>
            </div>
        </div>
    </div>
</div>
