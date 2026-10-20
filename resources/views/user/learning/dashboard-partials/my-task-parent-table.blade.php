{{-- Parent (placement) table — shared by the In Progress and Completed sections. --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-vcenter mb-0">
            <thead>
                <tr>
                    <th class="w-1">NO</th>
                    <th>PLACEMENT</th>
                    <th>TYPE</th>
                    <th class="text-center">PROGRESS</th>
                    <th>DEADLINE</th>
                    <th>STATUS</th>
                    <th class="text-end">ACTION</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tableGroups as $i => $group)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td><span class="fw-semibold">{{ $group['parent_name'] }}</span></td>
                        <td>
                            <span class="badge {{ $parentTypeBadge[$group['parent_type']] ?? 'bg-secondary-lt text-secondary' }}">
                                {{ $parentTypeLabel[$group['parent_type']] ?? $group['parent_type'] }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="fw-semibold small">{{ $group['completed'] }}/{{ $group['total'] }}</span>
                            <div class="progress mx-auto" style="height:4px;max-width:90px">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: {{ $group['total'] ? round($group['completed'] / $group['total'] * 100) : 0 }}%"></div>
                            </div>
                        </td>
                        <td class="text-nowrap">
                            @if ($group['nearest_deadline'])
                                @php
                                    $gdl = $group['nearest_deadline'];
                                    $gdlOverdue = $gdl->isPast();
                                    $gdlSoon = ! $gdlOverdue && $gdl->diffInDays(now()) <= 3;
                                @endphp
                                @if ($gdlOverdue)
                                    <span class="badge bg-danger"><i class="ti ti-alert-circle me-1"></i>{{ $gdl->format('d M Y') }}</span>
                                @elseif ($gdlSoon)
                                    <span class="badge bg-warning text-dark"><i class="ti ti-clock me-1"></i>{{ $gdl->format('d M Y') }}</span>
                                @else
                                    <span class="text-muted">{{ $gdl->format('d M Y') }}</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if (in_array($filter, ['submitted', 'completed', 'overdue'], true))
                                {{-- A task-status tab is active: every row has this status --}}
                                <span class="badge {{ $statusBadges[$filter] }}">{{ $statusLabels[$filter] }}</span>
                            @elseif ($group['has_overdue'])
                                <span class="badge bg-danger-lt text-danger">Overdue</span>
                            @elseif ($group['completed'] === $group['total'])
                                <span class="badge bg-success-lt text-success">Completed</span>
                            @else
                                <span class="badge bg-primary-lt text-primary">In Progress</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ $groupTasksUrl($group['key']) }}" class="btn btn-sm btn-success">
                                View Tasks <i class="ti ti-chevron-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
