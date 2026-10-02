@php
    $availableTrainings = $availableTrainings ?? \App\Models\OfflineTraining::withCount('participants')->latest()->paginate(10);
@endphp
{{-- Tab Training Tersedia / Available Training (Table View Layout) --}}
<style>
    .btn-owl-register {
        color: #003b6d !important;
        border-color: #003b6d !important;
        background-color: transparent !important;
        transition: all 0.2s ease-in-out;
    }
    .btn-owl-register:hover {
        color: #ffffff !important;
        background-color: #003b6d !important;
        border-color: #003b6d !important;
        box-shadow: 0 4px 6px -1px rgba(0, 59, 109, 0.2);
    }
    .bg-purple-subtle {
        background-color: #f3e8ff !important;
    }
    .text-purple {
        color: #6f42c1 !important;
    }
</style>
<div class="card border border-light-subtle shadow-sm rounded-4 bg-white p-4 mb-4">
    
    {{-- Quick Filter Bar matching schedule-filter.blade.php --}}
    <div class="mb-4 pb-3 border-bottom border-light-subtle">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="text-secondary small fw-semibold" style="color: #6c757d !important; font-size: 0.85rem;">
                Quick Filter
            </div>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-medium" id="availableTrainingTotalBadge">
                Total: {{ method_exists($availableTrainings, 'total') ? $availableTrainings->total() : $availableTrainings->count() }} Training{{ (method_exists($availableTrainings, 'total') ? $availableTrainings->total() : $availableTrainings->count()) === 1 ? '' : 's' }}
            </span>
        </div>

        <form id="availableFilterForm" method="GET" action="{{ route('trainingplan.user.index') }}" onsubmit="return true;">
            <div class="d-flex align-items-center gap-2 flex-wrap flex-lg-nowrap">
                <!-- 1. Category Select (Width: ~130px) -->
                <div style="min-width: 130px; flex-shrink: 0;">
                    <select id="filterTrainingCategory" name="category" class="form-select rounded-3">
                        <option value="">All Category</option>
                        <option value="Soft Skill" {{ request('category') === 'Soft Skill' ? 'selected' : '' }}>Soft Skill</option>
                        <option value="Technical Skill" {{ request('category') === 'Technical Skill' ? 'selected' : '' }}>Technical Skill</option>
                        <option value="Compliance & Safety" {{ request('category') === 'Compliance & Safety' ? 'selected' : '' }}>Compliance & Safety</option>
                        <option value="Leadership" {{ request('category') === 'Leadership' ? 'selected' : '' }}>Leadership</option>
                    </select>
                </div>

                <!-- 2. Status Select (Width: ~120px) -->
                <div style="min-width: 120px; flex-shrink: 0;">
                    <select id="filterTrainingStatus" name="status" class="form-select rounded-3">
                        <option value="">All Status</option>
                        <option value="open" {{ request('status') === 'open' || request('status') === 'Open Registration' ? 'selected' : '' }}>Open Registration</option>
                        <option value="running" {{ request('status') === 'running' || request('status') === 'Running' ? 'selected' : '' }}>Running</option>
                        <option value="closed" {{ request('status') === 'closed' || request('status') === 'Closed' ? 'selected' : '' }}>Closed Registration</option>
                    </select>
                </div>

                <!-- 3. Year Select (Width: ~100px) -->
                <div style="min-width: 100px; flex-shrink: 0;">
                    <select id="filterTrainingYear" name="year" class="form-select rounded-3">
                        <option value="" {{ !request('year') ? 'selected' : '' }}>All Year</option>
                        <option value="2026" {{ request('year') === '2026' ? 'selected' : '' }}>2026</option>
                        <option value="2025" {{ request('year') === '2025' ? 'selected' : '' }}>2025</option>
                        <option value="2027" {{ request('year') === '2027' ? 'selected' : '' }}>2027</option>
                    </select>
                </div>

                <!-- 4. Search Input (Flex grow 1 with Search Icon on left) -->
                <div class="position-relative flex-grow-1" style="min-width: 220px;">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search text-muted fs-2"></i>
                        </span>
                        <input type="text" id="filterTrainingSearch" name="search" class="form-control rounded-3" 
                               value="{{ request('search') ?? request('q') }}"
                               placeholder="Search training title, instructor, or room..." autocomplete="off">
                    </div>
                </div>

                <!-- 5. Action Buttons Group (Right side) -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <!-- Search Submit Button -->
                    <button type="submit" id="btnSubmitAvailableFilter" class="btn btn-primary px-3 rounded-3" 
                            style="background-color: #003b6d; border-color: #003b6d;" title="Apply Filter">
                        <i class="ti ti-search fs-2"></i>
                    </button>

                    <!-- Reset Link Button -->
                    <a href="{{ route('trainingplan.user.index') }}" id="btnResetFilter" class="btn btn-outline-primary px-3 rounded-3 d-inline-flex align-items-center justify-content-center" 
                       style="color: #003b6d; border-color: #003b6d;" title="Reset Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Available Training Table --}}
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover align-middle mb-0" id="availableTrainingTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 text-center align-middle py-3">NO</th>
                    <th class="text-center align-middle py-3" style="min-width: 200px;">TRAINING NAME</th>
                    <th class="text-center align-middle py-3">CATEGORY</th>
                    <th class="text-center align-middle py-3" style="min-width: 140px;">DATE</th>
                    <th class="text-center align-middle py-3" style="min-width: 120px;">TIME</th>
                    <th class="text-center align-middle py-3">LOCATION</th>
                    <th class="text-center align-middle py-3">SYLLABUS</th>
                    <th class="text-center align-middle py-3">APPROVED PARTICIPANT</th>
                    <th class="text-center align-middle py-3">STATUS</th>
                    <th class="text-center align-middle py-3 pe-4">ACTION</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($availableTrainings as $item)
                    @php
                        $title = $item->title ?? ($item->course_name ?? 'Training Schedule');
                        $enrolled = $item->registered_seats_count ?? ($item->relationLoaded('registrations') ? $item->registrations->whereIn('status', ['Pending', 'Approved', 'Completed'])->count() : 0);
                        $quota = $item->quota > 0 ? $item->quota : 1;
                        $remaining = max(0, $quota - $enrolled);
                        $percent = min(100, round(($enrolled / $quota) * 100));
                        $isFull = $remaining <= 0;
                        $barColor = $isFull ? '#ef4444' : ($remaining <= 10 ? '#f97316' : '#003b6d');

                        $myReg = $item->relationLoaded('registrations') ? $item->registrations->firstWhere('user_id', Auth::id()) : null;
                        $myRegStatus = $myReg ? $myReg->status : null;

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
                            default             => strtolower($item->status ?? 'open'),
                        };

                        $startDate = \Carbon\Carbon::parse($item->start_date);
                        $startTime = substr($item->start_time, 0, 5);
                        $endTime = substr($item->end_time, 0, 5);
                    @endphp
                    <tr class="training-table-row" 
                        data-id="{{ $item->id }}" 
                        data-category="{{ $item->category }}" 
                        data-status="{{ $dataStatus }}" 
                        data-year="{{ $startDate->format('Y') }}"
                        data-location="{{ $item->location }}"
                        data-instructor="{{ $item->instructor }}">
                        <td class="text-center align-middle text-muted fw-semibold">{{ method_exists($availableTrainings, 'firstItem') && $availableTrainings->firstItem() ? ($availableTrainings->firstItem() + $loop->index) : $loop->iteration }}</td>
                        <td class="text-center align-middle" style="min-width: 200px;">
                            <div class="fw-bold text-dark fs-3">{{ $title }}</div>
                            @if (!empty($item->training_code))
                                <div class="text-muted extra-small font-monospace">{{ $item->training_code }}</div>
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ $catStyle }}">
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
                        <td class="text-center align-middle" style="min-width: 120px;">
                            <div class="text-muted small">{{ $startTime }} - {{ $endTime }} WIB</div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="text-muted small">{{ $item->location }}</span>
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 btn-view-syllabus" 
                                    data-bs-toggle="modal" data-bs-target="#syllabusModal" 
                                    data-id="{{ $item->id }}" 
                                    data-title="{{ $title }}" 
                                    data-category="{{ $item->category }}" 
                                    data-instructor="{{ $item->instructor }}"
                                    data-duration="{{ $startTime }} - {{ $endTime }} WIB"
                                    data-description="{{ $item->description ?? '' }}"
                                    data-objectives="{{ $item->learning_objectives ?? '' }}"
                                    data-syllabus="{{ json_encode($item->syllabus ?? []) }}">
                                <i class="ti ti-list-check fs-2"></i>
                                <span>Syllabus</span>
                            </button>
                        </td>
                        <td class="text-center align-middle" style="min-width: 130px;">
                            <div class="fw-semibold text-dark small">{{ $enrolled }} / {{ $item->quota }} Seats {{ $isFull ? '(Full)' : '' }}</div>
                            <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                                <div class="progress-bar rounded-pill" role="progressbar" style="width: {{ $percent }}%; background-color: {{ $barColor }};" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge {{ $statusBadgeClass }} rounded-pill px-3 py-1 fw-semibold">
                                {{ $item->status }}
                            </span>
                        </td>
                        <!-- <td class="text-center align-middle pe-4">
                            @if ($myRegStatus === 'Pending')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-clock"></i>
                                    <span>Pending Approval</span>
                                </span>
                            @elseif ($myRegStatus === 'Approved')
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-check"></i>
                                    <span>Approved</span>
                                </span>
                            @elseif ($myRegStatus === 'Completed')
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-certificate"></i>
                                    <span>Completed</span>
                                </span>
                            @elseif ($myRegStatus === 'Rejected')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1" title="{{ $myReg->rejection_reason ?? 'Registration was rejected' }}">
                                    <i class="ti ti-x"></i>
                                    <span>Rejected</span>
                                </span>
                            @elseif ($item->status === 'Open Registration' && !$isFull)
                                <form action="{{ route('trainingplan.register', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Register for {{ addslashes($title) }}?');">
                                    @csrf
                                    <button type="submit" class="btn btn-owl-register rounded-pill px-4 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                                        <i class="ti ti-pencil-plus"></i>
                                        <span>Register</span>
                                    </button>
                                </form>
                            @elseif ($item->status === 'Running')
                                <button type="button" class="btn btn-secondary rounded-pill px-4 py-1-5 fw-semibold" disabled>
                                    <span>Running</span>
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary rounded-pill px-4 py-1-5 fw-semibold opacity-75" disabled>
                                    <span>Full</span>
                                </button>
                            @endif
                        </td> -->
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
                                <i class="ti ti-calendar-off fs-1"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">No Training Schedules Available</h4>
                            <p class="text-muted small mb-0">No active training sessions open for registration right now. Click '+ Add Training Schedule' in Manage page to publish new trainings.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($availableTrainings, 'hasPages') && $availableTrainings->hasPages())
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">
                Showing {{ $availableTrainings->firstItem() ?? 0 }} to {{ $availableTrainings->lastItem() ?? 0 }} of {{ $availableTrainings->total() }} available training(s)
            </div>
            <div>
                {{ $availableTrainings->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @elseif (method_exists($availableTrainings, 'total') && $availableTrainings->total() > 0)
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">
                Showing {{ $availableTrainings->firstItem() ?? 0 }} to {{ $availableTrainings->lastItem() ?? 0 }} of {{ $availableTrainings->total() }} available training(s)
            </div>
        </div>
    @endif
</div>