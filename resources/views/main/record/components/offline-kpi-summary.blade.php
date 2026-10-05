<div class="row g-3 mb-4">
    <!-- Card 1: Total Training Programs -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                    <i class="ti ti-certificate fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Programs</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($total_trainings ?? ($totalTrainings ?? 0)) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Participants -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                    <i class="ti ti-users fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Participants</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($total_participants ?? ($totalParticipants ?? 0)) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Learning Hours -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #f59e0b; color: #ffffff;">
                    <i class="ti ti-clock-hour-4 fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Learning Hours</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($total_learning_hours ?? ($totalLearningHours ?? 0), 1) }}h</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Finished Rate -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #38b000; color: #ffffff;">
                    <i class="ti ti-circle-check fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Completion Rate</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($completion_rate ?? ($completionRate ?? 0), 1) }}%</div>
                </div>
            </div>
        </div>
    </div>
</div>
