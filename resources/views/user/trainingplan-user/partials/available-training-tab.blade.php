@php
    $availableTrainings = $availableTrainings ?? \App\Models\OfflineTraining::withCount('participants')->where('status', '!=', 'Closed')->orderBy('start_date', 'asc')->get();
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
    
    {{-- Quick Filter Bar --}}
    <div class="row g-3 align-items-center mb-4 pb-2 border-bottom border-light-subtle">
        <div class="col-lg-5 col-md-5">
            <div class="input-icon">
                <span class="input-icon-addon">
                    <i class="ti ti-search text-muted fs-2"></i>
                </span>
                <input type="text" id="filterTrainingSearch" class="form-control rounded-3" placeholder="Search training title, instructor, or keywords...">
            </div>
        </div>
        <div class="col-lg-3 col-md-3">
            <select id="filterTrainingCategory" class="form-select rounded-3">
                <option value="">All Category</option>
                <option value="Soft Skill">Soft Skill</option>
                <option value="Technical Skill">Technical Skill</option>
                <option value="Compliance & Safety">Compliance & Safety</option>
                <option value="Leadership">Leadership</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-2">
            <select id="filterTrainingStatus" class="form-select rounded-3">
                <option value="">All Status</option>
                <option value="open">Open Registration</option>
                <option value="running">Running</option>
                <option value="pending">Pending Approval</option>
                <option value="registered">Registered</option>
                <option value="closed">Closed Registration</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-2 d-flex justify-content-end">
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-medium align-self-center" id="availableTrainingTotalBadge">
                Total: {{ $availableTrainings->count() }} Training{{ $availableTrainings->count() === 1 ? '' : 's' }}
            </span>
        </div>
    </div>

    {{-- Available Training Table --}}
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover align-middle mb-0" id="availableTrainingTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 text-center align-middle py-3">NO</th>
                    <th class="text-center align-middle py-3" style="min-width: 200px;">TRAINING NAME</th>
                    <th class="text-center align-middle py-3">CATEGORY</th>
                    <th class="text-center align-middle py-3" style="min-width: 110px;">DATE</th>
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
                        $enrolled = $item->participants_count ?? ($item->relationLoaded('participants') ? $item->participants->count() : 0);
                        $quota = $item->quota > 0 ? $item->quota : 1;
                        $remaining = max(0, $quota - $enrolled);
                        $percent = min(100, round(($enrolled / $quota) * 100));
                        $isFull = $remaining <= 0;
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
                            default             => 'bg-info-subtle text-info',
                        };

                        $dataStatus = match($item->status) {
                            'Open Registration' => 'open',
                            'Running'           => 'running',
                            default             => strtolower($item->status),
                        };

                        $startDate = \Carbon\Carbon::parse($item->start_date);
                        $startTime = substr($item->start_time, 0, 5);
                        $endTime = substr($item->end_time, 0, 5);
                    @endphp
                    <tr class="training-table-row" 
                        data-id="{{ $item->id }}" 
                        data-category="{{ $item->category }}" 
                        data-status="{{ $dataStatus }}" 
                        data-instructor="{{ $item->instructor }}">
                        <td class="text-center align-middle text-muted fw-semibold">{{ $loop->iteration }}</td>
                        <td class="text-center align-middle" style="min-width: 200px;">
                            <div class="fw-bold text-dark fs-3">{{ $item->title }}</div>
                            @if (!empty($item->training_code))
                                <div class="text-muted extra-small font-monospace">{{ $item->training_code }}</div>
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ $catStyle }}">
                                {{ $item->category }}
                            </span>
                        </td>
                        <td class="text-center align-middle" style="min-width: 110px;">
                            <div class="fw-semibold text-dark">{{ $startDate->format('d M Y') }}</div>
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
                                    data-title="{{ $item->title }}" 
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
                        <td class="text-center align-middle pe-4">
                            @if ($item->status === 'Open Registration' && !$isFull)
                                <button type="button" class="btn btn-owl-register rounded-pill px-4 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                                    <span>Register</span>
                                </button>
                            @elseif ($item->status === 'Running')
                                <button type="button" class="btn btn-secondary rounded-pill px-4 py-1-5 fw-semibold" disabled>
                                    <span>Running</span>
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary rounded-pill px-4 py-1-5 fw-semibold opacity-75" disabled>
                                    <span>Full</span>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="ti ti-calendar-off fs-1 d-block mb-2 text-secondary"></i>
                            No available trainings open for registration right now.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>