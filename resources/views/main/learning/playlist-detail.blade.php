@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-learning-page">
    <div class="container-xl">
        <!-- TOP BREADCRUMB & BACK BUTTON -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <a href="{{ $backUrl }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                    <i class="ti ti-arrow-left"></i>
                    <span>Back to {{ $backLabel }}</span>
                </a>
            </div>
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item">
                            <a href="{{ route('learning.manage.all') }}" class="text-decoration-none text-muted">Manage Learning</a>
                        </li>
                        @if(request()->query('category_slug'))
                            <li class="breadcrumb-item">
                                <a href="{{ route('learning.manage.category', request()->query('category_slug')) }}" class="text-decoration-none text-muted">
                                    {{ $classificationName }}
                                </a>
                            </li>
                        @endif
                        <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">
                            Playlist: {{ Str::limit($playlist->title, 35) }}
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- PLAYLIST HEADER CARD -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 56px; height: 56px; background-color: #ecfdf5; color: #059669; font-size: 26px;">
                        <i class="ti ti-list-details fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="h3 fw-bold mb-0 text-dark">{{ $playlist->title }}</h2>
                            @if($playlist->active)
                                <span class="badge bg-green-lt text-green fw-bold px-2 py-1">Active</span>
                            @else
                                <span class="badge bg-secondary-lt text-secondary px-2 py-1">Inactive</span>
                            @endif
                            <span class="badge bg-purple-lt text-purple px-2 py-1">
                                <i class="ti ti-category me-1"></i>{{ $classificationName }}
                            </span>
                        </div>
                        <div class="text-muted small d-flex align-items-center flex-wrap gap-2 pt-1">
                            <span><i class="ti ti-stack-2 me-1"></i>{{ $totalContents }} Contents</span>
                            <span>•</span>
                            <span><i class="ti ti-clock me-1"></i>{{ $totalDurationFormatted }}</span>
                            <span>•</span>
                            <span><i class="ti ti-certificate me-1"></i>{{ number_format($totalLearningHours, 2) }} Learning Hours</span>
                            @if($playlist->has_deadline && $playlist->from_date && $playlist->to_date)
                                <span>•</span>
                                <span><i class="ti ti-calendar me-1"></i>{{ $playlist->from_date->format('d M Y') }} - {{ $playlist->to_date->format('d M Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STAT KPI CARDS -->
        <div class="row g-3 mb-4">
            <!-- Total Contents -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                             style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                            <i class="ti ti-stack-2 fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Content</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalContents) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Duration -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                             style="width: 44px; height: 44px; background-color: #f59e0b; color: #ffffff;">
                            <i class="ti ti-clock fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Duration</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ $totalDurationFormatted }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Learning Hours -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                             style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                            <i class="ti ti-certificate fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Learning Hours</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalLearningHours, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                             style="width: 44px; height: 44px; background-color: #10b981; color: #ffffff;">
                            <i class="ti ti-check-circle fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Status</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ $playlist->active ? 'Active' : 'Inactive' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PARTICIPANT CONTEXT (IF SPECIFIC USER SELECTED) -->
        @if($participant)
            <div class="card border-0 shadow-sm mb-4 rounded-3 participant-context-card" style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md rounded-circle bg-success text-white fw-bold">
                                {{ strtoupper(substr($participant->name, 0, 2)) }}
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h4 class="fw-bold mb-0 text-dark">{{ $participant->name }}</h4>
                                    @if($participant->id_emp)
                                        <span class="badge bg-primary-lt fw-semibold">NIP: {{ $participant->id_emp }}</span>
                                    @endif
                                    <span class="badge bg-{{ $participantStats['status_badge'] }}-lt fw-bold">
                                        {{ $participantStats['status_label'] }}
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    <span>{{ $participant->department ?? '-' }}</span>
                                    @if($participant->team)
                                        <span>• {{ $participant->team }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="text-end" style="min-width: 140px;">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-semibold text-muted">Playlist Progress</span>
                                    <span class="small fw-bold text-dark">{{ $participantStats['overall_progress'] }}%</span>
                                </div>
                                <div class="progress progress-sm" style="height: 6px; width: 140px;">
                                    <div class="progress-bar bg-success" style="width: {{ $participantStats['overall_progress'] }}%;"></div>
                                </div>
                                <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                    {{ $participantStats['finished_count'] }} of {{ $totalContents }} contents finished
                                </div>
                            </div>

                            @php
                                $globalParams = request()->except('user_id');
                                $globalUrl = route('learning.manage.playlist', array_merge(['id' => $playlist->id], $globalParams));
                            @endphp
                            <a href="{{ $globalUrl }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" data-bs-toggle="tooltip" title="Switch to playlist global overview without participant progress">
                                <i class="ti ti-users"></i>
                                <span>Global View</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 rounded-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-info-circle fs-2 text-primary flex-shrink-0"></i>
                    <div>
                        <strong class="d-block small">Global Playlist View</strong>
                        <span class="small text-muted">Showing all contents within this playlist. Individual completion and progress tracking are displayed when accessed via an employee's record.</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- CONTENTS TABLE CARD -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center gap-2">
                    <h3 class="card-title fw-bold mb-0">Playlist Contents</h3>
                    <span class="badge bg-primary-lt">{{ $totalContents }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-striped card-table w-100" id="playlist-contents-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">NO</th>
                                <th>LEARNING TITLE</th>
                                <th class="text-center" style="width: 120px;">TYPE</th>
                                <th class="text-center" style="width: 120px;">DURATION</th>
                                <th class="text-center" style="width: 140px;">PROGRESS</th>
                                <th class="text-center" style="width: 130px;">STATUS</th>
                                <th class="text-center" style="width: 130px;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contents as $index => $content)
                                @php
                                    $rawType = (string) ($content->type ?? '');
                                    $isTypeVideo = in_array(strtolower($rawType), ['video', 'youtube', 'mooc'], true);

                                    $pcu = $participant ? $pcuRecords->get($content->id) : null;
                                    $itemProgress = $pcu ? max(0, min(100, (int) $pcu->progress)) : 0;
                                    $isFinished = $pcu ? $pcu->isFinished() : false;
                                @endphp
                                <tr>
                                    <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div>
                                                <a href="{{ route('learning.manage.content', $content->slug) }}" class="fw-semibold text-reset">
                                                    {{ $content->title }}
                                                </a>
                                                @if(!empty($content->classification?->classification_name))
                                                    <div class="text-muted small" style="font-size: 0.76rem;">
                                                        <i class="ti ti-folder me-1"></i>{{ $content->classification->classification_name }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($isTypeVideo)
                                            <span class="badge" style="background-color: #f59e0b; color: #ffffff; font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 999px;">Video</span>
                                        @else
                                            <span class="badge" style="background-color: #0ea5e9; color: #ffffff; font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 999px;">Document</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-semibold text-secondary">
                                        {{ \App\Support\DurationFormatter::format($content->duration ?? null) }}
                                    </td>
                                    <td class="text-center">
                                        @if($participant)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress progress-sm flex-fill" style="min-width: 70px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ $itemProgress }}%;"></div>
                                                </div>
                                                <span class="small text-muted">{{ $itemProgress }}%</span>
                                            </div>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($participant)
                                            @if($isFinished)
                                                <span class="badge bg-green-lt text-green">Completed</span>
                                            @elseif($itemProgress > 0)
                                                <span class="badge bg-warning-lt text-warning">Ongoing</span>
                                            @else
                                                <span class="badge bg-secondary-lt text-secondary">Not Started</span>
                                            @endif
                                        @else
                                            <span class="badge bg-azure-lt text-azure">Available</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            @if($participant && $pcu)
                                                <button type="button" class="btn btn-sm btn-icon text-primary owl-action-btn owl-action-btn-view btn-view-learning-detail"
                                                        data-source-type="playlist"
                                                        data-source-id="{{ $pcu->id }}"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="View Participant Record Details">
                                                    <i class="ti ti-eye"></i>
                                                </button>
                                            @endif
                                            <a href="{{ route('learning.manage.content', $content->slug) }}" class="btn btn-sm btn-icon text-primary owl-action-btn owl-action-btn-view"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="View Content Details">
                                                <i class="ti ti-external-link"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty">
                                            <div class="empty-icon text-muted mb-2">
                                                <i class="ti ti-folder-off fs-1"></i>
                                            </div>
                                            <p class="empty-title fw-bold">No contents found</p>
                                            <p class="empty-subtitle text-muted">There are no learning contents assigned to this playlist yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @include('main.learning.components.learning-detail-modal')
    </div>
</div>
@endsection

@section('style')
<style>
    .btn-back {
        color: #0c5b9e !important;
        font-weight: 500;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }
    .btn-back:hover {
        opacity: 0.85;
        transform: translateX(-3px);
    }
    [data-bs-theme="dark"] .btn-back,
    body.dark-mode .btn-back,
    body.theme-dark .btn-back {
        color: #60a5fa !important;
    }

    #playlist-contents-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    [data-bs-theme="dark"] #playlist-contents-table thead th,
    body.dark-mode #playlist-contents-table thead th,
    body.theme-dark #playlist-contents-table thead th {
        background: var(--tblr-bg-surface-secondary, #1a2942);
        color: #94a3b8;
        border-color: rgba(255, 255, 255, 0.1);
    }

    #playlist-contents-table tbody td {
        vertical-align: middle;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    #playlist-contents-table tbody td a.text-reset:hover {
        color: var(--tblr-primary, #0c5b9e) !important;
        text-decoration: underline !important;
    }
    [data-bs-theme="dark"] #playlist-contents-table tbody td a.text-reset:hover,
    body.dark-mode #playlist-contents-table tbody td a.text-reset:hover,
    body.theme-dark #playlist-contents-table tbody td a.text-reset:hover {
        color: #60a5fa !important;
    }

    /* Action Buttons */
    .owl-action-btn {
        width: 32px !important;
        height: 32px !important;
        min-width: 32px !important;
        padding: 0 !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        line-height: 1 !important;
        border: 1px solid !important;
        transition: all 0.15s ease-in-out !important;
        background-color: #ffffff;
    }
    .owl-action-btn i {
        font-size: 15px;
        line-height: 1;
    }

    .owl-action-btn-view {
        color: #0c5b9e !important;
        border-color: #bfdbfe !important;
        background-color: #ffffff !important;
    }
    .owl-action-btn-view:hover,
    .owl-action-btn-view:focus {
        color: #0c5b9e !important;
        border-color: #0c5b9e !important;
        background-color: #eff6ff !important;
        box-shadow: 0 2px 4px rgba(12, 91, 158, 0.12) !important;
        transform: translateY(-1px);
    }

    /* Dark Mode */
    [data-bs-theme="dark"] .owl-action-btn,
    body.dark-mode .owl-action-btn,
    body.theme-dark .owl-action-btn {
        background-color: rgba(255, 255, 255, 0.04) !important;
    }

    [data-bs-theme="dark"] .owl-action-btn-view,
    body.dark-mode .owl-action-btn-view,
    body.theme-dark .owl-action-btn-view {
        color: #60a5fa !important;
        border-color: rgba(96, 165, 250, 0.35) !important;
        background-color: rgba(96, 165, 250, 0.08) !important;
    }
    [data-bs-theme="dark"] .owl-action-btn-view:hover,
    [data-bs-theme="dark"] .owl-action-btn-view:focus,
    body.dark-mode .owl-action-btn-view:hover,
    body.dark-mode .owl-action-btn-view:focus,
    body.theme-dark .owl-action-btn-view:hover,
    body.theme-dark .owl-action-btn-view:focus {
        color: #93c5fd !important;
        border-color: rgba(96, 165, 250, 0.7) !important;
        background-color: rgba(96, 165, 250, 0.22) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
    }

    [data-bs-theme="dark"] .participant-context-card,
    body.dark-mode .participant-context-card,
    body.theme-dark .participant-context-card {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(31, 41, 55, 0.95) 100%) !important;
        border-left: 4px solid #10b981 !important;
    }
</style>
@endsection

@section('script')
<script>
$(document).ready(function () {
    // Initialize tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Handle view detail modal click
    $(document).on('click', '.btn-view-learning-detail', function (e) {
        e.preventDefault();
        const sourceType = $(this).data('source-type');
        const sourceId = $(this).data('source-id');

        if (!sourceType || !sourceId) {
            return;
        }

        const modalEl = document.getElementById('learningDetailModal');
        if (!modalEl) {
            return;
        }

        const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
            ? (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl))
            : null;

        $('#learningDetailLoading').show();
        $('#learningDetailError').hide();
        $('#learningDetailContent').hide();

        if (modalInstance) {
            modalInstance.show();
        } else if ($.fn.modal) {
            $('#learningDetailModal').modal('show');
        }

        const detailUrl = "{{ url('manage/learning/record-detail') }}/" + encodeURIComponent(sourceType) + "/" + encodeURIComponent(sourceId);

        $.ajax({
            url: detailUrl,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
            },
            success: function (res) {
                if (res && res.status === 'success' && res.data) {
                    const d = res.data;
                    const emp = d.employee || {};
                    const lr = d.learning || {};
                    const pr = d.progress || {};
                    const ts = d.timestamps || {};

                    $('#ld-emp-id').text(emp.id_emp || '-');
                    $('#ld-emp-name').text(emp.name || '-');
                    $('#ld-emp-dept').text(emp.department || '-');
                    $('#ld-emp-team').text(emp.team || '-');

                    $('#ld-content-title').text(lr.title || '-');
                    $('#ld-content-type').text(lr.type || '-');
                    $('#ld-content-classification').text(lr.classification || '-');
                    $('#ld-content-category').text(lr.category || '-');
                    $('#ld-content-source').text(lr.source || '-');

                    if (d.source_type === 'playlist' && lr.playlist_name && lr.playlist_name !== '-') {
                        $('#ld-playlist-name').text(lr.playlist_name);
                        $('#ld-playlist-container').show();
                    } else {
                        $('#ld-playlist-container').hide();
                    }

                    const pct = Math.max(0, Math.min(100, parseInt(pr.progress, 10) || 0));
                    $('#ld-progress-text').text(pct + '%');
                    $('#ld-progress-bar').css('width', pct + '%');

                    const isFinished = pr.finish === 'Finished';
                    $('#ld-finish-badge')
                        .text(pr.finish || '-')
                        .removeClass('bg-success-lt text-success bg-warning-lt text-warning')
                        .addClass(isFinished ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning');

                    $('#ld-learning-status').text(pr.learning_status || '-');
                    $('#ld-status').text(pr.status || '-');
                    $('#ld-learning-hours').text(pr.learning_hours || '-');
                    $('#ld-duration').text(pr.duration || '-');
                    $('#ld-current-time').text(pr.current_time || '-');
                    $('#ld-actual-watch').text(pr.actual_watch_seconds || '-');
                    $('#ld-afk-duration').text(pr.afk_duration || '-');

                    $('#ld-created-at').text(ts.created_at || '-');
                    $('#ld-updated-at').text(ts.updated_at || '-');
                    $('#ld-completed-at').text(ts.completed_at || '-');

                    $('#learningDetailLoading').hide();
                    $('#learningDetailContent').fadeIn(150);
                } else {
                    $('#learningDetailLoading').hide();
                    $('#learningDetailErrorMessage').text((res && res.message) ? res.message : 'Unable to load learning record details.');
                    $('#learningDetailError').show();
                }
            },
            error: function (xhr) {
                $('#learningDetailLoading').hide();
                let errMsg = 'Unable to load learning record details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                $('#learningDetailErrorMessage').text(errMsg);
                $('#learningDetailError').show();
            }
        });
    });
});
</script>
@endsection
