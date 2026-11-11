{{-- resources/views/manage/task/plans.blade.php --}}
{{-- Dashboard: parents (playlist / content / standalone / offline training) that have tasks. --}}
@extends('layouts.sidebar-master')

@section('content')

    <div class="page-body manage-task-page">
        <div class="container-xl">

            <!-- TOMBOL BACK RATA KIRI ATAS -->
            <div class="mb-3">
                <a href="{{ route('manage.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                    <i class="ti ti-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>

            {{-- ===== Hero Banner ===== --}}
            <section class="manage-record-hero mb-4" aria-labelledby="manage-task-hero-title">
                <div class="manage-record-hero__content">
                    <div class="manage-record-hero__badge">
                        <i class="ti ti-clipboard-list" aria-hidden="true"></i>
                        <span>TASK MANAGEMENT</span>
                    </div>

                    <h1 id="manage-task-hero-title" class="manage-record-hero__title">
                        Task Management
                    </h1>

                    <p class="manage-record-hero__description mb-0">
                        Manage tasks across playlists, contents, and offline trainings, including placement data, task progress, and review status.
                    </p>
                </div>
            </section>

            <x-alert type="success" />

            {{-- ===== 1. SUMMARY SECTION (UNIFIED KPI CONTAINER WITH DIVIDERS) ===== --}}
            <div class="mb-4">
                <div class="summary-kpi-bar">
                    <div class="row g-0 align-items-center">
                        <!-- Col 1: Total Course -->
                        <div class="col-6 col-lg-3 summary-kpi-col">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3 summary-kpi-icon" 
                                     style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                                    <i class="ti ti-stack-2 fs-2"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Course</div>
                                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['parents']) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Col 2: Total Tasks -->
                        <div class="col-6 col-lg-3 summary-kpi-col">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3 summary-kpi-icon" 
                                     style="width: 44px; height: 44px; background-color: #f59e0b; color: #ffffff;">
                                    <i class="ti ti-progress fs-2"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Tasks</div>
                                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['tasks']) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Col 3: Published -->
                        <div class="col-6 col-lg-3 summary-kpi-col">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3 summary-kpi-icon" 
                                     style="width: 44px; height: 44px; background-color: #38b000; color: #ffffff;">
                                    <i class="ti ti-circle-check fs-2"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Published</div>
                                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['published']) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Col 4: Pending Review -->
                        <div class="col-6 col-lg-3 summary-kpi-col summary-kpi-col--last">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3 summary-kpi-icon" 
                                     style="width: 44px; height: 44px; background-color: {{ ($stats['pending'] ?? 0) > 0 ? '#f59e0b' : '#64748b' }}; color: #ffffff;">
                                    <i class="ti {{ ($stats['pending'] ?? 0) > 0 ? 'ti-bell-ringing' : 'ti-clock' }} fs-2"></i>
                                </div>
                                <div>
                                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Pending Review</div>
                                    <div class="fw-bold fs-3 {{ ($stats['pending'] ?? 0) > 0 ? 'text-warning' : 'text-dark' }} mt-1" style="line-height: 1.1;">
                                        {{ number_format($stats['pending'] ?? 0) }}
                                        @if (($stats['pending'] ?? 0) > 0)
                                            <span class="badge bg-danger rounded-pill ms-1" style="font-size: 0.65rem; vertical-align: middle;">Need Action</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== 2. TASK DATA SCOPE (COMPACT REFERENCE DESIGN) ===== --}}
            <div class="mb-4">
                <div class="row g-3 record-navigation-row">
                    <!-- Scope Card 1: KP OWL -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => $type, 'search' => $search]) }}"
                            class="card text-decoration-none h-100 scope-nav-card scope-nav-card--kp record-nav-card {{ $scope === 'kp' ? 'scope-nav-card--active scope-nav-card--kp-active record-nav-card--active' : 'scope-nav-card--inactive' }}">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="scope-nav-icon scope-nav-icon--kp">
                                        <i class="ti ti-device-laptop"></i>
                                    </div>
                                    @if ($scope === 'kp')
                                        <span class="scope-nav-badge-active">
                                            ACTIVE
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="scope-nav-title mb-1">KP OWL</h3>
                                    <div class="scope-nav-subtitle fw-semibold mb-1 text-blue-primary">
                                        Online Task Data
                                    </div>
                                    <div class="scope-nav-desc">
                                        Tasks from playlists, contents &amp; others
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Scope Card 2: Non KP OWL -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <a href="{{ route('manage.task.index', ['scope' => 'non_kp', 'search' => $search]) }}"
                            class="card text-decoration-none h-100 scope-nav-card scope-nav-card--non-kp record-nav-card {{ $scope === 'non_kp' ? 'scope-nav-card--active scope-nav-card--non-kp-active record-nav-card--active' : 'scope-nav-card--inactive' }}">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="scope-nav-icon scope-nav-icon--non-kp">
                                        <i class="ti ti-map-pin"></i>
                                    </div>
                                    @if ($scope === 'non_kp')
                                        <span class="scope-nav-badge-active">
                                            ACTIVE
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="scope-nav-title mb-1">Non KP OWL</h3>
                                    <div class="scope-nav-subtitle fw-semibold mb-1 text-amber-primary">
                                        Offline Training Tasks
                                    </div>
                                    <div class="scope-nav-desc">
                                        Tasks from offline trainings
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            {{-- ===== TABLE CARD ===== --}}
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h3 class="card-title mb-1 fw-bold text-dark">
                            {{ $scope === 'non_kp' ? 'Offline Training & Task List' : 'Course & Task List' }}
                        </h3>
                        <div class="text-muted small">
                            @if ($scope === 'non_kp')
                                Directory of offline training sessions containing assigned tasks.
                            @else
                                Directory of courses, contents, and others containing assigned tasks.
                            @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('manage.task.trash') }}" class="btn btn-outline-secondary position-relative rounded-3 px-3 d-inline-flex align-items-center gap-1.5">
                            <i class="ti ti-trash fs-2"></i>
                            <span>Trash</span>
                            @if (($trashCount ?? 0) > 0)
                                <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                            @endif
                        </a>
                        @if ($scope === 'non_kp')
                            <a href="{{ route('manage.task.create', ['scope' => 'non_kp', 'parent_type' => 'offline_training']) }}"
                               class="btn btn-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5">
                                <i class="ti ti-plus fs-2"></i>
                                <span>Create New Task</span>
                            </a>
                        @else
                            <button type="button"
                                    class="btn btn-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                                    data-bs-toggle="modal"
                                    data-bs-target="#chooseContainerModal">
                                <i class="ti ti-plus fs-2"></i>
                                <span>Create New Task</span>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body border-top border-bottom py-3">
                    @if ($scope === 'kp')
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-3 my-task-pills-row">
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'all', 'search' => $search]) }}"
                               class="my-task-category-pill {{ $type === 'all' ? 'active' : '' }}"
                               title="Filter by All Tasks">
                                All Tasks
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'playlist', 'search' => $search]) }}"
                               class="my-task-category-pill {{ $type === 'playlist' ? 'active' : '' }}"
                               title="Filter by Playlist">
                                Playlist
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'content', 'search' => $search]) }}"
                               class="my-task-category-pill {{ $type === 'content' ? 'active' : '' }}"
                               title="Filter by Content">
                                Content
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'standalone', 'search' => $search]) }}"
                               class="my-task-category-pill {{ $type === 'standalone' ? 'active' : '' }}"
                               title="Filter by Others">
                                Others
                            </a>
                        </div>
                        <hr class="my-3 my-task-divider">
                    @endif

                    <form method="GET" action="{{ route('manage.task.index') }}" class="d-flex align-items-center flex-wrap gap-2 w-100 my-task-toolbar">
                        <input type="hidden" name="scope" value="{{ $scope }}">
                        @if ($scope === 'kp')
                            <input type="hidden" name="parent_type" value="{{ $type }}">
                        @endif
                        <div class="position-relative flex-grow-1 my-task-search-wrapper" style="min-width: 220px;">
                            <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted pointer-events-none" style="z-index: 2;">
                                <i class="ti ti-search" style="font-size: 15px;"></i>
                            </span>
                            <input type="text"
                                   name="search"
                                   id="adminTaskSearchInput"
                                   class="form-control my-task-search-input"
                                   placeholder="{{ $scope === 'kp' ? 'Search playlist / content...' : 'Search offline training...' }}"
                                   value="{{ $search }}"
                                   autocomplete="off">
                            @if (!empty($search))
                                <a href="{{ route('manage.task.index', array_filter(['scope' => $scope, 'parent_type' => $scope === 'kp' ? $type : null])) }}"
                                   class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-2.5 text-muted text-decoration-none my-task-search-clear"
                                   title="Clear search" style="z-index: 2;">
                                    <i class="ti ti-x"></i>
                                </a>
                            @endif
                        </div>
                        <button type="submit"
                                class="btn btn-primary my-task-btn-square my-task-btn-search"
                                title="Search">
                            <i class="ti ti-search text-white fs-3"></i>
                        </button>
                        <a href="{{ route('manage.task.index', array_filter(['scope' => $scope, 'parent_type' => $scope === 'kp' ? $type : null])) }}"
                           class="btn btn-white my-task-btn-square my-task-btn-reset"
                           title="Reset filter">
                            <i class="ti ti-refresh fs-3 text-secondary"></i>
                        </a>
                    </form>
                    @if (!empty($search))
                        <div class="d-flex align-items-center flex-wrap gap-2 mt-2.5 pt-2.5 border-top">
                            <span class="text-muted small" style="font-size: 11.5px;">Active Filter:</span>
                            <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                                <i class="ti ti-search text-muted"></i>
                                <span>"{{ $search }}"</span>
                                <a href="{{ route('manage.task.index', array_filter(['scope' => $scope, 'parent_type' => $scope === 'kp' ? $type : null])) }}" class="text-muted ms-1 text-decoration-none" title="Remove search filter"><i class="ti ti-x"></i></a>
                            </span>
                        </div>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th class="w-1">NO</th>
                                <th>CATEGORY</th>
                                <th>COURSE / ACTIVITY</th>
                                <th class="text-center">TOTAL TASK</th>
                                <th class="text-center">PUBLISHED</th>
                                <th class="text-center">DRAFT</th>
                                <th class="text-center">PENDING REVIEW</th>
                                <th class="text-nowrap">CREATED AT</th>
                                <th class="text-end">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($parents as $i => $row)
                                @php
                                    $key = "{$row->parent_type}:{$row->parent_id}";
                                    $pending = $pendingByParent[$key] ?? 0;
                                    $name = $row->parent_type === 'offline_training'
                                        ? ($row->parent_name ?: ('Offline Training' . ($row->parent_id ? " #{$row->parent_id}" : '')))
                                        : ($row->parent_type === 'standalone'
                                            ? ($row->parent_name ?: 'Others')
                                            : ($row->parent_name ?? '—'));
                                    $badge = [
                                        'playlist' => 'bg-primary',
                                        'content' => 'bg-info',
                                        'offline_training' => 'bg-warning',
                                        'standalone' => 'bg-secondary',
                                    ][$row->parent_type] ?? 'bg-secondary';
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ ($parents->firstItem() ?? 0) + $i }}</td>
                                    <td>
                                        <span class="badge {{ $badge }}">
                                            {{ \App\Models\Task::PARENT_TYPES[$row->parent_type] ?? $row->parent_type }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $name }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $row->total }}</span>
                                    </td>
                                    <td class="text-center">{{ $row->published }}</td>
                                    <td class="text-center">{{ $row->draft }}</td>
                                    <td class="text-center">
                                        @if ($pending > 0)
                                            <a href="{{ route('manage.task.parent', ['parentType' => $row->parent_type, 'parentId' => $row->parent_id ?? 0]) }}" class="text-decoration-none">
                                                <span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;" title="{{ $pending }} response(s) waiting for review">
                                                    <span class="status-dot status-dot-animated bg-red" style="width: 6px; height: 6px;"></span>
                                                    <i class="ti ti-bell-ringing"></i>
                                                    <span>{{ $pending }} Need Review</span>
                                                </span>
                                            </a>
                                        @else
                                            <span class="badge bg-secondary-lt text-muted px-2 py-0.5" style="font-size: 0.75rem;">0</span>
                                        @endif
                                    </td>
                                    <td class="text-muted text-nowrap" style="font-size: 0.85rem;" title="{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') : '' }}">
                                        {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d M Y') : '—' }}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <a href="{{ route('manage.task.parent', ['parentType' => $row->parent_type, 'parentId' => $row->parent_id ?? 0]) }}"
                                               class="btn btn-outline-primary rounded-3">
                                                Manage Tasks <i class="ti ti-arrow-right ms-1"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary container-menu-btn" title="Actions" style="padding: 0.35rem 0.65rem;"
                                                    data-name="{{ $row->parent_name }}"
                                                    data-manage-url="{{ route('manage.task.parent', ['parentType' => $row->parent_type, 'parentId' => $row->parent_id ?? 0]) }}"
                                                    data-delete-url="{{ route('manage.task.container.destroy', ['parentType' => $row->parent_type, 'parentId' => $row->parent_id ?? 0]) }}">
                                                <i class="ti ti-dots fs-2" style="font-size: 1.25rem; line-height: 1;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        @if ($scope === 'non_kp')
                                            No tasks found for offline trainings. <a href="{{ route('manage.task.create', ['scope' => 'non_kp', 'parent_type' => 'offline_training']) }}">Create a new task</a> to get started.
                                        @else
                                            No tasks yet. <a href="{{ route('manage.task.create') }}">Create a new task</a> to get started.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($parents->hasPages())
                    <div class="card-footer d-flex justify-content-center py-3 bg-white border-0">
                        {{ $parents->links() }}
                    </div>
                @endif

            </div>

            {{-- Global container actions menu (outside table to avoid clipping) --}}
            <div id="containerActionsMenu" class="dropdown-menu shadow-lg border rounded-3 py-1" style="display:none; position:fixed; z-index:1080; min-width:180px;">
                <span class="dropdown-header text-uppercase small fw-bold text-muted px-3 py-2">COURSE ACTIONS</span>
                <div id="containerActionsItems"></div>
            </div>

        </div>
    </div>


{{-- ======================================================== --}}
{{-- MODAL: CHOOSE / CREATE TASK CONTAINER                    --}}
{{-- ======================================================== --}}
<div class="modal modal-blur fade" id="chooseContainerModal" tabindex="-1" aria-labelledby="chooseContainerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content shadow-lg rounded-4 border-0 overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4 bg-white rounded-top-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary-lt text-primary flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="ti ti-folder-plus fs-1"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" id="chooseContainerModalLabel" style="font-size: 1.15rem; line-height: 1.3;">Choose Course / Activity</h4>
                        <div class="text-muted small mt-0.5" style="font-size: 0.825rem;">Select or create the course or activity where this task will be assigned.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                {{-- Step 1: Category selection dropdown --}}
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-2 d-flex align-items-center gap-2" for="modalCategorySelect">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 11px;">1</span>
                        <span>Select Category:</span>
                        <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted rounded-start-3 px-3">
                            <i class="ti ti-books fs-2 text-primary" id="modalCategoryIcon"></i>
                        </span>
                        <select id="modalCategorySelect" class="form-select form-select-lg border-start-0 rounded-end-3" style="font-size: 0.95rem;">
                            @if ($scope === 'kp')
                                <option value="playlist" selected>Playlist</option>
                                <option value="content">Content (All Content)</option>
                                <option value="content_playlist">Content Playlist</option>
                                <option value="standalone">Others</option>
                            @else
                                <option value="offline_training" selected>Offline Training</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-hint mt-2 text-muted d-flex align-items-center gap-1.5" id="modalCategoryHint" style="font-size: 0.8rem;">
                        <i class="ti ti-info-circle text-primary"></i>
                        <span>Tasks integrated within a complete online course.</span>
                    </div>
                </div>

                <hr class="my-3 text-muted opacity-25">

                {{-- Step 2: Dynamic input according to category --}}
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 11px;">2</span>
                        <span id="modalStep2Label">Select Course / Playlist:</span>
                        <span class="text-danger">*</span>
                    </label>
                </div>

                <div id="modalStep2Container">
                    <!-- Dropdown for Playlist -->
                    <div id="modalPlaylistPicker" class="modal-picker-section">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted rounded-start-3 px-3">
                                <i class="ti ti-books fs-2 text-primary"></i>
                            </span>
                            <select id="modal_playlist_id" class="form-select form-select-lg border-start-0 rounded-end-3" style="font-size: 0.95rem;">
                                <option value="">— Loading courses... —</option>
                            </select>
                        </div>
                        <div class="form-hint mt-2 text-muted d-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                            <i class="ti ti-info-circle text-primary"></i>
                            <span>Choose the course where this task will be assigned.</span>
                        </div>
                    </div>

                    <!-- Dropdown for Content (All Content) -->
                    <div id="modalContentPicker" class="modal-picker-section" style="display: none;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted rounded-start-3 px-3">
                                <i class="ti ti-file-text fs-2 text-info"></i>
                            </span>
                            <select id="modal_content_id" class="form-select form-select-lg border-start-0 rounded-end-3" style="font-size: 0.95rem;">
                                <option value="">— Loading content... —</option>
                            </select>
                        </div>
                        <div class="form-hint mt-2 text-muted d-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                            <i class="ti ti-info-circle text-info"></i>
                            <span>Choose the learning material from All Content where this task will be placed.</span>
                        </div>
                    </div>

                    <!-- Dropdown for Content Playlist -->
                    <div id="modalContentPlaylistPicker" class="modal-picker-section" style="display: none;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted rounded-start-3 px-3">
                                <i class="ti ti-playlist fs-2 text-azure"></i>
                            </span>
                            <select id="modal_content_playlist_id" class="form-select form-select-lg border-start-0 rounded-end-3" style="font-size: 0.95rem;">
                                <option value="">— Loading content playlist... —</option>
                            </select>
                        </div>
                        <div class="form-hint mt-2 text-muted d-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                            <i class="ti ti-info-circle text-azure"></i>
                            <span>Choose the content playlist where this task will be assigned.</span>
                        </div>
                    </div>

                    <!-- Dropdown for Offline Training -->
                    <div id="modalOfflinePicker" class="modal-picker-section" style="display: none;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted rounded-start-3 px-3">
                                <i class="ti ti-map-pin fs-2 text-warning"></i>
                            </span>
                            <select id="modal_offline_id" class="form-select form-select-lg border-start-0 rounded-end-3" style="font-size: 0.95rem;">
                                <option value="">— Loading offline training... —</option>
                            </select>
                        </div>
                        <div class="form-hint mt-2 text-muted d-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                            <i class="ti ti-info-circle text-warning"></i>
                            <span>Choose the assigned in-person training session.</span>
                        </div>
                    </div>

                                        <!-- Section for Others (Standalone) -->
                    <div id="modalOthersSection" class="modal-picker-section" style="display: none;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted rounded-start-3 px-3">
                                <i class="ti ti-folder-plus fs-2 text-purple"></i>
                            </span>
                            <input type="text"
                                   id="modal_others_title"
                                   class="form-control form-control-lg border-start-0 rounded-end-3"
                                   placeholder="e.g. Onboarding Batch 2026 / Safety Leadership Workshop"
                                   autocomplete="off"
                                   style="font-size: 0.95rem;">
                        </div>
                        <div class="form-hint mt-2 text-muted d-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                            <i class="ti ti-info-circle text-purple"></i>
                            <span>Enter the title for this activity. You will be directed to add tasks to it.</span>
                        </div>
                    </div>
                </div>


            </div>

            <div class="modal-footer border-top py-3 px-4 bg-light-subtle rounded-bottom-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-3 px-3.5" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" id="btnSubmitContainerModal" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2 fw-semibold shadow-sm">
                    <span id="btnSubmitText">Continue to Manage Tasks</span>
                    <i class="ti ti-arrow-right" id="btnSubmitIcon"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-pointer {
        cursor: pointer;
    }
    .container-type-card {
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        background-color: #ffffff;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        user-select: none;
    }
    .container-type-card:hover {
        border-color: #cbd5e1;
        background-color: #f8fafc;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .container-type-card.active-type-card {
        border-color: var(--tblr-primary, #206bc4) !important;
        background-color: rgba(32, 107, 196, 0.04) !important;
        box-shadow: 0 4px 14px rgba(32, 107, 196, 0.12);
    }
    
    #chooseContainerModal .input-group .form-control,
    #chooseContainerModal .input-group .form-select,
    #chooseContainerModal .input-group .input-group-text {
        height: 44px;
        min-height: 44px;
        font-size: 0.95rem;
    }
    #chooseContainerModal .input-group .form-control::placeholder {
        font-size: 0.95rem;
        color: #94a3b8;
    }
    .input-group:focus-within .input-group-text,
    .input-group:focus-within .form-control,
    .input-group:focus-within .form-select {
        border-color: var(--tblr-primary, #206bc4);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('chooseContainerModal');
    if (!modalEl) return;

    var categorySelect = document.getElementById('modalCategorySelect');
    var categoryIcon = document.getElementById('modalCategoryIcon');
    var categoryHint = document.getElementById('modalCategoryHint');
    var step2Label = document.getElementById('modalStep2Label');
    var playlistPicker = document.getElementById('modalPlaylistPicker');
    var contentPicker = document.getElementById('modalContentPicker');
    var contentPlaylistPicker = document.getElementById('modalContentPlaylistPicker');
    var offlinePicker = document.getElementById('modalOfflinePicker');
    var othersSection = document.getElementById('modalOthersSection');
    var btnSubmit = document.getElementById('btnSubmitContainerModal');
    var btnSubmitText = document.getElementById('btnSubmitText');
    var loadedCache = {};

    function markInvalid(element) {
        if (!element) return;
        element.classList.add('is-invalid');
        element.focus();
        var clear = function () {
            element.classList.remove('is-invalid');
            element.removeEventListener('input', clear);
            element.removeEventListener('change', clear);
        };
        element.addEventListener('input', clear);
        element.addEventListener('change', clear);
    }

    function clearAllInvalid() {
        ['modal_playlist_id', 'modal_content_id', 'modal_content_playlist_id', 'modal_offline_id', 'modal_others_title'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.classList.remove('is-invalid');
        });
    }

    function loadOptions(type, selectEl, placeholder) {
        if (!selectEl) return;
        if (loadedCache[type]) {
            renderOptions(loadedCache[type], selectEl, placeholder);
            return;
        }

        selectEl.innerHTML = '<option value="">— Loading options... —</option>';
        var url = "{{ url('manage/task/parents') }}/" + type;

        fetch(url)
            .then(function (res) { return res.json(); })
            .then(function (items) {
                loadedCache[type] = items;
                renderOptions(items, selectEl, placeholder);
            })
            .catch(function () {
                selectEl.innerHTML = '<option value="">Failed to load ' + type + ' list</option>';
            });
    }

    function renderOptions(items, selectEl, placeholder) {
        var html = '<option value="">' + placeholder + '</option>';
        if (Array.isArray(items) && items.length > 0) {
            items.forEach(function (item) {
                html += '<option value="' + item.id + '">' + (item.title || ('#' + item.id)) + '</option>';
            });
        } else {
            html += '<option value="" disabled>No existing items found</option>';
        }
        selectEl.innerHTML = html;
    }

    function onTypeChange() {
        clearAllInvalid();

        var val = categorySelect ? categorySelect.value : 'playlist';

        if (playlistPicker) playlistPicker.style.display = (val === 'playlist') ? 'block' : 'none';
        if (contentPicker) contentPicker.style.display = (val === 'content') ? 'block' : 'none';
        if (contentPlaylistPicker) contentPlaylistPicker.style.display = (val === 'content_playlist') ? 'block' : 'none';
        if (offlinePicker) offlinePicker.style.display = (val === 'offline_training') ? 'block' : 'none';
        if (othersSection) othersSection.style.display = (val === 'standalone') ? 'block' : 'none';

        if (val === 'playlist') {
            if (categoryIcon) categoryIcon.className = 'ti ti-books fs-2 text-primary';
            if (categoryHint) categoryHint.innerHTML = '<i class="ti ti-info-circle text-primary"></i><span>Tasks integrated within a complete online course.</span>';
            if (step2Label) step2Label.textContent = 'Select Course / Playlist:';
            if (btnSubmitText) btnSubmitText.textContent = 'Continue to Manage Tasks';
            loadOptions('playlist', document.getElementById('modal_playlist_id'), '— Select Course / Playlist —');
        } else if (val === 'content') {
            if (categoryIcon) categoryIcon.className = 'ti ti-file-text fs-2 text-info';
            if (categoryHint) categoryHint.innerHTML = '<i class="ti ti-info-circle text-info"></i><span>Tasks associated with learning materials from All Content.</span>';
            if (step2Label) step2Label.textContent = 'Select Content (All Content):';
            if (btnSubmitText) btnSubmitText.textContent = 'Continue to Manage Tasks';
            loadOptions('content', document.getElementById('modal_content_id'), '— Select Content / Material (All Content) —');
        } else if (val === 'content_playlist') {
            if (categoryIcon) categoryIcon.className = 'ti ti-playlist fs-2 text-azure';
            if (categoryHint) categoryHint.innerHTML = '<i class="ti ti-info-circle text-azure"></i><span>Tasks associated with contents that belong to playlists.</span>';
            if (step2Label) step2Label.textContent = 'Select Content Playlist:';
            if (btnSubmitText) btnSubmitText.textContent = 'Continue to Manage Tasks';
            loadOptions('content_playlist', document.getElementById('modal_content_playlist_id'), '— Select Content Playlist —');
        } else if (val === 'offline_training') {
            if (categoryIcon) categoryIcon.className = 'ti ti-map-pin fs-2 text-warning';
            if (categoryHint) categoryHint.innerHTML = '<i class="ti ti-info-circle text-warning"></i><span>Tasks for in-person training / in-class training sessions.</span>';
            if (step2Label) step2Label.textContent = 'Select Offline Training Session:';
            if (btnSubmitText) btnSubmitText.textContent = 'Continue to Manage Tasks';
            loadOptions('offline_training', document.getElementById('modal_offline_id'), '— Select Offline Training Session —');
        } else if (val === 'standalone') {
            if (categoryIcon) categoryIcon.className = 'ti ti-folders fs-2 text-purple';
            if (categoryHint) categoryHint.innerHTML = '<i class="ti ti-info-circle text-purple"></i><span>Standalone activities (Onboarding, Workshops, Events).</span>';
            if (step2Label) step2Label.textContent = 'Enter Activity Title:';
            if (btnSubmitText) btnSubmitText.textContent = 'Create & Manage Tasks';
            var titleInput = document.getElementById('modal_others_title');
            if (titleInput) {
                setTimeout(function () { titleInput.focus(); }, 100);
            }
        }
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', onTypeChange);
    }

    modalEl.addEventListener('show.bs.modal', function () {
        clearAllInvalid();
        onTypeChange();
    });

    if (btnSubmit) {
        btnSubmit.addEventListener('click', function () {
            clearAllInvalid();
            var type = categorySelect ? categorySelect.value : 'playlist';

            if (type === 'playlist') {
                var pSelect = document.getElementById('modal_playlist_id');
                if (!pSelect || !pSelect.value) {
                    markInvalid(pSelect);
                    return;
                }
                window.location.href = "{{ url('manage/task/parent/playlist') }}/" + pSelect.value;
            } else if (type === 'content') {
                var cSelect = document.getElementById('modal_content_id');
                if (!cSelect || !cSelect.value) {
                    markInvalid(cSelect);
                    return;
                }
                window.location.href = "{{ url('manage/task/parent/content') }}/" + cSelect.value;
            } else if (type === 'content_playlist') {
                var cpSelect = document.getElementById('modal_content_playlist_id');
                if (!cpSelect || !cpSelect.value) {
                    markInvalid(cpSelect);
                    return;
                }
                window.location.href = "{{ url('manage/task/parent/content') }}/" + cpSelect.value;
            } else if (type === 'offline_training') {
                var oSelect = document.getElementById('modal_offline_id');
                if (!oSelect || !oSelect.value) {
                    markInvalid(oSelect);
                    return;
                }
                window.location.href = "{{ url('manage/task/parent/offline_training') }}/" + oSelect.value;
            } else if (type === 'standalone') {
                var titleInput = document.getElementById('modal_others_title');
                var title = titleInput ? titleInput.value.trim() : '';
                if (!title) {
                    markInvalid(titleInput);
                    return;
                }

                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5" role="status"></span> Creating Activity...';

                fetch("{{ route('manage.task.group.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ title: title })
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        window.location.reload();
                    }
                })
                .catch(function () {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<span>Create & Manage Tasks</span><i class="ti ti-arrow-right ms-1"></i>';
                    showError('Failed to create task container. Please try again.');
                });
            }
        });
    }

    // Container action menu (3 dots)
    (function () {
        var menu = document.getElementById('containerActionsMenu');
        var items = document.getElementById('containerActionsItems');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '{{ csrf_token() }}';
        var openBtn = null;

        function closeMenu() {
            if (!menu) return;
            menu.style.display = 'none';
            openBtn = null;
            document.removeEventListener('click', outsideClick);
        }

        function outsideClick(e) {
            if (openBtn && !menu.contains(e.target) && !openBtn.contains(e.target)) {
                closeMenu();
            }
        }

        document.querySelectorAll('.container-menu-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (openBtn === btn) {
                    closeMenu();
                    return;
                }
                if (openBtn) closeMenu();
                openBtn = btn;

                var deleteUrl = btn.dataset.deleteUrl;
                var manageUrl = btn.dataset.manageUrl;
                var name = btn.dataset.name || 'this course';
                var cleanName = (name || 'this course').replace(/['"\\<>&]/g, ' ');

                var h = '';
                h += '<a href="' + manageUrl + '" class="dropdown-item d-flex align-items-center py-2 px-3 text-primary">';
                h += '<i class="ti ti-arrow-right me-2 text-primary fs-3"></i><span>Manage Tasks</span>';
                h += '</a>';

                h += '<div class="dropdown-divider my-1"></div>';

                h += '<form action="' + deleteUrl + '" method="POST" class="m-0" onsubmit="return confirm(\'Move ' + cleanName + ' to trash? You can restore it later from the Trash page.\')">';
                h += '<input type="hidden" name="_token" value="' + csrfToken + '">';
                h += '<input type="hidden" name="_method" value="DELETE">';
                h += '<button type="submit" class="dropdown-item d-flex align-items-center text-danger py-2 px-3">';
                h += '<i class="ti ti-trash me-2 text-danger fs-3"></i><span>Move to Trash</span>';
                h += '</button>';
                h += '</form>';

                items.innerHTML = h;

                var r = btn.getBoundingClientRect();
                menu.style.display = 'block';
                var mw = menu.offsetWidth, mh = menu.offsetHeight;
                var left = Math.min(r.right - mw, window.innerWidth - mw - 8);
                var top = r.bottom + 4;
                if (top + mh > window.innerHeight - 8) {
                    top = Math.max(8, r.top - mh - 4);
                }
                menu.style.left = Math.max(8, left) + 'px';
                menu.style.top = Math.max(8, top) + 'px';

                setTimeout(function () {
                    document.addEventListener('click', outsideClick);
                }, 0);
            });
        });

        window.addEventListener('resize', closeMenu);
        window.addEventListener('scroll', closeMenu, true);
    })();
});
</script>
@endsection

@section('style')
<style>
    .manage-task-page {
        background: #fff;
    }

    [data-bs-theme="dark"] .manage-task-page,
    body.dark-mode .manage-task-page,
    body.theme-dark .manage-task-page {
        background: var(--tblr-body-bg, #111827);
    }

    .btn-back {
        color: #0c5b9e !important;
        font-size: 0.95rem;
        transition: color 0.15s ease, transform 0.15s ease;
    }
    .btn-back:hover {
        color: #094475 !important;
        transform: translateX(-2px);
    }

    .manage-record-hero {
        display: flex;
        align-items: center;
        position: relative;
        isolation: isolate;
        min-height: 160px;
        overflow: hidden;
        padding: clamp(24px, 3vw, 36px);
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 20px;
        background:
            radial-gradient(circle at 86% 16%, rgba(255, 255, 255, .18) 0%, transparent 28%),
            linear-gradient(
                135deg,
                #001b44 0%,
                #002b66 22%,
                #004b99 50%,
                #2a7bd1 76%,
                #6aaed6 100%
            );
        color: #fff;
        box-shadow: 0 14px 32px rgba(0, 27, 68, .16);
    }

    .manage-record-hero__content {
        min-width: 0;
        max-width: 800px;
    }

    .manage-record-hero__badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 12px;
        padding: 6px 12px;
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: 999px;
        background: rgba(255, 255, 255, .14);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .manage-record-hero__title {
        margin: 0 0 10px;
        color: #fff;
        font-size: clamp(26px, 3vw, 34px);
        font-weight: 800;
        line-height: 1.18;
        letter-spacing: -.02em;
    }

    .manage-record-hero__description {
        max-width: 720px;
        color: rgba(255, 255, 255, .88);
        font-size: 14px;
        line-height: 1.6;
    }

    .stat-kpi-card {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
        transition: all 0.2s ease;
    }
    .stat-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }

    .record-section-header {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
    }

    /* =========================================================
       SECTION LABELS (SUMMARY & TASK DATA SCOPE)
       ========================================================= */
    .summary-section-label {
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        color: #8da2b5;
    }

    /* =========================================================
       SUMMARY KPI BAR (Unified Light Container with Dividers)
       ========================================================= */
    .summary-kpi-bar {
        background-color: #f0f4f9;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px 18px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }

    .summary-kpi-col {
        padding: 8px 16px;
        border-right: 1px solid #e2e8f0;
    }

    .summary-kpi-col--last {
        border-right: none;
    }

    @media (max-width: 991.98px) {
        .summary-kpi-col:nth-child(2) {
            border-right: none;
        }
        .summary-kpi-col:nth-child(1),
        .summary-kpi-col:nth-child(2) {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 8px;
        }
    }

    @media (max-width: 575.98px) {
        .summary-kpi-col {
            border-right: none !important;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 8px;
        }
        .summary-kpi-col:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
    }

    .summary-kpi-icon {
        border-radius: 12px !important;
    }

    /* =========================================================
       TASK DATA SCOPE CARDS (Compact Matching Reference)
       ========================================================= */
    .scope-nav-card {
        border-radius: 14px !important;
        background: #ffffff !important;
        border: 1.5px solid #e2e8f0 !important;
        max-width: 440px;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease !important;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    /* Active State: Clean Card with Crisp Navy Border (Exact Match to Reference) */
    .scope-nav-card--active,
    .scope-nav-card--kp-active,
    .scope-nav-card--non-kp-active {
        background: #ffffff !important;
        border: 2px solid #0c4b81 !important;
        box-shadow: 0 4px 14px rgba(12, 75, 129, 0.08) !important;
    }

    /* Inactive State Hover */
    .scope-nav-card--inactive {
        background: #ffffff !important;
        border: 1.5px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02) !important;
    }

    .scope-nav-card--inactive:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1 !important;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06) !important;
    }

    /* Top Row: Icon Containers (Compact Squircle) */
    .scope-nav-icon {
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        transition: all 0.2s ease;
    }

    .scope-nav-icon--kp {
        background: #e9f2f9 !important;
        color: #0c4b81 !important;
    }

    .scope-nav-icon--non-kp {
        background: #fbf0dd !important;
        color: #d97706 !important;
    }

    /* ACTIVE Pill Badge (Compact) */
    .scope-nav-badge-active {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2.5px 11px;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        background-color: #0c4b81 !important;
        color: #ffffff !important;
        line-height: 1.35;
    }

    /* Bottom Section: Typography */
    .scope-nav-title {
        color: #0f172a !important;
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: -0.01em;
        line-height: 1.25;
    }

    .text-blue-primary {
        color: #0c5b9e !important;
        font-size: 0.8rem;
    }

    .text-amber-primary {
        color: #d97706 !important;
        font-size: 0.8rem;
    }

    .scope-nav-desc {
        color: #64748b !important;
        font-size: 0.775rem;
        line-height: 1.35;
    }

    /* Legacy record-nav-card compatibility */
    .record-nav-card--active {
        border-color: #0c4b81 !important;
    }

    /* Dark Mode Support */
    [data-bs-theme="dark"] .summary-kpi-bar,
    body.dark-mode .summary-kpi-bar,
    body.theme-dark .summary-kpi-bar {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] .summary-kpi-col,
    body.dark-mode .summary-kpi-col,
    body.theme-dark .summary-kpi-col {
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] .scope-nav-card,
    body.dark-mode .scope-nav-card,
    body.theme-dark .scope-nav-card {
        background: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25) !important;
    }

    [data-bs-theme="dark"] .scope-nav-card--active,
    body.dark-mode .scope-nav-card--active,
    body.theme-dark .scope-nav-card--active {
        background: #1e293b !important;
        border-color: #38bdf8 !important;
        box-shadow: 0 4px 18px rgba(56, 189, 248, 0.18) !important;
    }

    [data-bs-theme="dark"] .scope-nav-badge-active,
    body.dark-mode .scope-nav-badge-active,
    body.theme-dark .scope-nav-badge-active {
        background-color: #38bdf8 !important;
        color: #0f172a !important;
    }

    [data-bs-theme="dark"] .scope-nav-icon--kp,
    body.dark-mode .scope-nav-icon--kp,
    body.theme-dark .scope-nav-icon--kp {
        background: rgba(56, 189, 248, 0.15) !important;
        color: #38bdf8 !important;
    }

    [data-bs-theme="dark"] .scope-nav-icon--non-kp,
    body.dark-mode .scope-nav-icon--non-kp,
    body.theme-dark .scope-nav-icon--non-kp {
        background: rgba(251, 191, 36, 0.15) !important;
        color: #fbbf24 !important;
    }

    [data-bs-theme="dark"] .scope-nav-title,
    body.dark-mode .scope-nav-title,
    body.theme-dark .scope-nav-title {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .scope-nav-desc,
    body.dark-mode .scope-nav-desc,
    body.theme-dark .scope-nav-desc {
        color: #94a3b8 !important;
    }

    /* =========================================================
       QUICK FILTER (MATCHING USER MY-TASK DESIGN)
       ========================================================= */
    .my-task-pills-row {
        margin-top: 0.25rem;
    }

    .my-task-category-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.45rem 1.25rem;
        border-radius: 50rem;
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.2;
        text-decoration: none;
        white-space: nowrap;
        background-color: #f1f5f9;
        color: #334155;
        border: none;
        transition: all 0.15s ease-in-out;
    }

    .my-task-category-pill:hover {
        background-color: #e2e8f0;
        color: #0f172a;
        text-decoration: none;
    }

    /* Active State: Soft sky blue with bright blue text (1:1 with My Task reference) */
    .my-task-category-pill.active {
        background-color: #e0f2fe !important;
        color: #0284c7 !important;
        font-weight: 600;
    }

    /* Divider */
    .my-task-divider {
        border-top: 1px solid #e2e8f0;
        opacity: 0.7;
        margin: 1rem 0;
    }

    /* Search input */
    .my-task-search-wrapper {
        position: relative;
    }

    .my-task-search-input {
        height: 38px;
        border-radius: 8px !important;
        padding-left: 2.25rem;
        padding-right: 2rem;
        font-size: 0.8125rem;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        color: #334155;
        transition: all 0.15s ease-in-out;
    }

    .my-task-search-input:focus {
        background-color: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
        color: #0f172a;
    }

    .my-task-search-input::placeholder {
        color: #94a3b8;
    }

    .my-task-search-clear {
        color: #94a3b8 !important;
        padding: 0 8px;
    }

    .my-task-search-clear:hover {
        color: #475569 !important;
    }

    /* Square Action Buttons (38px x 38px, rounded 8px) */
    .my-task-btn-square {
        width: 38px;
        height: 38px;
        min-width: 38px;
        padding: 0;
        border-radius: 8px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: none !important;
        transition: all 0.15s ease-in-out;
    }

    .my-task-btn-search {
        background-color: #0054a6 !important;
        border-color: #0054a6 !important;
        color: #ffffff !important;
    }

    .my-task-btn-search:hover {
        background-color: #004085 !important;
        border-color: #004085 !important;
    }

    .my-task-btn-reset {
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        color: #64748b !important;
    }

    .my-task-btn-reset:hover {
        background-color: #f8fafc !important;
        border-color: #cbd5e1 !important;
        color: #334155 !important;
    }

    /* Dark Mode Support for Quick Filter */
    [data-bs-theme="dark"] .my-task-category-pill,
    body.dark-mode .my-task-category-pill,
    body.theme-dark .my-task-category-pill {
        background-color: #334155 !important;
        color: #cbd5e1 !important;
    }

    [data-bs-theme="dark"] .my-task-category-pill:hover,
    body.dark-mode .my-task-category-pill:hover,
    body.theme-dark .my-task-category-pill:hover {
        background-color: #475569 !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .my-task-category-pill.active,
    body.dark-mode .my-task-category-pill.active,
    body.theme-dark .my-task-category-pill.active {
        background-color: rgba(56, 189, 248, 0.15) !important;
        color: #38bdf8 !important;
    }

    [data-bs-theme="dark"] .my-task-divider,
    body.dark-mode .my-task-divider,
    body.theme-dark .my-task-divider {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .my-task-search-input,
    body.dark-mode .my-task-search-input,
    body.theme-dark .my-task-search-input {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .my-task-btn-reset,
    body.dark-mode .my-task-btn-reset,
    body.theme-dark .my-task-btn-reset {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #cbd5e1 !important;
    }
</style>
@endsection
