{{-- Table Card --}}
<div class="card owl-main-card">
    <div class="card-header">
        <div>
            <h3 class="card-title">{{ $title }}</h3>
            <div class="card-subtitle text-muted mt-1">
                Showing {{ $contents->firstItem() ?? 0 }}–{{ $contents->lastItem() ?? 0 }} of {{ $contents->total() }} content(s)
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            @if ($contents->count() > 0)
                <table class="table table-hover owl-table mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Classification</th>
                            <th>Duration</th>
                            <th>Learning Hour</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Exam</th>
                            <th>Playlist</th>
                            <th>Deadline</th>
                            <th class="text-center">Views</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contents as $list)
                            <tr>
                                <td>{{ $contents->firstItem() + $loop->index }}</td>
                                <td>
                                    <span class="monitoring-link fw-semibold"
                                        data-bs-trigger="hover"
                                        data-bs-toggle="popover"
                                        title="Title"
                                        data-bs-content="{{ $list->title }}">
                                        {{ Str::limit($list->title, 30) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $typeColor = [
                                            'Video'          => 'blue',
                                            'Document'       => 'cyan',
                                            'YouTube'        => 'red',
                                            'SOP/Regulation' => 'purple',
                                        ][$list->type] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $typeColor }}-lt">{{ \App\Support\ContentTypeHelper::label($list->type) }}</span>
                                </td>
                                <td>{{ $list->classification->classification_name ?? '-' }}</td>
                                <td>{{ \App\Support\DurationFormatter::format($list->duration) }}</td>
                                <td>
                                    @if ($list->learning_hours !== null)
                                        <span class="badge bg-blue-lt font-monospace text-nowrap" style="letter-spacing: 0.02em;">
                                            <i class="ti ti-clock me-1 opacity-75"></i>{{ number_format((float) $list->learning_hours, 4, '.', '') }} h
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>{{ $list->date ? \Carbon\Carbon::parse($list->date)->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @php $isActive = $list->active == 'Active'; @endphp
                                    <span class="badge bg-{{ $isActive ? 'success' : 'warning' }}-lt text-{{ $isActive ? 'success' : 'warning' }}">
                                        {{ $list->active }}
                                    </span>
                                </td>
                                <td>
                                    @if ($list->exam == 'OK')
                                        <span class="badge bg-success-lt text-success">OK</span>
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary">NOT</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($list->playlistonly == 'Y')
                                        <span class="badge bg-purple-lt text-purple">Yes</span>
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($list->has_deadline && $list->from_date && $list->to_date)
                                        @php $expired = now()->gt($list->to_date); @endphp
                                        <span class="badge bg-{{ $expired ? 'danger' : 'cyan' }}-lt text-{{ $expired ? 'danger' : 'cyan' }}" style="font-size:11px;">
                                            {{ $list->from_date->format('d/m/Y') }} – {{ $list->to_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary" style="font-size:11px;">No Deadline</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-azure-lt text-azure">{{ number_format($list->views ?? 0) }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary owl-action-btn btn-view-content-detail"
                                            data-id="{{ $list->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#contentDetailModal"
                                            title="View Detail"
                                            aria-label="View content details">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                        <a href="{{ route('content.edit', $list) }}"
                                            class="btn btn-sm btn-info owl-action-btn"
                                            title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form action="{{ route('content.destroy', $list) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-danger owl-action-btn"
                                                title="Delete"
                                                onclick="return confirm('Are you sure want to delete this data?')">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty py-5">
                    <div class="empty-icon">
                        <i class="ti ti-books" style="font-size:3rem;opacity:.4;"></i>
                    </div>
                    <p class="empty-title">No content found</p>
                    <p class="empty-subtitle text-secondary">Try adjusting your filters or add new content.</p>
                    <div class="empty-action">
                        <a href="{{ route('content.create') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-plus me-1"></i>Add Content
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @if ($contents->hasPages())
        <div class="card-footer d-flex align-items-center justify-content-center py-3">
            {{ $contents->links() }}
        </div>
    @endif
</div>
