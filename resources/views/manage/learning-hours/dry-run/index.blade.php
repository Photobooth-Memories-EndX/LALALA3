@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-learning-hours-dry-run-page">
    <div class="container-xl">

        <!-- Page Header -->
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <div class="text-uppercase text-muted small fw-semibold">
                    <a href="{{ route('manage.learning-hours.index') }}" class="text-decoration-none text-muted">Manage &bull; Learning Hours</a>
                    &bull; Dry Run & Execution
                </div>
                <h1 class="page-title mb-1">Historical Playlist Progress Migration — Dry Run</h1>
                <div class="text-muted">
                    Preview candidate records, deduplicate source progress, and safely execute chunked migration into <code>playlist_content_users</code>.
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <a href="{{ route('manage.learning-hours.dry-run.index', array_merge(request()->query(), ['fresh' => 1])) }}" class="btn btn-outline-secondary" title="Force refresh calculation from database">
                    <i class="ti ti-refresh me-1"></i> Refresh
                </a>
                <a href="{{ route('manage.learning-hours.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Module Hub
                </a>
                <button type="button" class="btn {{ ($summary['to_insert_count'] ?? 0) == 0 ? 'btn-outline-success' : 'btn-primary' }}" id="btnOpenExecuteModal" data-bs-toggle="modal" data-bs-target="#modalConfirmExecute" {{ (!empty($activeExecution) && $activeExecution['status'] === 'processing') ? 'disabled' : '' }}>
                    @if (($summary['to_insert_count'] ?? 0) == 0)
                        <i class="ti ti-circle-check me-1"></i> Migration Up-to-Date
                    @else
                        <i class="ti ti-player-play me-1"></i> Execute Migration
                    @endif
                </button>
            </div>
        </header>

        <!-- Live Execution Progress Card (Visible during or after execution) -->
        <div id="execution-card" class="card border-0 shadow-sm mb-4" style="{{ empty($activeExecution) ? 'display: none;' : '' }}">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold mb-0">
                    <i class="ti ti-activity me-2 text-primary"></i> Migration Execution Progress
                </h3>
                <span id="exec-status-badge" class="badge {{ (!empty($activeExecution) && $activeExecution['status'] === 'completed') ? 'bg-success-lt text-success' : ((!empty($activeExecution) && $activeExecution['status'] === 'failed') ? 'bg-danger-lt text-danger' : 'bg-warning-lt text-warning') }} fs-6 px-3 py-2">
                    @if (!empty($activeExecution) && $activeExecution['status'] === 'completed')
                        <i class="ti ti-check me-1"></i> Completed
                    @elseif (!empty($activeExecution) && $activeExecution['status'] === 'failed')
                        <i class="ti ti-alert-triangle me-1"></i> Failed
                    @else
                        <i class="ti ti-loader me-1"></i> Running
                    @endif
                </span>
            </div>
            <div class="card-body">
                <!-- Progress Section -->
                <div class="mb-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1 mb-2">
                        <div>
                            <span id="exec-progress-title" class="text-uppercase text-muted fw-bold small me-2">Migration Progress</span>
                            <span id="exec-progress-counts" class="h4 mb-0 fw-bold font-monospace text-dark">
                                {{ number_format($activeExecution['processed_records'] ?? 0) }} / {{ number_format($activeExecution['total_candidates'] ?? $summary['total_candidates']) }} records
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span id="exec-batch-badge" class="badge bg-secondary-lt font-monospace px-2 py-1">
                                Batch <span id="exec-current-batch">{{ $activeExecution['batch_number'] ?? 0 }}</span> / <span id="exec-total-batches">{{ $activeExecution['total_batches'] ?? ($summary['total_candidates'] > 0 ? ceil($summary['total_candidates'] / 1000) : 0) }}</span>
                            </span>
                            <span id="exec-progress-text" class="h4 mb-0 font-monospace fw-bold text-primary">
                                {{ $activeExecution['percentage'] ?? '0' }}%
                            </span>
                        </div>
                    </div>

                    <div class="progress" style="height: 14px;">
                        <div id="exec-progress-bar" class="progress-bar {{ (!empty($activeExecution) && $activeExecution['status'] === 'completed') ? 'bg-success' : 'progress-bar-striped progress-bar-animated bg-primary' }}"
                             role="progressbar"
                             style="width: {{ $activeExecution['percentage'] ?? 0 }}%;"
                             aria-valuenow="{{ $activeExecution['percentage'] ?? 0 }}"
                             aria-valuemin="0"
                             aria-valuemax="100"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span id="exec-step-indicator" class="small fw-semibold text-muted">
                            @if (!empty($activeExecution) && $activeExecution['status'] === 'completed')
                                {{ $activeExecution['message'] ?? 'Migration completed successfully.' }}
                            @elseif (!empty($activeExecution) && $activeExecution['status'] === 'failed')
                                {{ $activeExecution['last_error'] ?? 'Execution paused due to error.' }}
                            @else
                                Ready to start.
                            @endif
                        </span>
                        <span id="exec-status-subtext" class="small text-muted font-monospace">
                            Status: <span id="exec-live-status" class="fw-semibold">{{ ucfirst($activeExecution['status'] ?? 'Ready') }}</span>
                        </span>
                    </div>
                </div>

                <!-- Execution Metrics Row -->
                <div class="row g-3 text-center mb-3">
                    <div class="col-sm-3 col-6">
                        <div class="p-2 bg-light rounded">
                            <div class="small text-muted">Total Candidates</div>
                            <div id="exec-total-count" class="h4 mb-0 fw-bold text-dark">
                                {{ number_format($activeExecution['total_candidates'] ?? $summary['total_candidates']) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-2 bg-light rounded">
                            <div class="small text-muted">Processed Records</div>
                            <div id="exec-processed-count" class="h4 mb-0 fw-bold text-primary">
                                {{ number_format($activeExecution['processed_records'] ?? 0) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-2 bg-teal-lt rounded">
                            <div class="small text-teal">Inserted (New PCU)</div>
                            <div id="exec-inserted-count" class="h4 mb-0 fw-bold text-teal">
                                {{ number_format($activeExecution['inserted_records'] ?? 0) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-2 bg-light rounded">
                            <div class="small text-muted">Skipped (Existing)</div>
                            <div id="exec-skipped-count" class="h4 mb-0 fw-bold text-muted">
                                {{ number_format($activeExecution['skipped_records'] ?? 0) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Info Row -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center small text-muted gap-2 pt-2 border-top">
                    <div>
                        <i class="ti ti-playlist me-1"></i> Current Playlist:
                        <span id="exec-current-playlist" class="fw-semibold text-dark">
                            {{ $activeExecution['current_playlist'] ?? 'Waiting...' }}
                        </span>
                    </div>
                    <div>
                        <i class="ti ti-clock me-1"></i> Elapsed Duration:
                        <span id="exec-duration" class="font-monospace fw-semibold text-dark">
                            {{ isset($activeExecution['duration_seconds']) ? $activeExecution['duration_seconds'] . 's' : '0s' }}
                        </span>
                    </div>
                </div>

                <!-- Success Alert (Hidden until completed) -->
                <div id="exec-success-alert" class="alert alert-success border-0 shadow-sm mt-3 mb-0" style="{{ (!empty($activeExecution) && $activeExecution['status'] === 'completed') ? '' : 'display: none;' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div id="exec-success-title" class="fw-bold fs-5">
                                <i class="ti ti-circle-check me-2"></i>
                                {{ (!empty($activeExecution) && !empty($activeExecution['already_completed'])) ? 'Migration Already Complete' : 'Migration Completed Successfully!' }}
                            </div>
                            <div id="exec-success-message" class="small text-success">
                                {{ (!empty($activeExecution) && !empty($activeExecution['already_completed']))
                                    ? 'All historical playlist progress has already been migrated. ' . number_format($activeExecution['total_candidates'] ?? $summary['total_candidates']) . ' of ' . number_format($activeExecution['total_candidates'] ?? $summary['total_candidates']) . ' records are already present in isolated storage.'
                                    : 'All historical playlist candidates have been processed. Existing progress records were preserved.' }}
                            </div>
                        </div>
                        <a href="{{ route('manage.learning-hours.dry-run.index', ['cutoff' => $cutoff, 'playlist_id' => $selectedPlaylistId ?? 'all']) }}" class="btn btn-success btn-sm">
                            <i class="ti ti-refresh me-1"></i> Refresh Summary
                        </a>
                    </div>
                </div>

                <!-- Error Alert (Hidden until error occurs) -->
                <div id="exec-error-alert" class="alert alert-danger border-0 shadow-sm mt-3 mb-0" style="{{ (!empty($activeExecution) && $activeExecution['status'] === 'failed') ? '' : 'display: none;' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-5"><i class="ti ti-alert-triangle me-2"></i> Migration Execution Error</div>
                            <div id="exec-error-message" class="small text-danger">
                                {{ $activeExecution['last_error'] ?? 'Execution encountered an unexpected failure.' }}
                            </div>
                        </div>
                        <button type="button" id="exec-btn-retry" class="btn btn-danger btn-sm">
                            <i class="ti ti-reload me-1"></i> Retry Execution
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Read-Only / Safety Info Banner -->
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-3 mb-4" role="alert">
            <span class="avatar avatar-md bg-info-lt text-info">
                <i class="ti ti-shield-check fs-2"></i>
            </span>
            <div class="flex-grow-1">
                <div class="fw-semibold fs-5 text-info">STRICTLY READ-ONLY PREVIEW</div>
                <div class="small text-muted">
                    No database mutations are performed by previewing. Migrations use <code>insertOrIgnore()</code> to ensure <strong>existing Playlist Content User records are 100% preserved and never overwritten</strong>.
                    Live execution runs in sequential steps with active concurrency protection.
                </div>
            </div>
            <span class="badge bg-blue-lt text-blue fs-6 px-3 py-2">
                <i class="ti ti-eye me-1"></i> Preview Mode
            </span>
        </div>

        <!-- Filter Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h3 class="card-title text-muted small fw-bold text-uppercase">
                    <i class="ti ti-filter me-1"></i> Migration Scope Filter
                </h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('manage.learning-hours.dry-run.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Cutoff Timestamp</label>
                        <input type="text" name="cutoff" class="form-control font-monospace" value="{{ $cutoff }}" placeholder="YYYY-MM-DD HH:MM:SS">
                        <div class="form-text small text-muted">Old playlists: <code>created_at &le; cutoff</code></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Filter by Specific Playlist</label>
                        <select name="playlist_id" class="form-select">
                            <option value="all" {{ empty($selectedPlaylistId) ? 'selected' : '' }}>All Old Playlists ({{ $playlists->count() }} playlists)</option>
                            @foreach ($playlists as $pl)
                                <option value="{{ $pl->id }}" {{ $selectedPlaylistId == $pl->id ? 'selected' : '' }}>
                                    #{{ $pl->id }} - {{ Str::limit($pl->title, 45) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Search Candidates</label>
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="User name, ID emp, content...">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="ti ti-search me-1"></i> Filter
                        </button>
                        @if ($cutoff !== \App\Services\LearningHours\PlaylistProgressDryRunService::DEFAULT_CUTOFF || !empty($selectedPlaylistId) || !empty($search))
                            <a href="{{ route('manage.learning-hours.dry-run.index') }}" class="btn btn-outline-secondary" title="Reset Filters">
                                <i class="ti ti-refresh"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Old Playlists</div>
                        <div class="h2 mb-1 fw-bold text-primary">{{ number_format($summary['old_playlists_count']) }}</div>
                        <div class="small text-muted">{{ number_format($summary['contents_count']) }} contents connected</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Affected Users</div>
                        <div class="h2 mb-1 fw-bold text-azure">{{ number_format($summary['affected_users_count']) }}</div>
                        <div class="small text-muted">Learners with historical records</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Total Unique Candidates</div>
                        <div class="h2 mb-1 fw-bold text-dark">{{ number_format($summary['total_candidates']) }}</div>
                        <div class="small text-muted">Unique (user, playlist, content)</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-teal-lt">
                    <div class="card-body p-3">
                        <div class="text-teal small text-uppercase fw-semibold mb-1">New PCU to Create</div>
                        <div class="h2 mb-1 fw-bold text-teal">{{ number_format($summary['to_insert_count']) }}</div>
                        <div class="small text-teal">{{ number_format($summary['skipped_existing_count']) }} existing skipped</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breakdown Progress Statistics -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <span class="avatar avatar-md bg-success-lt text-success">
                            <i class="ti ti-check fs-2"></i>
                        </span>
                        <div>
                            <div class="text-muted small">Finished Candidates</div>
                            <div class="h3 mb-0 fw-bold text-success">{{ number_format($summary['finished_count']) }}</div>
                            <div class="small text-muted">Progress &ge; 100% or finish = 1</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <span class="avatar avatar-md bg-warning-lt text-warning">
                            <i class="ti ti-loader fs-2"></i>
                        </span>
                        <div>
                            <div class="text-muted small">In-Progress Candidates</div>
                            <div class="h3 mb-0 fw-bold text-warning">{{ number_format($summary['in_progress_count']) }}</div>
                            <div class="small text-muted">0% &lt; progress &lt; 100%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <span class="avatar avatar-md bg-secondary-lt text-secondary">
                            <i class="ti ti-clock-pause fs-2"></i>
                        </span>
                        <div>
                            <div class="text-muted small">Zero Progress Candidates</div>
                            <div class="h3 mb-0 fw-bold text-secondary">{{ number_format($summary['zero_count']) }}</div>
                            <div class="small text-muted">Progress = 0% and finish = 0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Source Data Validation Section -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold">
                    <i class="ti ti-shield-check me-2 text-success"></i> Source Data Validation (<code>content_users</code> Deduplication)
                </h3>
                <span class="badge bg-success-lt text-success">
                    <i class="ti ti-check me-1"></i> Phase 3 Ordering Applied
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded text-center">
                            <div class="text-muted small">Duplicate Groups</div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($validation['duplicate_groups_count']) }}</div>
                            <div class="small text-muted">Groups with &gt; 1 row</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded text-center">
                            <div class="text-muted small">Duplicate Source Rows</div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($validation['total_duplicate_rows']) }}</div>
                            <div class="small text-muted">Total redundant rows</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded text-center">
                            <div class="text-muted small">Conflicting Progress Groups</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($validation['conflicting_progress_groups']) }}</div>
                            <div class="small text-success">Resolved to best progress</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-success-lt rounded text-center">
                            <div class="text-success small">Protected PCU Records</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($validation['affected_pcu_records']) }}</div>
                            <div class="small text-success">Accurate 100% preserved</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border mb-3">
                    <div class="fw-semibold mb-1 text-dark">
                        <i class="ti ti-sort-descending me-1 text-primary"></i> Source Selection Priority Hierarchy:
                    </div>
                    <ol class="mb-0 small text-muted ps-3">
                        @foreach ($validation['prioritization_rules'] as $rule)
                            <li>{{ $rule }}</li>
                        @endforeach
                    </ol>
                </div>

                @if (!empty($validation['sample_conflicts']))
                    <div class="table-responsive">
                        <table class="table table-sm table-vcenter table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>User ID</th>
                                    <th>Content</th>
                                    <th>Playlists Affected</th>
                                    <th>Previous Choice (Wrong)</th>
                                    <th>New Choice (Best / Phase 3)</th>
                                    <th>Resolution Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($validation['sample_conflicts'] as $c)
                                    <tr>
                                        <td class="font-monospace fw-semibold">{{ $c['user_id'] }}</td>
                                        <td>{{ Str::limit($c['content_title'], 35) }} <span class="text-muted small">(#{{ $c['content_id'] }})</span></td>
                                        <td><span class="badge bg-secondary-lt">{{ $c['playlist_count'] }} playlists</span></td>
                                        <td><span class="badge bg-danger-lt text-danger">{{ $c['old_choice'] }}</span></td>
                                        <td><span class="badge bg-success-lt text-success fw-bold">{{ $c['new_choice'] }}</span></td>
                                        <td><span class="badge bg-teal-lt text-teal"><i class="ti ti-check me-1"></i> {{ $c['status'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Candidate Preview Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title fw-bold mb-0">Candidate Records Preview</h3>
                    <div class="text-muted small">
                        Showing {{ $candidates->firstItem() ?? 0 }} to {{ $candidates->lastItem() ?? 0 }} of {{ number_format($candidates->total()) }} unique candidate records
                    </div>
                </div>
                <div class="text-muted small">
                    Page {{ $candidates->currentPage() }} of {{ $candidates->lastPage() }}
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Learner</th>
                            <th>Playlist</th>
                            <th>Content</th>
                            <th style="width: 140px;">Progress</th>
                            <th style="width: 100px;">Finish</th>
                            <th style="width: 120px;">Watch Time</th>
                            <th style="width: 130px;">Action / State</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($candidates as $cand)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-truncate" style="max-width: 190px;" title="{{ $cand->user_name }}">
                                        {{ $cand->user_name }}
                                    </div>
                                    <div class="small text-muted font-monospace">
                                        ID: {{ $cand->user_id }} {{ $cand->id_emp ? '&bull; ' . $cand->id_emp : '' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate fw-medium" style="max-width: 250px;" title="{{ $cand->playlist_title }}">
                                        <i class="ti ti-playlist text-primary me-1"></i> {{ $cand->playlist_title }}
                                    </div>
                                    <div class="small text-muted">Playlist #{{ $cand->trainingplan_id }}</div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="{{ $cand->content_title }}">
                                        {{ $cand->content_title }}
                                    </div>
                                    <span class="badge bg-secondary-lt" style="font-size: 10px;">{{ $cand->content_type }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar {{ $cand->progress >= 100 ? 'bg-success' : ($cand->progress > 0 ? 'bg-primary' : 'bg-secondary') }}"
                                                 style="width: {{ $cand->progress }}%"></div>
                                        </div>
                                        <span class="small font-monospace fw-semibold">{{ $cand->progress }}%</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($cand->finish == '1' || $cand->progress >= 100)
                                        <span class="badge bg-success-lt text-success"><i class="ti ti-check me-1"></i> Finished</span>
                                    @elseif ($cand->progress > 0)
                                        <span class="badge bg-warning-lt text-warning"><i class="ti ti-loader me-1"></i> In-Progress</span>
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary">Not Started</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-monospace text-muted small">
                                        {{ gmdate('H:i:s', (int) $cand->current_time) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($cand->already_exists == 1)
                                        <span class="badge bg-secondary-lt text-secondary" title="Record already in playlist_content_users">
                                            <i class="ti ti-arrow-forward me-1"></i> Existing (Skip)
                                        </span>
                                    @else
                                        <span class="badge bg-teal-lt text-teal fw-semibold">
                                            <i class="ti ti-plus me-1"></i> New Insert
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="ti ti-search-off fs-2 mb-2 d-block"></i>
                                    No candidate records match the current filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="card-footer d-flex justify-content-between align-items-center py-2">
                <div class="text-muted small">
                    Showing {{ $candidates->firstItem() ?? 0 }} to {{ $candidates->lastItem() ?? 0 }} of {{ number_format($candidates->total()) }} records
                </div>
                <div>
                    {{ $candidates->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>

        <!-- Terminal Command Reference Note -->
        <div class="card border-0 bg-light p-3 text-muted small">
            <div class="fw-semibold text-dark mb-1">
                <i class="ti ti-terminal me-1"></i> CLI Command & Web Parity:
            </div>
            <div>
                Both the interactive Web Execution (AJAX chunking) and backend CLI command use the shared <code>HistoricalPlaylistProgressExecutionService</code>:
                <code class="text-dark bg-white px-2 py-1 rounded border">php artisan learning:migrate-playlist-progress --force</code>
            </div>
            <div class="mt-1 text-muted">
                Zero logic drift: Both interfaces apply identical deduplication, Phase 3 priority sorting, and <code>insertOrIgnore</code> protections.
            </div>
        </div>

    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="modalConfirmExecute" tabindex="-1" aria-labelledby="modalConfirmExecuteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- 1. Confirmation View (Initial) -->
            <div id="modal-confirm-view">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalConfirmExecuteLabel">
                        <i class="ti ti-alert-triangle text-warning me-2"></i> Execute Historical Playlist Progress Migration?
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-muted small mb-3">
                        You are about to execute progress migration from <code>content_users</code> to <code>playlist_content_users</code> for the reviewed scope below.
                    </p>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th class="bg-light text-muted w-50 small">Cutoff Timestamp</th>
                                    <td class="font-monospace small fw-semibold">{{ $cutoff }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted small">Playlist Scope</th>
                                    <td class="small fw-semibold">
                                        @if ($selectedPlaylistId)
                                            #{{ $selectedPlaylistId }} - {{ $playlists->firstWhere('id', $selectedPlaylistId)?->title ?? 'Selected Playlist' }}
                                        @else
                                            All Historical Playlists ({{ $summary['old_playlists_count'] }} Playlists)
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted small">Total Candidate PCUs</th>
                                    <td class="small font-monospace fw-bold text-primary">{{ number_format($summary['total_candidates']) }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted small">Existing PCUs (Skipped)</th>
                                    <td class="small font-monospace text-muted">{{ number_format($summary['skipped_existing_count']) }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted small">New PCUs to Create</th>
                                    <td class="small font-monospace fw-bold text-success">{{ number_format($summary['to_insert_count']) }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted small">Execution Strategy</th>
                                    <td class="small text-muted">Client-Driven AJAX Chunking (1,000 records/step)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if (($summary['to_insert_count'] ?? 0) == 0)
                        <div class="alert alert-success border-0 small mb-0">
                            <div class="fw-semibold mb-1"><i class="ti ti-check me-1"></i> Already Up-To-Date</div>
                            <div>All <strong>{{ number_format($summary['total_candidates']) }}</strong> historical candidates already exist in isolated storage. Executing will verify status without creating duplicate records.</div>
                        </div>
                    @else
                        <div class="alert alert-warning border-0 small mb-0">
                            <div class="fw-semibold mb-1"><i class="ti ti-shield-check me-1"></i> Safety & Idempotency Guarantee</div>
                            <div>This action creates new playlist progress records. <strong>Existing records will NOT be overwritten</strong> (<code>insertOrIgnore</code>). The process runs in sequential steps with active concurrency locking.</div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="btnCancelExecute">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmExecute">
                        <i class="ti ti-player-play me-1"></i> {{ ($summary['to_insert_count'] ?? 0) == 0 ? 'Confirm & Verify Status' : 'Start Migration' }}
                    </button>
                </div>
            </div>

            <!-- 2. Live Execution Progress View (Inside Modal) -->
            <div id="modal-progress-view" style="display: none;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalProgressTitle">
                        <i class="ti ti-activity text-primary me-2" id="modalProgressIcon"></i> <span id="modalProgressHeading">Historical Playlist Progress Migration</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="modalProgressCloseBtn" style="display: none;"></button>
                </div>
                <div class="modal-body py-3">
                    <!-- Live Status Subheading -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span id="modalStatusBadge" class="badge bg-warning-lt text-warning fs-6 px-3 py-1">
                            <i class="ti ti-loader me-1" id="modalStatusBadgeIcon"></i> <span id="modalLiveStatus">Processing...</span>
                        </span>
                        <span id="modalBatchBadge" class="badge bg-secondary-lt font-monospace px-2 py-1">
                            Batch <span id="modalCurrentBatch">0</span> / <span id="modalTotalBatches">{{ $activeExecution['total_batches'] ?? ($summary['total_candidates'] > 0 ? ceil($summary['total_candidates'] / 1000) : 0) }}</span>
                        </span>
                    </div>

                    <!-- Progress Numbers & Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span id="modalProgressCounts" class="h4 mb-0 fw-bold font-monospace text-dark">
                                0 / {{ number_format($summary['total_candidates']) }} records
                            </span>
                            <span id="modalProgressText" class="h4 mb-0 font-monospace fw-bold text-primary">
                                0.0%
                            </span>
                        </div>
                        <div class="progress" style="height: 12px;">
                            <div id="modalProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 role="progressbar"
                                 style="width: 0%;"
                                 aria-valuenow="0"
                                 aria-valuemin="0"
                                 aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span id="modalStepIndicator" class="small fw-semibold text-muted">
                                Preparing migration session...
                            </span>
                            <span id="modalDuration" class="small text-muted font-monospace">0s</span>
                        </div>
                    </div>

                    <!-- Counter Grid -->
                    <div class="row g-2 text-center mb-3">
                        <div class="col-3">
                            <div class="p-2 bg-light rounded">
                                <div class="text-muted" style="font-size: 0.75rem;">Total Candidates</div>
                                <div id="modalTotalCount" class="fw-bold text-dark font-monospace small">
                                    {{ number_format($summary['total_candidates']) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 bg-light rounded">
                                <div class="text-muted" style="font-size: 0.75rem;">Processed</div>
                                <div id="modalProcessedCount" class="fw-bold text-primary font-monospace small">0</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 bg-teal-lt rounded">
                                <div class="text-teal" style="font-size: 0.75rem;">Inserted</div>
                                <div id="modalInsertedCount" class="fw-bold text-teal font-monospace small">0</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 bg-light rounded">
                                <div class="text-muted" style="font-size: 0.75rem;">Skipped</div>
                                <div id="modalSkippedCount" class="fw-bold text-muted font-monospace small">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Playlist Info -->
                    <div class="small text-muted p-2 rounded bg-light border-0 mb-2">
                        <i class="ti ti-playlist me-1"></i> Current: <span id="modalCurrentPlaylist" class="fw-semibold text-dark">Initializing...</span>
                    </div>

                    <!-- Alert Boxes (Success / Error) -->
                    <div id="modalSuccessAlert" class="alert alert-success border-0 small mb-0" style="display: none;">
                        <div class="fw-semibold mb-1" id="modalSuccessTitle"><i class="ti ti-circle-check me-1"></i> Migration Completed</div>
                        <div id="modalSuccessMessage">Migration completed successfully.</div>
                    </div>
                    <div id="modalErrorAlert" class="alert alert-danger border-0 small mb-0" style="display: none;">
                        <div class="fw-semibold mb-1"><i class="ti ti-alert-triangle me-1"></i> Execution Paused</div>
                        <div id="modalErrorMessage"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between align-items-center">
                    <span id="modalFooterStatus" class="small text-muted font-monospace">
                        <i class="ti ti-loader me-1"></i> Migration is in progress...
                    </span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger btn-sm" id="modalBtnRetry" style="display: none;">
                            <i class="ti ti-rotate-clockwise me-1"></i> Retry Execution
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" id="modalBtnDismiss" style="display: none;">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function() {
    let activeExecutionId = @json($activeExecution['execution_id'] ?? null);
    let isRunning = @json(!empty($activeExecution) && $activeExecution['status'] === 'processing');
    const cutoff = @json($cutoff);
    const playlistId = @json($selectedPlaylistId);
    let stepCount = 0;
    let startTime = null;

    if (activeExecutionId) {
        $('#execution-card').show();
        updateUIProgress(@json($activeExecution));
        if (isRunning) {
            stepMigration();
        } else if (@json(!empty($activeExecution) && $activeExecution['status'] === 'completed')) {
            renderCompleted(@json($activeExecution));
        } else if (@json(!empty($activeExecution) && $activeExecution['status'] === 'failed')) {
            renderFailed(@json($activeExecution['last_error'] ?? 'Execution encountered an unexpected failure.'));
        }
    }

    // Modal lifecycle guards:
    // Prevent accidental closure while migration is running
    $('#modalConfirmExecute').on('hide.bs.modal', function(e) {
        if (isRunning) {
            e.preventDefault();
            return false;
        }
    });

    // Reset view back to confirmation when modal is dismissed
    $('#modalConfirmExecute').on('hidden.bs.modal', function() {
        if (!isRunning) {
            $('#modal-progress-view').hide();
            $('#modal-confirm-view').show();
            $('#btnConfirmExecute').prop('disabled', false);
            $('#btnCancelExecute').prop('disabled', false);
        }
    });

    $('#btnConfirmExecute').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);
        $('#btnCancelExecute').prop('disabled', true);
        $('#btnOpenExecuteModal').prop('disabled', true);

        // Transform modal content into live progress view (DO NOT close modal)
        $('#modal-confirm-view').hide();
        $('#modal-progress-view').show();
        $('#modalProgressCloseBtn').hide();
        $('#modalBtnDismiss').hide();
        $('#modalBtnRetry').hide();
        $('#modalSuccessAlert').hide();
        $('#modalErrorAlert').hide();
        $('#modalFooterStatus').show().html('<i class="ti ti-loader me-1"></i> Migration is in progress...');

        const totalCandidates = Number(@json($summary['total_candidates'])) || 0;
        const totalBatches = totalCandidates > 0 ? Math.ceil(totalCandidates / 1000) : 0;

        // Initialize modal progress widgets
        $('#modalProgressHeading').text('Historical Playlist Progress Migration');
        $('#modalProgressIcon').attr('class', 'ti ti-activity text-primary me-2');
        $('#modalStatusBadge')
            .removeClass('bg-success-lt text-success bg-danger-lt text-danger')
            .addClass('bg-warning-lt text-warning')
            .html('<i class="ti ti-loader me-1"></i> Initializing...');
        $('#modalLiveStatus').text('Initializing...');
        $('#modalStepIndicator').html('<span class="spinner-border spinner-border-sm me-1 text-primary"></span> Preparing migration session...');
        $('#modalProgressCounts').text('0 / ' + totalCandidates.toLocaleString() + ' records');
        $('#modalProgressText').text('0.0%');
        $('#modalProgressBar')
            .removeClass('bg-success bg-danger')
            .addClass('progress-bar-striped progress-bar-animated bg-primary')
            .css('width', '0%')
            .attr('aria-valuenow', 0);
        $('#modalCurrentBatch').text('0');
        $('#modalTotalBatches').text(totalBatches);
        $('#modalTotalCount').text(totalCandidates.toLocaleString());
        $('#modalProcessedCount').text('0');
        $('#modalInsertedCount').text('0');
        $('#modalSkippedCount').text('0');
        $('#modalCurrentPlaylist').text('Initializing...');
        $('#modalDuration').text('0s');

        // Also prepare background execution card for persistent display
        $('#exec-success-alert').hide();
        $('#exec-error-alert').hide();
        $('#execution-card').show();
        $('#exec-status-badge')
            .removeClass('bg-success-lt text-success bg-danger-lt text-danger')
            .addClass('bg-warning-lt text-warning')
            .html('<i class="ti ti-loader me-1"></i> Initializing...');
        $('#exec-live-status').text('Initializing...');
        $('#exec-step-indicator').html('<span class="spinner-border spinner-border-sm me-1 text-primary"></span> Preparing migration session...');
        $('#exec-progress-counts').text('0 / ' + totalCandidates.toLocaleString() + ' records');
        $('#exec-progress-text').text('0.0%');
        $('#exec-progress-bar')
            .removeClass('bg-success bg-danger')
            .addClass('progress-bar-striped progress-bar-animated bg-primary')
            .css('width', '0%')
            .attr('aria-valuenow', 0);
        $('#exec-current-batch').text('0');
        $('#exec-total-batches').text(totalBatches);
        $('#exec-total-count').text(totalCandidates.toLocaleString());
        $('#exec-processed-count').text('0');
        $('#exec-inserted-count').text('0');
        $('#exec-skipped-count').text('0');
        $('#exec-duration').text('0s');

        startTime = Date.now();
        isRunning = true;

        $.ajax({
            url: "{{ route('manage.learning-hours.dry-run.execute') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                cutoff: cutoff,
                playlist_id: playlistId,
                chunk_size: 1000
            },
            success: function(res) {
                if (res.execution) {
                    activeExecutionId = res.execution.execution_id;
                    isRunning = !res.execution.is_completed;
                    updateUIProgress(res.execution);

                    if (res.execution.is_completed) {
                        renderCompleted(res.execution);
                    } else {
                        stepMigration();
                    }
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to start execution.';
                if (xhr.status === 409) {
                    errorMsg = xhr.responseJSON?.message || 'Another migration execution is currently running.';
                } else if (xhr.responseJSON?.message) {
                    errorMsg = xhr.responseJSON.message;
                }

                renderFailed(errorMsg);
            }
        });
    });

    function stepMigration() {
        if (!activeExecutionId || !isRunning) return;

        stepCount++;
        const total = Number($('#exec-total-count').text().replace(/,/g, '')) || 0;
        const totalBatches = Number($('#exec-total-batches').text()) || 1;

        // Update card
        $('#exec-current-batch').text(stepCount);
        $('#exec-live-status').text('Processing...');
        $('#exec-status-badge')
            .removeClass('bg-success-lt text-success bg-danger-lt text-danger')
            .addClass('bg-warning-lt text-warning')
            .html('<i class="ti ti-loader me-1"></i> Running');
        $('#exec-step-indicator').text('Processing batch ' + stepCount + ' of ' + totalBatches + '...');

        // Update modal
        $('#modalCurrentBatch').text(stepCount);
        $('#modalLiveStatus').text('Processing...');
        $('#modalStatusBadge')
            .removeClass('bg-success-lt text-success bg-danger-lt text-danger')
            .addClass('bg-warning-lt text-warning')
            .html('<i class="ti ti-loader me-1"></i> Processing...');
        $('#modalStepIndicator').text('Processing batch ' + stepCount + ' of ' + totalBatches + '...');

        $.ajax({
            url: "{{ route('manage.learning-hours.dry-run.execute-step') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                execution_id: activeExecutionId
            },
            success: function(res) {
                updateUIProgress(res);

                if (res.is_completed) {
                    isRunning = false;
                    renderCompleted(res);
                } else {
                    // Sequential controlled tick
                    setTimeout(stepMigration, 150);
                }
            },
            error: function(xhr) {
                isRunning = false;
                const errData = xhr.responseJSON || {};
                renderFailed(errData.message || 'Network error occurred during migration step.');
            }
        });
    }

    function updateUIProgress(data) {
        const total = Number(data.total_candidates || 0);
        const processed = Number(data.processed_records || 0);
        const pct = Math.min(100, Math.max(0, parseFloat(data.percentage) || 0)).toFixed(1);
        const currentBatch = Number(data.batch_number || 0);
        const totalBatches = Number(data.total_batches || (total > 0 ? Math.ceil(total / (data.chunk_size || 1000)) : 0));

        let durationText = '0s';
        if (startTime) {
            const elapsed = Math.round((Date.now() - startTime) / 1000);
            durationText = elapsed + 's';
        } else if (data.duration_seconds !== null && data.duration_seconds !== undefined) {
            durationText = data.duration_seconds + 's';
        }

        // Update Execution Card
        $('#exec-progress-title').text('Migration Progress');
        $('#exec-progress-bar').css('width', pct + '%').attr('aria-valuenow', pct);
        $('#exec-progress-text').text(pct + '%');
        $('#exec-progress-counts').text(processed.toLocaleString() + ' / ' + total.toLocaleString() + ' records');
        if (currentBatch > 0) {
            $('#exec-current-batch').text(currentBatch);
        }
        $('#exec-total-batches').text(totalBatches);
        $('#exec-total-count').text(total.toLocaleString());
        $('#exec-processed-count').text(processed.toLocaleString());
        $('#exec-inserted-count').text(Number(data.inserted_records || 0).toLocaleString());
        $('#exec-skipped-count').text(Number(data.skipped_records || 0).toLocaleString());
        $('#exec-current-playlist').text(data.current_playlist || '-');
        $('#exec-duration').text(durationText);

        // Update Modal Progress View
        $('#modalProgressHeading').text('Historical Playlist Progress Migration');
        $('#modalProgressBar').css('width', pct + '%').attr('aria-valuenow', pct);
        $('#modalProgressText').text(pct + '%');
        $('#modalProgressCounts').text(processed.toLocaleString() + ' / ' + total.toLocaleString() + ' records');
        if (currentBatch > 0) {
            $('#modalCurrentBatch').text(currentBatch);
        }
        $('#modalTotalBatches').text(totalBatches);
        $('#modalTotalCount').text(total.toLocaleString());
        $('#modalProcessedCount').text(processed.toLocaleString());
        $('#modalInsertedCount').text(Number(data.inserted_records || 0).toLocaleString());
        $('#modalSkippedCount').text(Number(data.skipped_records || 0).toLocaleString());
        $('#modalCurrentPlaylist').text(data.current_playlist || '-');
        $('#modalDuration').text(durationText);
    }

    function renderCompleted(data) {
        isRunning = false;
        const total = Number(data.total_candidates || 0);
        const processed = Number(data.processed_records || total);
        const totalBatches = Number(data.total_batches || (total > 0 ? Math.ceil(total / (data.chunk_size || 1000)) : 0));
        const isAlreadyMigrated = !!data.already_completed;

        // 1. Update Execution Card
        $('#exec-status-badge')
            .removeClass('bg-warning-lt text-warning bg-danger-lt text-danger')
            .addClass('bg-success-lt text-success')
            .html('<i class="ti ti-check me-1"></i> Completed');
        $('#exec-progress-bar')
            .removeClass('progress-bar-striped progress-bar-animated bg-primary bg-danger')
            .addClass('bg-success')
            .css('width', '100%')
            .attr('aria-valuenow', 100);
        $('#exec-progress-text').text('100%');
        $('#exec-progress-counts').text(total.toLocaleString() + ' / ' + total.toLocaleString() + ' records');
        $('#exec-current-batch').text(totalBatches);
        $('#exec-total-batches').text(totalBatches);
        $('#exec-live-status').text('Completed');

        if (isAlreadyMigrated) {
            $('#exec-progress-title').text('Migration Already Complete');
            $('#exec-step-indicator').text(data.message || 'All historical playlist progress has already been migrated. ' + total.toLocaleString() + ' of ' + total.toLocaleString() + ' records are already present.');
            $('#exec-success-title').html('<i class="ti ti-circle-check me-2"></i> Migration Already Complete');
            $('#exec-success-message').text('All historical playlist progress has already been migrated. ' + total.toLocaleString() + ' of ' + total.toLocaleString() + ' records are already present in isolated storage.');
        } else {
            $('#exec-progress-title').text('Migration Completed');
            $('#exec-step-indicator').text(data.message || 'Migration completed successfully.');
            $('#exec-success-title').html('<i class="ti ti-circle-check me-2"></i> Migration Completed Successfully!');
            $('#exec-success-message').text('All historical playlist candidates have been processed. Existing progress records were preserved.');
        }
        $('#exec-success-alert').slideDown();
        $('#exec-error-alert').hide();

        // 2. Update Modal Progress View
        $('#modalStatusBadge')
            .removeClass('bg-warning-lt text-warning bg-danger-lt text-danger')
            .addClass('bg-success-lt text-success')
            .html('<i class="ti ti-check me-1"></i> Completed');
        $('#modalProgressBar')
            .removeClass('progress-bar-striped progress-bar-animated bg-primary bg-danger')
            .addClass('bg-success')
            .css('width', '100%')
            .attr('aria-valuenow', 100);
        $('#modalProgressText').text('100%');
        $('#modalProgressCounts').text(total.toLocaleString() + ' / ' + total.toLocaleString() + ' records');
        $('#modalCurrentBatch').text(totalBatches);
        $('#modalTotalBatches').text(totalBatches);
        $('#modalLiveStatus').text('Completed');
        $('#modalProcessedCount').text(total.toLocaleString());

        if (isAlreadyMigrated) {
            $('#modalProgressIcon').attr('class', 'ti ti-circle-check text-success me-2');
            $('#modalProgressHeading').text('Migration Already Complete');
            $('#modalStepIndicator').text('Completed: All candidates already migrated.');
            $('#modalSuccessTitle').html('<i class="ti ti-circle-check me-1"></i> Migration Already Complete');
            $('#modalSuccessMessage').text('All historical playlist progress has already been migrated. ' + total.toLocaleString() + ' of ' + total.toLocaleString() + ' records are already present in isolated storage.');
        } else {
            $('#modalProgressIcon').attr('class', 'ti ti-circle-check text-success me-2');
            $('#modalProgressHeading').text('Migration Completed');
            $('#modalStepIndicator').text('Migration completed successfully.');
            $('#modalSuccessTitle').html('<i class="ti ti-circle-check me-1"></i> Migration Completed Successfully!');
            $('#modalSuccessMessage').text('All ' + total.toLocaleString() + ' historical playlist candidates have been processed. Existing records were preserved.');
        }

        $('#modalSuccessAlert').slideDown();
        $('#modalErrorAlert').hide();
        $('#modalFooterStatus').html('<i class="ti ti-check text-success me-1"></i> Migration completed successfully.');
        $('#modalBtnDismiss').show();
        $('#modalProgressCloseBtn').show();
        $('#modalBtnRetry').hide();

        $('#btnConfirmExecute').prop('disabled', false);
        $('#btnCancelExecute').prop('disabled', false);
        $('#btnOpenExecuteModal').prop('disabled', false);
    }

    function renderFailed(errorMsg) {
        isRunning = false;

        // 1. Update Execution Card
        $('#exec-status-badge')
            .removeClass('bg-warning-lt text-warning bg-success-lt text-success')
            .addClass('bg-danger-lt text-danger')
            .html('<i class="ti ti-alert-triangle me-1"></i> Failed');
        $('#exec-progress-bar')
            .removeClass('progress-bar-striped progress-bar-animated bg-primary')
            .addClass('bg-danger');
        $('#exec-progress-title').text('Migration Failed');
        $('#exec-live-status').text('Failed');
        $('#exec-step-indicator').text('Execution paused due to error.');
        $('#exec-error-message').text(errorMsg);
        $('#exec-error-alert').slideDown();
        $('#exec-btn-retry').prop('disabled', false);

        // 2. Update Modal Progress View
        $('#modalProgressIcon').attr('class', 'ti ti-alert-triangle text-danger me-2');
        $('#modalProgressHeading').text('Migration Failed');
        $('#modalStatusBadge')
            .removeClass('bg-warning-lt text-warning bg-success-lt text-success')
            .addClass('bg-danger-lt text-danger')
            .html('<i class="ti ti-alert-triangle me-1"></i> Failed');
        $('#modalProgressBar')
            .removeClass('progress-bar-striped progress-bar-animated bg-primary')
            .addClass('bg-danger');
        $('#modalLiveStatus').text('Failed');
        $('#modalStepIndicator').text('Execution paused due to error.');
        $('#modalErrorMessage').text(errorMsg);
        $('#modalErrorAlert').slideDown();
        $('#modalSuccessAlert').hide();
        $('#modalFooterStatus').html('<i class="ti ti-alert-triangle text-danger me-1"></i> Execution paused due to error.');
        $('#modalBtnRetry').show().prop('disabled', false);
        $('#modalBtnDismiss').show();
        $('#modalProgressCloseBtn').show();

        $('#btnConfirmExecute').prop('disabled', false);
        $('#btnCancelExecute').prop('disabled', false);
        $('#btnOpenExecuteModal').prop('disabled', false);
    }

    $('#modalBtnRetry').on('click', function() {
        $(this).prop('disabled', true).hide();
        $('#modalBtnDismiss').hide();
        $('#modalProgressCloseBtn').hide();
        $('#modalErrorAlert').slideUp();
        $('#modalProgressHeading').text('Historical Playlist Progress Migration');
        $('#modalProgressIcon').attr('class', 'ti ti-activity text-primary me-2');
        $('#modalStatusBadge')
            .removeClass('bg-danger-lt text-danger')
            .addClass('bg-warning-lt text-warning')
            .html('<i class="ti ti-loader me-1"></i> Retrying...');
        $('#modalProgressBar')
            .removeClass('bg-danger')
            .addClass('progress-bar-striped progress-bar-animated bg-primary');
        $('#modalLiveStatus').text('Retrying...');
        $('#modalFooterStatus').show().html('<i class="ti ti-loader me-1"></i> Migration is in progress...');

        $('#exec-error-alert').slideUp();
        $('#exec-status-badge')
            .removeClass('bg-danger-lt text-danger')
            .addClass('bg-warning-lt text-warning')
            .html('<i class="ti ti-loader me-1"></i> Retrying...');
        $('#exec-progress-bar')
            .removeClass('bg-danger')
            .addClass('progress-bar-striped progress-bar-animated bg-primary');
        $('#exec-live-status').text('Retrying...');

        isRunning = true;
        stepMigration();
    });

    $('#exec-btn-retry').on('click', function() {
        $(this).prop('disabled', true);
        $('#btnOpenExecuteModal').prop('disabled', true);
        $('#exec-error-alert').slideUp();
        $('#exec-status-badge')
            .removeClass('bg-danger-lt text-danger')
            .addClass('bg-warning-lt text-warning')
            .html('<i class="ti ti-loader me-1"></i> Retrying...');
        $('#exec-progress-bar')
            .removeClass('bg-danger')
            .addClass('progress-bar-striped progress-bar-animated bg-primary');
        $('#exec-live-status').text('Retrying...');
        isRunning = true;
        stepMigration();
    });
});
</script>
@endsection
