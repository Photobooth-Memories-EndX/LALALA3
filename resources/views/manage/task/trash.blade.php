{{-- resources/views/manage/task/trash.blade.php --}}
{{-- Unified Trash for Tasks & Courses: restore them or delete forever. --}}
@extends('layouts.sidebar-master')

@section('content')
    @php
        $items = $items ?? $tasks ?? collect();
        $type = $type ?? request('type', 'all');
        $search = $search ?? request('search', '');
        $taskTrashCount = $taskTrashCount ?? \App\Models\Task::onlyTrashed()->count();
        $courseTrashCount = $courseTrashCount ?? (
            \App\Models\TrainingPlan::onlyTrashed()->count()
            + \App\Models\Content::onlyTrashed()->count()
            + \App\Models\OfflineTraining::onlyTrashed()->count()
            + (\Illuminate\Support\Facades\Schema::hasColumn('task_groups', 'deleted_at') ? \App\Models\TaskGroup::onlyTrashed()->count() : 0)
        );
        $totalTrashCount = $totalTrashCount ?? ($taskTrashCount + $courseTrashCount);
    @endphp
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="mb-2">
                <a href="{{ route('manage.task.index') }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Back
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index') }}">Task Management</a>
                    </div>
                    <h2 class="page-title d-flex align-items-center gap-2">
                        <i class="ti ti-trash me-1"></i>Trash
                        @if (($totalTrashCount ?? 0) > 0)
                            <span class="badge bg-danger rounded-pill" style="font-size: 0.75rem;">{{ $totalTrashCount }}</span>
                        @endif
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ti ti-check me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm border-0 rounded-4">
                {{-- Filter Toolbar: Pills (All, Course, Task) + Search --}}
                <div class="card-body border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center flex-wrap gap-2 my-task-pills-row">
                            <a href="{{ route('manage.task.trash', ['type' => 'all', 'search' => $search]) }}"
                               class="my-task-category-pill {{ $type === 'all' ? 'active' : '' }}"
                               title="Show all deleted items">
                                All <span class="badge bg-secondary-lt ms-1">{{ $totalTrashCount }}</span>
                            </a>
                            <a href="{{ route('manage.task.trash', ['type' => 'course', 'search' => $search]) }}"
                               class="my-task-category-pill {{ $type === 'course' ? 'active' : '' }}"
                               title="Show deleted courses & activities">
                                Course <span class="badge bg-secondary-lt ms-1">{{ $courseTrashCount }}</span>
                            </a>
                            <a href="{{ route('manage.task.trash', ['type' => 'task', 'search' => $search]) }}"
                               class="my-task-category-pill {{ $type === 'task' ? 'active' : '' }}"
                               title="Show deleted tasks">
                                Task <span class="badge bg-secondary-lt ms-1">{{ $taskTrashCount }}</span>
                            </a>
                        </div>

                        <form method="GET" action="{{ route('manage.task.trash') }}" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="type" value="{{ $type }}">
                            <div class="position-relative" style="min-width: 220px;">
                                <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted pointer-events-none" style="z-index: 2;">
                                    <i class="ti ti-search" style="font-size: 15px;"></i>
                                </span>
                                <input type="text"
                                       name="search"
                                       class="form-control rounded-3 ps-5 my-task-search-input"
                                       placeholder="Search trash..."
                                       value="{{ $search }}">
                            </div>
                            @if ($search !== '')
                                <a href="{{ route('manage.task.trash', ['type' => $type]) }}" class="btn btn-outline-secondary rounded-3 px-3" title="Clear Search">
                                    <i class="ti ti-x"></i>
                                </a>
                            @endif
                            <button type="submit" class="btn btn-outline-primary rounded-3 px-3">
                                <i class="ti ti-search me-1"></i>Search
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Trash Table --}}
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr class="text-uppercase small fw-bold text-muted bg-light">
                                <th class="w-1">NO</th>
                                <th>ITEM / TITLE</th>
                                <th>TYPE</th>
                                <th>CATEGORY / PLACEMENT</th>
                                <th>DELETED AT</th>
                                <th class="text-end">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $i => $item)
                                @php
                                    $itemTitle = $item->title ?? 'Untitled';
                                    $itemType = $item->item_type ?? ($item instanceof \App\Models\Task ? 'task' : 'course');
                                    $itemCategory = $item->category ?? ($item instanceof \App\Models\Task ? (\App\Models\Task::PARENT_TYPES[$item->parent_type] ?? $item->parent_type) : 'Course');
                                    $itemPlacement = $item->placement ?? ($item instanceof \App\Models\Task ? $item->parentName() : '');
                                    $itemBadgeClass = $item->badge_class ?? ($itemType === 'course' ? 'bg-purple-lt text-purple' : 'bg-primary-lt text-primary');
                                    $itemRestoreUrl = $item->restore_url ?? route('manage.task.restore', ['id' => $item->id, 'item_type' => $itemType]);
                                    $itemDeleteUrl = $item->delete_url ?? route('manage.task.force-destroy', ['id' => $item->id, 'item_type' => $itemType]);
                                    $itemConfirmMsg = $item->confirm_msg ?? 'Permanently delete this item? This action cannot be undone.';
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ ($items->firstItem() ?? 0) + $i }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $itemTitle }}</div>
                                        @if ($itemType === 'course')
                                            <div class="text-muted small">Course Container &bull; {{ $itemCategory }}</div>
                                        @else
                                            <div class="text-muted small">Task &bull; {{ $item->task_type_label ?? 'Task' }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($itemType === 'course')
                                            <span class="badge bg-purple-lt text-purple fw-semibold">
                                                <i class="ti ti-books me-1"></i>Course
                                            </span>
                                        @else
                                            <span class="badge bg-primary-lt text-primary fw-semibold">
                                                <i class="ti ti-clipboard-check me-1"></i>Task
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $itemBadgeClass }}">
                                            {{ $itemCategory }}
                                        </span>
                                        @if ($itemPlacement)
                                            <span class="text-muted small ms-1">{{ $itemPlacement }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted text-nowrap" style="font-size: 0.85rem;">
                                        {{ $item->deleted_at ? \Carbon\Carbon::parse($item->deleted_at)->format('d M Y H:i') : '-' }}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-outline-secondary trash-menu-btn" title="Actions" style="padding: 0.35rem 0.65rem;"
                                                data-restore-url="{{ $itemRestoreUrl }}"
                                                data-delete-url="{{ $itemDeleteUrl }}"
                                                data-confirm-msg="{{ $itemConfirmMsg }}">
                                            <i class="ti ti-dots fs-2" style="font-size: 1.25rem; line-height: 1;"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <div class="empty py-3">
                                            <div class="empty-icon text-muted mb-3">
                                                <span class="avatar avatar-xl bg-secondary-lt text-secondary rounded-circle" style="width: 56px; height: 56px;">
                                                    <i class="ti ti-trash" style="font-size: 1.75rem;"></i>
                                                </span>
                                            </div>
                                            <h4 class="empty-title fw-bold text-dark mb-1">
                                                @if ($type === 'course')
                                                    No Trashed Courses
                                                @elseif ($type === 'task')
                                                    No Trashed Tasks
                                                @else
                                                    Trash is Empty
                                                @endif
                                            </h4>
                                            <p class="empty-subtitle text-muted mb-0 small">
                                                @if ($search !== '')
                                                    No deleted items match your search "<strong>{{ $search }}</strong>".
                                                @else
                                                    Deleted items will appear here and can be restored or permanently removed.
                                                @endif
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Global trash actions menu (outside table to avoid clipping) --}}
                <div id="trashActionsMenu" class="dropdown-menu shadow-lg border rounded-3 py-1" style="display:none; position:fixed; z-index:1080; min-width:180px;">
                    <span class="dropdown-header text-uppercase small fw-bold text-muted px-3 py-2">ACTIONS</span>
                    <div id="trashActionsItems"></div>
                </div>

                @if ($items->hasPages())
                    <div class="card-footer d-flex justify-content-center py-3 bg-white border-0">{{ $items->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <style>
    /* Category Filter Pills (Matches Admin Dashboard & User My-Task Design) */
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

    .my-task-category-pill.active {
        background-color: #e0f2fe !important;
        color: #0284c7 !important;
        font-weight: 600;
    }

    .my-task-search-input {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        color: #1e293b;
        font-size: 0.875rem;
        transition: all 0.15s ease-in-out;
    }

    .my-task-search-input:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        outline: none;
    }

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

    [data-bs-theme="dark"] .my-task-search-input,
    body.dark-mode .my-task-search-input,
    body.theme-dark .my-task-search-input {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f8fafc !important;
    }
    </style>

    <script>
    (function () {
        var menu = document.getElementById('trashActionsMenu');
        var items = document.getElementById('trashActionsItems');
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

        document.querySelectorAll('.trash-menu-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (openBtn === btn) {
                    closeMenu();
                    return;
                }
                if (openBtn) closeMenu();
                openBtn = btn;

                var restoreUrl = btn.dataset.restoreUrl;
                var deleteUrl = btn.dataset.deleteUrl;
                var confirmMsg = btn.dataset.confirmMsg || 'Delete permanently? This action cannot be undone.';

                var h = '';
                h += '<form action="' + restoreUrl + '" method="POST" class="m-0">';
                h += '<input type="hidden" name="_token" value="' + csrfToken + '">';
                h += '<button type="submit" class="dropdown-item d-flex align-items-center py-2 px-3 text-success">';
                h += '<i class="ti ti-restore me-2 text-success fs-3"></i><span>Restore</span>';
                h += '</button>';
                h += '</form>';

                h += '<div class="dropdown-divider my-1"></div>';

                h += '<form action="' + deleteUrl + '" method="POST" class="m-0" onsubmit="return confirm(\'' + confirmMsg.replace(/'/g, "\\'") + '\')">';
                h += '<input type="hidden" name="_token" value="' + csrfToken + '">';
                h += '<input type="hidden" name="_method" value="DELETE">';
                h += '<button type="submit" class="dropdown-item d-flex align-items-center text-danger py-2 px-3">';
                h += '<i class="ti ti-trash me-2 text-danger fs-3"></i><span>Delete Permanently</span>';
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
    </script>
@endsection
