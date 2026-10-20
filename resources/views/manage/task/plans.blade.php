{{-- resources/views/manage/task/plans.blade.php --}}
{{-- Dashboard: parents (playlist / content / standalone) that have tasks. --}}
@extends('layouts.sidebar-master')

@section('content')

    <style>
        .tm-back { display:inline-flex; align-items:center; gap:.35rem; color:#206bc4; font-weight:600; font-size:.9rem; text-decoration:none; margin-bottom:.75rem; }
        .tm-back:hover { color:#185294; text-decoration:underline; }
        .tm-hero { background:linear-gradient(120deg,#0a2a4d 0%,#123f73 55%,#2f7cd6 100%); border-radius:1rem; padding:2rem; color:#fff; margin-bottom:1.25rem; box-shadow:0 .5rem 1.5rem rgba(10,42,77,.25); }
        .tm-hero-pill { display:inline-flex; align-items:center; gap:.4rem; font-size:.72rem; font-weight:700; letter-spacing:.04em; background:rgba(0,0,0,.28); border-radius:999px; padding:.3rem .85rem; margin-bottom:.9rem; color:#fff; }
        .tm-hero h1 { color:#fff; font-size:1.75rem; font-weight:800; margin:0 0 .5rem; }
        .tm-hero p { color:rgba(255,255,255,.85); margin:0; max-width:760px; }
        .tm-filter-card { display:block; background:#fff; border:1px solid #e6e8eb; border-radius:.75rem; padding:.9rem 1rem; text-decoration:none; color:inherit; position:relative; height:100%; transition:border-color .15s, box-shadow .15s; }
        .tm-filter-card:hover { border-color:#206bc4; box-shadow:0 .25rem .75rem rgba(32,107,196,.12); text-decoration:none; color:inherit; }
        .tm-filter-card.active { border:2px solid #206bc4; padding:calc(.9rem - 1px) calc(1rem - 1px); }
        .tm-filter-icon { width:38px; height:38px; border-radius:.55rem; display:inline-flex; align-items:center; justify-content:center; font-size:1.1rem; margin-bottom:.55rem; }
        .tm-filter-title { font-weight:700; font-size:.95rem; color:#1a1a1a; margin-bottom:.1rem; }
        .tm-filter-sub { font-size:.78rem; font-weight:600; margin-bottom:.2rem; }
        .tm-filter-desc { font-size:.76rem; color:#6b7280; line-height:1.35; }
        .tm-active-badge { position:absolute; top:.8rem; right:.8rem; background:#0a2a4d; color:#fff; font-size:.65rem; font-weight:700; letter-spacing:.05em; border-radius:999px; padding:.2rem .6rem; }
        .tm-stat-icon { width:48px; height:48px; border-radius:.65rem; display:inline-flex; align-items:center; justify-content:center; font-size:1.4rem; color:#fff; flex-shrink:0; }
        .tm-stat-label { font-size:.82rem; color:#6b7280; margin-bottom:.15rem; }
        .tm-stat-number { font-size:1.45rem; font-weight:800; color:#1a1a1a; line-height:1.2; }
        .tm-scope-btn { border-radius:999px; font-weight:600; font-size:.85rem; padding:.45rem 1.1rem; }
    </style>

    <div class="page-body">
        <div class="container-xl">

            <a href="{{ route('manage.index') }}" class="tm-back">
                <i class="ti ti-arrow-left"></i> Back
            </a>

            {{-- ===== Hero ===== --}}
            <div class="tm-hero">
                <span class="tm-hero-pill"><i class="ti ti-clipboard-list"></i> TASK MANAGEMENT</span>
                <h1>Task Management</h1>
                <p>Manage tasks across playlists, contents, and standalone tasks, including placement data, task progress, and review status.</p>
            </div>

            <x-alert type="success" />

            {{-- ===== Scope cards: KP OWL / NON KP OWL ===== --}}
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4 mb-3">
                    <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => $type, 'search' => $search]) }}"
                       class="tm-filter-card {{ $scope === 'kp' ? 'active' : '' }}">
                        @if ($scope === 'kp')
                            <span class="tm-active-badge">ACTIVE</span>
                        @endif
                        <div class="tm-filter-icon bg-blue-lt text-blue">
                            <i class="ti ti-device-laptop"></i>
                        </div>
                        <div class="tm-filter-title">KP OWL</div>
                        <div class="tm-filter-sub text-blue">Online Task Data</div>
                        <div class="tm-filter-desc">Tasks from playlists, contents &amp; standalone</div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <a href="{{ route('manage.task.index', ['scope' => 'non_kp', 'search' => $search]) }}"
                       class="tm-filter-card {{ $scope === 'non_kp' ? 'active' : '' }}">
                        @if ($scope === 'non_kp')
                            <span class="tm-active-badge">ACTIVE</span>
                        @endif
                        <div class="tm-filter-icon bg-orange-lt text-orange">
                            <i class="ti ti-map-pin"></i>
                        </div>
                        <div class="tm-filter-title">NON KP OWL</div>
                        <div class="tm-filter-sub text-orange">Offline Training Tasks</div>
                        <div class="tm-filter-desc">Tasks from offline trainings</div>
                    </a>
                </div>
            </div>

            {{-- ===== Stat cards ===== --}}
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="tm-stat-icon bg-primary">
                                    <i class="ti ti-folder"></i>
                                </div>
                                <div>
                                    <div class="tm-stat-label">Placements With Tasks</div>
                                    <div class="tm-stat-number">{{ $stats['parents'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="tm-stat-icon bg-info">
                                    <i class="ti ti-clipboard-list"></i>
                                </div>
                                <div>
                                    <div class="tm-stat-label">Total Tasks</div>
                                    <div class="tm-stat-number">{{ $stats['tasks'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="tm-stat-icon bg-success">
                                    <i class="ti ti-circle-check"></i>
                                </div>
                                <div>
                                    <div class="tm-stat-label">Published</div>
                                    <div class="tm-stat-number">{{ $stats['published'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="tm-stat-icon bg-warning">
                                    <i class="ti ti-clock"></i>
                                </div>
                                <div>
                                    <div class="tm-stat-label">Pending Review</div>
                                    <div class="tm-stat-number">{{ $stats['pending'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Table ===== --}}
            <div class="card">
                <div class="card-header bg-white border-0">
                    <div>
                        <h3 class="card-title mb-1">Task Placement</h3>
                        <div class="text-muted small">
                            Only playlists, contents, or standalone tasks that have tasks are listed.
                        </div>
                    </div>
                    <div class="card-actions d-flex gap-2">
                        <a href="{{ route('manage.task.trash') }}" class="btn btn-outline-secondary position-relative">
                            <i class="ti ti-trash me-1"></i>Trash
                            @if (($trashCount ?? 0) > 0)
                                <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('manage.task.create', array_filter(['scope' => $scope, 'parent_type' => $scope === 'non_kp' ? 'offline_training' : null])) }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>Create New Task
                        </a>
                    </div>
                </div>

                <div class="card-body border-top border-bottom py-3">
                    @if ($scope === 'kp')
                        <div class="small text-muted mb-2">Quick Filter</div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'all', 'search' => $search]) }}"
                               class="btn {{ $type === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="ti ti-layout-grid me-1"></i>All
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'playlist', 'search' => $search]) }}"
                               class="btn {{ $type === 'playlist' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="ti ti-list me-1"></i>Playlist
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'content', 'search' => $search]) }}"
                               class="btn {{ $type === 'content' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="ti ti-file-text me-1"></i>Content
                            </a>
                            <a href="{{ route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'standalone', 'search' => $search]) }}"
                               class="btn {{ $type === 'standalone' ? 'btn-primary' : 'btn-outline-secondary' }}">
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
                                           class="btn btn-outline-primary">
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
                    <div class="card-footer d-flex justify-content-center py-3">
                        {{ $parents->links() }}
                    </div>
                @endif

            </div>

        </div>
    </div>

@endsection
