{{-- resources/views/manage/task/plans.blade.php --}}
{{-- Dashboard: parents (playlist / content / offline training) that have tasks.
     Only parents with at least one task are listed. --}}
@extends('layouts.sidebar-master')

@section('content')

    @include('main.assesment.partials.style')

    {{-- ===== Header ala Assessment Management ===== --}}
    <div class="assessment-page-header page-header d-print-none mb-3">
        <div class="container-xl">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="assessment-back">
                    <a href="{{ route('manage.index') }}" class="assessment-back-link">
                        <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                        Back
                    </a>
                </div>
                <div class="assessment-breadcrumb">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item"><a href="{{ route('manage.index') }}">Manage</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Task</li>
                    </ol>
                </div>
            </div>

            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle text-muted">
                        Learning Management System
                    </div>
                    <h2 class="owl-page-title mb-1">
                        Task Management
                    </h2>
                    <div class="owl-page-subtitle">
                        Manage tasks by playlist, content, or offline training.
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <a href="{{ route('manage.task.create') }}"
                           class="btn btn-primary d-inline-flex align-items-center">
                            <i class="fas fa-plus me-2"></i>
                            <span>Create New Task</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            <x-alert type="success" />

            {{-- ===== Stat cards ===== --}}
            <div class="row mb-4">

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card owl-stat-card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="owl-stat-icon bg-primary">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="owl-stat-content">
                                    <div class="owl-stat-label">Parents With Tasks</div>
                                    <div class="owl-stat-number">{{ $stats['parents'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card owl-stat-card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="owl-stat-icon bg-info">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="owl-stat-content">
                                    <div class="owl-stat-label">Total Task</div>
                                    <div class="owl-stat-number">{{ $stats['tasks'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card owl-stat-card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="owl-stat-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="owl-stat-content">
                                    <div class="owl-stat-label">Published</div>
                                    <div class="owl-stat-number">{{ $stats['published'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card owl-stat-card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="owl-stat-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="owl-stat-content">
                                    <div class="owl-stat-label">Pending Review</div>
                                    <div class="owl-stat-number">{{ $stats['pending'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ===== Tabel induk bertask ===== --}}
            <div class="card owl-main-card">

                <div class="card-header bg-white border-0">
                    <div>
                        <h3 class="card-title mb-1">
                            Task Parents
                        </h3>
                        <div class="text-muted small">
                            Only playlists, contents, or offline trainings that have tasks are listed.
                        </div>
                    </div>
                </div>

                <div class="card-body border-top border-bottom py-3">
                    <div class="small text-muted mb-2">Quick Filter</div>

                    {{-- Level 1: KP OWL / NON KP OWL --}}
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => $type, 'search' => $search]) }}"
                           class="btn {{ $scope === 'kp' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            <i class="ti ti-device-laptop me-1"></i>KP OWL
                        </a>
                        <a href="{{ route('manage.task.index', ['scope' => 'non_kp', 'search' => $search]) }}"
                           class="btn {{ $scope === 'non_kp' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            <i class="ti ti-map-pin me-1"></i>NON KP OWL
                        </a>
                    </div>

                    {{-- Level 2: Content / Playlist (KP OWL only) --}}
                    @if ($scope === 'kp')
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'content', 'search' => $search]) }}"
                               class="btn {{ $type === 'content' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="ti ti-file-text me-1"></i>Content
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'playlist', 'search' => $search]) }}"
                               class="btn {{ $type === 'playlist' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="ti ti-list me-1"></i>Playlist
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
                                       id="taskParentSearch"
                                       class="form-control"
                                       value="{{ $search }}"
                                       placeholder="{{ $scope === 'kp' ? 'Search playlist / content...' : 'Search offline training...' }}"
                                       autocomplete="off">
                            </div>
                            <button type="submit" class="btn btn-primary" title="Search" aria-label="Search">
                                <i class="ti ti-search"></i>
                            </button>
                            <a href="{{ route('manage.task.index', array_filter(['scope' => $scope, 'parent_type' => $scope === 'kp' ? $type : null])) }}"
                               class="btn btn-outline-secondary" title="Reset" aria-label="Reset">
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
                                <th>PARENT</th>
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
                                        ? 'Offline Training' . ($row->parent_id ? " #{$row->parent_id}" : '')
                                        : ($row->parent_name ?? '—');
                                    $badge = [
                                        'playlist' => 'bg-primary',
                                        'content' => 'bg-info',
                                        'offline_training' => 'bg-warning',
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
                                        <a href="{{ route('manage.task.parent', ['parentType' => $row->parent_type, 'parentId' => $row->parent_id]) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Manage Tasks <i class="ti ti-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        @if ($scope === 'non_kp')
                                            Offline training data is not available yet.
                                        @else
                                            No tasks yet. Create a new task to get started.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($parents->hasPages())
                    <div class="card-footer d-flex justify-content-center py-3">
                        {{ $parents->links() }}
                    </div>
                @endif

            </div>

        </div>
    </div>

@endsection
