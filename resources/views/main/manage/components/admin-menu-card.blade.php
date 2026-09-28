<div class="col-sm-6 col-lg-4 col-xl-3 mb-3">

    <a href="{{ $url ?? url('/manage/training-schedule') }}"
       class="text-decoration-none text-reset d-block h-100">

        <div class="card border-0 shadow-sm h-100 admin-menu-card">

            <div class="card-body p-4 d-flex flex-column">

                <div class="admin-card-icon mb-3">
                    @php
                        $isScheduleIcon = in_array($icon ?? '', ['ti ti-calendar-event', 'ti-calendar-event', 'ti ti-calendar']);
                    @endphp
                    <span class="avatar avatar-md"
                          style="width: 48px; height: 48px; font-size: 1.25rem; {{ $isScheduleIcon ? 'background-color: #e0f2fe !important; color: #0284c7 !important; border-radius: 14px; border: 1px solid #bae6fd;' : ($iconStyle ?? '') }}">
                        <i class="{{ $icon ?? 'ti ti-calendar-event' }} fs-2"></i>
                    </span>
                </div>

                <h3 class="admin-card-title mb-1.5">
                    {{ $title ?? 'Training Schedule Management' }}
                </h3>

                <p class="admin-card-desc mb-3">
                    {{ $description ?? 'Create new PTKP training schedules, manage quotas, instructors, locations, and registration statuses.' }}
                </p>

                <div class="mt-auto pt-2">
                    <div class="admin-card-action d-inline-flex align-items-center">
                        <span>{{ $button ?? 'Manage Schedule' }}</span>
                        <i class="ti ti-arrow-right ms-1.5"></i>
                    </div>
                </div>

            </div>

        </div>

    </a>

</div>
