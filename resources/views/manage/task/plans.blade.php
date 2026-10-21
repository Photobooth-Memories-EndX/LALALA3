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

            {{-- ===== 4 KPI SUMMARY CARDS (DIRECTLY UNDER HERO) ===== --}}
            <div class="row g-3 mb-4">
                <!-- Card 1: Placements With Tasks -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                                <i class="ti ti-stack-2 fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Placements With Tasks</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['parents']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Total Tasks -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #f59e0b; color: #ffffff;">
                                <i class="ti ti-progress fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Tasks</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['tasks']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Published -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #38b000; color: #ffffff;">
                                <i class="ti ti-circle-check fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Published</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['published']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Pending Review -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                                <i class="ti ti-clock fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Pending Review</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['pending']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== SECTION HEADER: ACTIVE SCOPE INFO & ACTION BUTTONS ===== --}}
            <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                             style="width: 54px; height: 54px; background-color: {{ $scope === 'kp' ? '#e0f2fe; color: #0284c7;' : '#fef3c7; color: #d97706;' }}; border-radius: 12px;">
                            <i class="ti {{ $scope === 'kp' ? 'ti-device-laptop' : 'ti-map-pin' }} fs-1"></i>
                        </div>
                        <div>
                            <h2 class="h3 fw-bold mb-1 text-dark">{{ $scope === 'kp' ? 'KP OWL' : 'NON KP OWL' }}</h2>
                            <p class="text-muted small mb-0">
                                {{ $scope === 'kp' ? 'Online Task Data — Tasks from playlists, contents & standalone in OWL LMS.' : 'Offline Training Tasks — Tasks from offline trainings in OWL LMS.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons: Trash / Create New Task -->
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('manage.task.trash') }}" class="btn btn-outline-secondary position-relative rounded-3 px-3 d-inline-flex align-items-center gap-1.5">
                            <i class="ti ti-trash fs-2"></i>
                            <span>Trash</span>
                            @if (($trashCount ?? 0) > 0)
                                <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('manage.task.create', array_filter(['scope' => $scope, 'parent_type' => $scope === 'non_kp' ? 'offline_training' : null])) }}"
                           class="btn btn-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5">
                            <i class="ti ti-plus fs-2"></i>
                            <span>Create New Task</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- ===== SCOPE NAVIGATION CARDS ===== --}}
            <div class="row g-3 mb-4 record-navigation-row">
                <!-- Card 1: KP OWL -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => $type, 'search' => $search]) }}"
                        class="card card-link text-decoration-none h-100 record-nav-card {{ $scope === 'kp' ? 'record-nav-card--active' : '' }}">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="avatar avatar-sm rounded-3 record-nav-icon"
                                        style="background-color: #e0f2fe; color: #0284c7;">
                                        <i class="ti ti-device-laptop"></i>
                                    </span>
                                    @if ($scope === 'kp')
                                        <span class="badge bg-primary text-white rounded-pill px-2.5 py-1"
                                            style="font-size: 0.725rem; font-weight: 600;">ACTIVE</span>
                                    @endif
                                </div>
                                <h3 class="card-title fw-bold mb-1" style="font-size: 1.05rem;">KP OWL</h3>
                                <div class="record-nav-subtitle fw-semibold small mb-1" style="color: #0c5b9e;">Online Task Data</div>
                            </div>
                            <p class="text-muted small mb-0 mt-2" style="font-size: 0.8rem; line-height: 1.35;">Tasks from playlists, contents &amp; standalone</p>
                        </div>
                    </a>
                </div>

                <!-- Card 2: Non KP OWL -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="{{ route('manage.task.index', ['scope' => 'non_kp', 'search' => $search]) }}"
                        class="card card-link text-decoration-none h-100 record-nav-card {{ $scope === 'non_kp' ? 'record-nav-card--active' : '' }}">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="avatar avatar-sm rounded-3 record-nav-icon"
                                        style="background-color: #fef3c7; color: #d97706;">
                                        <i class="ti ti-map-pin"></i>
                                    </span>
                                    @if ($scope === 'non_kp')
                                        <span class="badge bg-primary text-white rounded-pill px-2.5 py-1"
                                            style="font-size: 0.725rem; font-weight: 600;">ACTIVE</span>
                                    @endif
                                </div>
                                <h3 class="card-title fw-bold mb-1" style="font-size: 1.05rem;">Non KP OWL</h3>
                                <div class="record-nav-subtitle fw-semibold small mb-1" style="color: #b45309;">Offline Training Tasks</div>
                            </div>
                            <p class="text-muted small mb-0 mt-2" style="font-size: 0.8rem; line-height: 1.35;">Tasks from offline trainings</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- ===== TABLE CARD ===== --}}
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-0 py-3">
                    <div>
                        <h3 class="card-title mb-1 fw-bold text-dark">Task Placement</h3>
                        <div class="text-muted small">
                            Only playlists, contents, offline trainings, or standalone tasks that have tasks are listed.
                        </div>
                    </div>
                </div>

                <div class="card-body border-top border-bottom py-3">
                    @if ($scope === 'kp')
                        <div class="small text-muted mb-2">Quick Filter</div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'all', 'search' => $search]) }}"
                               class="btn {{ $type === 'all' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill">
                                <i class="ti ti-layout-grid me-1"></i>All
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'playlist', 'search' => $search]) }}"
                               class="btn {{ $type === 'playlist' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill">
                                <i class="ti ti-list me-1"></i>Playlist
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'content', 'search' => $search]) }}"
                               class="btn {{ $type === 'content' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill">
                                <i class="ti ti-file-text me-1"></i>Content
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'standalone', 'search' => $search]) }}"
                               class="btn {{ $type === 'standalone' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill">
                                <i class="ti ti-world me-1"></i>Global Task
                            </a>
                        </div>
                    @endif
                    <form method="GET" action="{{ route('manage.task.index') }}">
                        <input type="hidden" name="scope" value="{{ $scope }}">
                        @if ($scope === 'kp')
                            <input type="hidden" name="parent_type" value="{{ $type }}">
                        @endif
                        <div class="d-flex gap-2">
                            <div class="input-icon flex-fill">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="search"
                                       name="search"
                                       class="form-control rounded-3"
                                       value="{{ $search }}"
                                       placeholder="{{ $scope === 'kp' ? 'Search playlist / content...' : 'Search offline training...' }}"
                                       autocomplete="off">
                            </div>
                            <button type="submit" class="btn btn-primary rounded-3 px-3" title="Search" aria-label="Search">
                                <i class="ti ti-search"></i>
                            </button>
                            <a href="{{ route('manage.task.index', array_filter(['scope' => $scope, 'parent_type' => $scope === 'kp' ? $type : null])) }}"
                               class="btn btn-outline-secondary rounded-3 px-3" title="Reset" aria-label="Reset">
                                <i class="ti ti-rotate-clockwise"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th class="w-1">NO</th>
                                <th>TYPE</th>
                                <th>PLACEMENT</th>
                                <th class="text-center">TOTAL TASK</th>
                                <th class="text-center">PUBLISHED</th>
                                <th class="text-center">DRAFT</th>
                                <th class="text-center">PENDING REVIEW</th>
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
                                            ? 'Global Task'
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
                                            <span class="badge bg-warning text-dark">{{ $pending }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('manage.task.parent', ['parentType' => $row->parent_type, 'parentId' => $row->parent_id ?? 0]) }}"
                                           class="btn btn-outline-primary rounded-3">
                                            Manage Tasks <i class="ti ti-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
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

        </div>
    </div>

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

    .record-nav-card {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    }

    .record-nav-card:hover {
        transform: translateY(-3px);
        border-color: #93c5fd !important;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    }

    .record-nav-card--active {
        border: 2px solid #0c5b9e !important;
        background: #f8fafc !important;
        box-shadow: 0 6px 18px rgba(12, 91, 158, 0.12) !important;
    }

    .record-nav-icon {
        width: 32px !important;
        height: 32px !important;
        min-width: 32px !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
    }

    .record-nav-icon i {
        font-size: 16px !important;
        line-height: 1 !important;
    }

    [data-bs-theme="dark"] .record-nav-card,
    body.dark-mode .record-nav-card,
    body.theme-dark .record-nav-card {
        background: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }

    [data-bs-theme="dark"] .record-nav-card:hover,
    body.dark-mode .record-nav-card:hover,
    body.theme-dark .record-nav-card:hover {
        border-color: #3b82f6 !important;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
    }

    [data-bs-theme="dark"] .record-nav-card--active,
    body.dark-mode .record-nav-card--active,
    body.theme-dark .record-nav-card--active {
        border: 2px solid #38bdf8 !important;
        background: rgba(14, 165, 233, 0.08) !important;
        box-shadow: 0 6px 18px rgba(56, 189, 248, 0.18) !important;
    }

    [data-bs-theme="dark"] .record-nav-card .card-title,
    body.dark-mode .record-nav-card .card-title,
    body.theme-dark .record-nav-card .card-title {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .record-nav-card .record-nav-subtitle,
    body.dark-mode .record-nav-card .record-nav-subtitle,
    body.theme-dark .record-nav-card .record-nav-subtitle {
        color: #93c5fd !important;
    }

    [data-bs-theme="dark"] .record-nav-card .record-nav-icon,
    body.dark-mode .record-nav-card .record-nav-icon,
    body.theme-dark .record-nav-card .record-nav-icon {
        filter: brightness(0.9);
    }
</style>
@endsection
