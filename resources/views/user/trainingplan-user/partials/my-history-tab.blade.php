@php
    $myHistory = $myHistory ?? collect();
    if ($myHistory->isEmpty() && \Illuminate\Support\Facades\Auth::check()) {
        $userId = \Illuminate\Support\Facades\Auth::id();
        $myHistory = \App\Models\OfflineTrainingRegister::with('training')
            ->where('user_id', $userId)
            ->orderBy('registered_at', 'desc')
            ->get()
            ->map(function ($reg, $idx) {
                $training = $reg->training;
                return (object)[
                    'id'          => $reg->id,
                    'modal_id'    => $training->id ?? $reg->id,
                    'title'       => $training->title ?? 'Training Schedule',
                    'category'    => $training->category ?? 'General',
                    'start_date'  => $training->start_date ?? $reg->registered_at,
                    'end_date'    => $training->end_date ?? null,
                    'start_time'  => $training->start_time ?? '08:00:00',
                    'end_time'    => $training->end_time ?? '16:00:00',
                    'location'    => $training->location ?? '-',
                    'instructor'  => $training->instructor ?? '-',
                    'description' => $training->description ?? '',
                    'status'      => $reg->status,
                ];
            });
    }
@endphp

{{-- Tab Riwayat Saya / My History --}}
<div class="card border border-light-subtle shadow-sm rounded-4 bg-white p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Training Registration & History</h3>
            <p class="text-muted small mb-0">List of your enrolled training sessions, approval statuses, and completion history.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-lt text-primary px-3 py-2 rounded-pill fw-semibold" id="myHistoryTotalBadge">
                Total: {{ count($myHistory) }} Trainings
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-vcenter align-middle mb-0" id="myHistoryTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold text-center align-middle" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 py-3">NO</th>
                    <th class="py-3" style="min-width: 200px;">TRAINING NAME</th>
                    <th class="py-3">CATEGORY</th>
                    <th class="py-3" style="min-width: 140px;">DATE</th>
                    <th class="py-3" style="min-width: 120px;">TIME</th>
                    <th class="py-3">LOCATION</th>
                    <th class="py-3">STATUS</th>
                    <th class="py-3" style="min-width: 160px;">ACTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($myHistory as $item)
                    @php
                        $catStyle = match($item->category ?? '') {
                            'Technical Skill'     => 'background-color: #e0f2fe; color: #0284c7;',
                            'Compliance & Safety' => 'background-color: #fef3c7; color: #b45309;',
                            'Leadership'          => 'background-color: #f3e8ff; color: #7c3aed;',
                            default               => 'background-color: #e0f2fe; color: #0369a1;',
                        };

                        $statusBadgeClass = match($item->status ?? '') {
                            'Approved'  => 'bg-success-subtle text-success',
                            'Pending'   => 'bg-warning-subtle text-warning border border-warning-subtle',
                            'Completed' => 'text-info',
                            'Rejected'  => 'bg-danger-subtle text-danger',
                            default     => 'bg-secondary-subtle text-secondary',
                        };

                        $statusBadgeStyle = ($item->status ?? '') === 'Completed'
                            ? 'background-color: rgba(2, 132, 199, 0.12) !important; color: #0284c7 !important;'
                            : '';

                        $startTime = substr($item->start_time ?? '00:00', 0, 5);
                        $endTime = substr($item->end_time ?? '00:00', 0, 5);

                        $modalDateStr = ($item->end_date && $item->start_date != $item->end_date)
                            ? \Carbon\Carbon::parse($item->start_date)->format('d F Y') . ' - ' . \Carbon\Carbon::parse($item->end_date)->format('d F Y') . ', ' . $startTime . ' - ' . $endTime . ' WIB'
                            : \Carbon\Carbon::parse($item->start_date)->format('d F Y') . ', ' . $startTime . ' - ' . $endTime . ' WIB';
                    @endphp
                    <tr id="history-row-{{ $item->id }}" class="text-center align-middle">
                        <td class="text-muted fw-semibold">{{ $loop->iteration }}</td>
                        <td class="fw-bold text-dark" style="min-width: 200px;">{{ $item->title }}</td>
                        <td>
                            <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="{{ $catStyle }}">{{ $item->category }}</span>
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
                        <td class="text-muted small" style="min-width: 120px;">{{ $startTime }} - {{ $endTime }} WIB</td>
                        <td class="text-muted small">{{ $item->location }}</td>
                        <td>
                            <span class="badge {{ $statusBadgeClass }} rounded-pill px-3 py-1 fw-semibold" @if($statusBadgeStyle) style="{{ $statusBadgeStyle }}" @endif>{{ $item->status }}</span>
                        </td>
                        <td style="min-width: 160px;">
                            @if(($item->status ?? '') === 'Pending')
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#eventDetailModal"
                                            data-id="{{ $item->modal_id ?? $item->id }}"
                                            data-title="{{ $item->title }}"
                                            data-category="{{ $item->category }}"
                                            data-date="{{ $modalDateStr }}"
                                            data-location="{{ $item->location }}"
                                            data-instructor="{{ $item->instructor }}"
                                            data-description="{{ $item->description }}">
                                        Detail
                                    </button>
                                    <form action="{{ route('trainingplan.cancel', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel your training registration?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1" title="Cancel Registration">
                                            <i class="ti ti-trash"></i>
                                            <span>Cancel</span>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#eventDetailModal"
                                        data-id="{{ $item->modal_id ?? $item->id }}"
                                        data-title="{{ $item->title }}"
                                        data-category="{{ $item->category }}"
                                        data-date="{{ $modalDateStr }}"
                                        data-location="{{ $item->location }}"
                                        data-instructor="{{ $item->instructor }}"
                                        data-description="{{ $item->description }}">
                                    Detail
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
                                <i class="ti ti-history-off fs-1"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">No Training History Found</h4>
                            <p class="text-muted small mb-0">You have not registered for any offline training sessions yet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
