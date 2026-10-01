@php
    $trainings = $trainings ?? \App\Models\OfflineTraining::forSchedule()->withCount('participants')->orderBy('start_date', 'asc')->paginate(10);
@endphp
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover align-middle mb-0" id="manageScheduleTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold text-center align-middle" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 py-3">NO</th>
                    <th class="py-3" style="min-width: 200px;">TRAINING TITLE</th>
                    <th class="py-3">CATEGORY</th>
                    <th class="py-3" style="min-width: 140px;">DATE</th>
                    <th class="py-3" style="min-width: 120px;">TIME</th>
                    <th class="py-3">LOCATION</th>
                    <th class="py-3">INSTRUCTOR</th>
                    <th class="py-3">QUOTA / SEATS</th>
                    <th class="py-3">STATUS</th>
                    <th class="py-3" style="min-width: 170px;">ACTION</th>
                </tr>
            </thead>
            <tbody class="divide-y" id="scheduleTableBody">
                @forelse ($trainings as $item)
                    @php
                        $enrolled = $item->active_seats_count ?? ($item->relationLoaded('registrations') ? $item->registrations->whereIn('status', ['Pending', 'Approved', 'Completed'])->count() : 0);
                        $quota = $item->quota > 0 ? $item->quota : 1;
                        $remaining = max(0, $quota - $enrolled);
                        $percent = min(100, round(($enrolled / $quota) * 100));
                        $isFull = $remaining <= 0 || $item->status === 'Closed';
                        $barColor = $isFull ? '#ef4444' : ($remaining <= 10 ? '#f97316' : '#003b6d');

                        $catStyle = match($item->category) {
                            'Technical Skill'     => 'background-color: #e0f2fe; color: #0284c7;',
                            'Compliance & Safety' => 'background-color: #fef3c7; color: #b45309;',
                            'Leadership'          => 'background-color: #f3e8ff; color: #7c3aed;',
                            default               => 'background-color: #e0f2fe; color: #0369a1;',
                        };

                        $statusBadgeClass = match($item->status) {
                            'Open Registration' => 'bg-success-subtle text-success',
                            'Running'           => 'bg-warning-subtle text-warning border border-warning-subtle',
                            'Closed'            => 'bg-secondary-subtle text-secondary',
                            default             => 'bg-info-subtle text-info',
                        };

                        $dataStatus = match($item->status) {
                            'Open Registration' => 'open',
                            'Running'           => 'running',
                            'Closed'            => 'closed',
                            default             => strtolower($item->status),
                        };

                        $startDate = \Carbon\Carbon::parse($item->start_date);
                        $startTime = substr($item->start_time, 0, 5);
                        $endTime = substr($item->end_time, 0, 5);
                    @endphp
                    <tr class="schedule-table-row text-center align-middle" 
                        data-id="{{ $item->id }}" 
                        data-category="{{ $item->category }}" 
                        data-status="{{ $dataStatus }}" 
                        data-month="{{ (int)$startDate->format('n') }}"
                        data-year="{{ $startDate->format('Y') }}" 
                        data-date="{{ $startDate->format('Y-m-d') }}" 
                        data-instructor="{{ $item->instructor }}" 
                        data-location="{{ $item->location }}">
                        <td class="text-muted fw-semibold schedule-row-index">{{ method_exists($trainings, 'firstItem') && $trainings->firstItem() ? ($trainings->firstItem() + $loop->index) : $loop->iteration }}</td>
                        <td class="fw-bold text-dark text-start" style="min-width: 200px;">
                            <div class="fs-3 training-title-text">{{ $item->title }}</div>
                            <div class="text-muted extra-small font-monospace">Code: {{ $item->training_code ?? 'PTKP-TR-' . $item->id }}</div>
                        </td>
                        <td>
                            <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="{{ $catStyle }}">
                                {{ $item->category }}
                            </span>
                        </td>
                        <td class="fw-semibold text-dark text-center align-middle" style="min-width: 140px;">
                            @if($item->end_date && $item->start_date != $item->end_date)
                                @if(\Carbon\Carbon::parse($item->start_date)->format('M Y') == \Carbon\Carbon::parse($item->end_date)->format('M Y'))
                                    {{ \Carbon\Carbon::parse($item->start_date)->format('d') }} - {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($item->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                                @endif
                            @else
                                {{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}
                            @endif
                        </td>
                        <td>
                            <div class="text-muted small" style="min-width: 120px;">{{ $startTime }} - {{ $endTime }} WIB</div>
                        </td>
                        <td class="text-muted small">
                            {{ $item->location }}
                        </td>
                        <td class="text-muted small">
                            <div class="fw-semibold text-dark">{{ $item->instructor }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark small">{{ $enrolled }} / {{ $item->quota }} Seats {{ $isFull ? '(Full)' : '' }}</div>
                            <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                                <div class="progress-bar rounded-pill" role="progressbar" style="width: {{ $percent }}%; background-color: {{ $barColor }};"></div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $statusBadgeClass }} rounded-pill px-3 py-1 fw-semibold">
                                {{ $item->status }}
                            </span>
                        </td>
                        <td class="text-center py-3">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <button type="button"
                                        class="btn btn-sm btn-icon btn-outline-secondary rounded-3 btn-view-syllabus"
                                        title="View Syllabus"
                                        data-bs-toggle="modal"
                                        data-bs-target="#syllabusModal"
                                        data-id="{{ $item->id }}"
                                        data-title="{{ $item->title }}"
                                        data-category="{{ $item->category }}"
                                        data-instructor="{{ $item->instructor }}"
                                        data-duration="{{ $startTime }} - {{ $endTime }} WIB"
                                        data-description="{{ $item->description ?? '' }}"
                                        data-objectives="{{ $item->learning_objectives ?? '' }}"
                                        data-syllabus="{{ json_encode($item->syllabus ?? []) }}">
                                    <i class="ti ti-list-check fs-2"></i>
                                </button>
                                <a href="{{ route('manage.training-schedule.edit', $item->id) }}"
                                   class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-view-edit-schedule"
                                   title="View/Edit Schedule">
                                    <i class="ti ti-edit fs-2"></i>
                                </a>
                                <a href="{{ route('manage.training-schedule.participants', $item->id) }}" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule"
                                        title="View Participants"
                                        data-id="{{ $item->id }}" data-title="{{ $item->title }}">
                                    <i class="ti ti-users fs-2"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-delete-schedule"
                                        title="Delete Schedule"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteScheduleModal"
                                        data-training-id="{{ $item->id }}"
                                        data-training-title="{{ $item->title }}"
                                        data-training-code="{{ $item->training_code ?? 'PTKP-TR-' . $item->id }}"
                                        data-training-date="{{ $startDate->format('d M Y') }}"
                                        data-delete-url="{{ route('manage.training-schedule.destroy', $item->id) }}">
                                    <i class="ti ti-trash fs-2"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
                                <i class="ti ti-calendar-off fs-1"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">No Training Schedules Found</h4>
                            <p class="text-muted small mb-3">No training schedules available in the system yet. Click below to add and publish a new training schedule.</p>
                            <a href="{{ url('/manage/training-schedule/create') }}" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                                <i class="ti ti-plus fs-2"></i>
                                <span>Add Training Schedule</span>
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Empty State for Table Filtering -->
    <div class="p-5 text-center d-none" id="scheduleTableEmpty">
        <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
            <i class="ti ti-calendar-off fs-1"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">No Training Schedules Found</h4>
        <p class="text-muted small mb-0">No PTKP training schedules match the selected filters. Try changing or clearing your filter criteria.</p>
    </div>

    @if (method_exists($trainings, 'hasPages') && $trainings->hasPages())
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">
                Showing {{ $trainings->firstItem() ?? 0 }} to {{ $trainings->lastItem() ?? 0 }} of {{ $trainings->total() }} results
            </div>
            <div>
                {{ $trainings->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @elseif (method_exists($trainings, 'total') && $trainings->total() > 0)
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">
                Showing {{ $trainings->firstItem() ?? 0 }} to {{ $trainings->lastItem() ?? 0 }} of {{ $trainings->total() }} results
            </div>
        </div>
    @endif
</div>
