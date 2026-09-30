<div class="page-header d-print-none mb-3 pb-0">
    <!-- Back Navigation Link -->
    <div class="mb-2">
        <a href="{{ url('/manage') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
            <i class="ti ti-arrow-left fs-2"></i>
            <span>Back</span>
        </a>
    </div>

    <!-- Hero Header Card -->
    <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <!-- Left Group: Icon Box + Title & Description -->
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                     style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                    <i class="ti ti-calendar-event" style="font-size: 2rem !important;"></i>
                </div>

                <div>
                    <h2 class="h3 fw-bold mb-1 text-dark">Manage Training Schedule</h2>
                    <p class="text-muted small mb-0">Create new PTKP training schedules, manage quotas, instructors, locations, and registration statuses.</p>
                    <div class="mt-3">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-medium d-inline-flex align-items-center" id="scheduleTotalBadge">Total Schedules: <strong id="scheduleTotalCount" class="ms-1">4 Trainings</strong></span>
                    </div>
                </div>
            </div>

            <!-- Right Group: Primary Action Button -->
            <div class="flex-shrink-0">
                <a href="{{ url('/manage/training-schedule/create') }}" class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 rounded-3" 
                   style="background-color: #003b6d; border-color: #003b6d;">
                    <i class="ti ti-plus fs-2"></i>
                    <span>Add Training Schedule</span>
                </a>
            </div>
        </div>
    </div>
</div>
