@extends('layouts.sidebar-master') {{-- ⚠️ SESUAIKAN --}}

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="mb-2">
                @if (!empty($lockedParent))
                    <a href="{{ route('manage.task.parent', ['parentType' => $lockedParent['type'], 'parentId' => $lockedParent['id']]) }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                        <i class="ti ti-arrow-left"></i> Back to {{ $lockedParent['name'] }}
                    </a>
                @else
                    <a href="{{ !empty($task->parentRouteParams()) ? route('manage.task.parent', $task->parentRouteParams()) : route('manage.task.index') }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                        <i class="ti ti-arrow-left"></i> Back
                    </a>
                @endif
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index') }}" class="text-muted text-decoration-none">Task Management</a>
                        @if (!empty($lockedParent))
                            <span class="text-muted mx-1">/</span>
                            <a href="{{ route('manage.task.parent', ['parentType' => $lockedParent['type'], 'parentId' => $lockedParent['id']]) }}" class="text-muted text-decoration-none">{{ $lockedParent['name'] }}</a>
                        @endif
                    </div>
                    <h2 class="page-title d-flex align-items-center gap-2 flex-wrap">
                        <span>Edit Task</span>
                        @if (!empty($lockedParent))
                            <span class="badge bg-primary-lt text-primary fs-5 fw-semibold px-2.5 py-1 rounded-pill">
                                <i class="ti ti-folder me-1"></i>{{ $lockedParent['name'] }}
                            </span>
                        @endif
                        @if ($task->status === 'draft')
                            <span class="badge bg-warning-lt text-warning fs-6 fw-semibold px-2.5 py-0.5 rounded-pill">
                                Draft Mode
                            </span>
                        @endif
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3 rounded-3" role="alert">
                    <i class="ti ti-circle-check fs-2 text-success"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3 rounded-3" role="alert">
                    <i class="ti ti-alert-circle fs-2 text-danger"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @include('manage.task._form')
        </div>
    </div>
@endsection