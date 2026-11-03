{{-- resources/views/manage/task/trash.blade.php --}}
{{-- Soft-deleted tasks: restore them or delete forever. --}}
@extends('layouts.sidebar-master')

@section('content')
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
                    <h2 class="page-title">
                        <i class="ti ti-trash me-1"></i>Trash
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th class="w-1">NO</th>
                                <th>TASK</th>
                                <th>TYPE</th>
                                <th>PLACEMENT</th>
                                <th>DELETED AT</th>
                                <th class="text-end">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $i => $task)
                                <tr>
                                    <td class="text-muted">{{ ($tasks->firstItem() ?? 0) + $i }}</td>
                                    <td class="fw-semibold">{{ $task->title }}</td>
                                    <td>
                                        <span class="badge bg-primary-lt text-primary">
                                            {{ \App\Models\Task::TYPES[$task->type] ?? $task->type }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-lt text-secondary">
                                            {{ \App\Models\Task::PARENT_TYPES[$task->parent_type] ?? $task->parent_type }}
                                        </span>
                                        <span class="text-muted small ms-1">{{ $task->parentName() }}</span>
                                    </td>
                                    <td class="text-muted text-nowrap">{{ $task->deleted_at?->format('d M Y H:i') }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-secondary trash-menu-btn" title="Actions" style="padding: 0.35rem 0.65rem;"
                                                data-restore-url="{{ route('manage.task.restore', $task->id) }}"
                                                data-delete-url="{{ route('manage.task.force-destroy', $task->id) }}">
                                            <i class="ti ti-dots fs-2" style="font-size: 1.25rem; line-height: 1;"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="ti ti-trash fs-1 d-block mb-2"></i>
                                        Trash is empty.
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

                @if ($tasks->hasPages())
                    <div class="card-footer">{{ $tasks->links() }}</div>
                @endif
            </div>
        </div>
    </div>

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

                var h = '';
                h += '<form action="' + restoreUrl + '" method="POST" class="m-0">';
                h += '<input type="hidden" name="_token" value="' + csrfToken + '">';
                h += '<button type="submit" class="dropdown-item d-flex align-items-center py-2 px-3 text-success">';
                h += '<i class="ti ti-restore me-2 text-success fs-3"></i><span>Restore</span>';
                h += '</button>';
                h += '</form>';

                h += '<div class="dropdown-divider my-1"></div>';

                h += '<form action="' + deleteUrl + '" method="POST" class="m-0" onsubmit="return confirm(\'Delete this task? Questions, responses and files will be wiped and cannot be restored.\')">';
                h += '<input type="hidden" name="_token" value="' + csrfToken + '">';
                h += '<input type="hidden" name="_method" value="DELETE">';
                h += '<button type="submit" class="dropdown-item d-flex align-items-center text-danger py-2 px-3">';
                h += '<i class="ti ti-trash me-2 text-danger fs-3"></i><span>Delete</span>';
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
