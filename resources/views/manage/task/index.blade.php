{{-- resources/views/manage/task/index.blade.php --}}
{{-- Tasks of one parent (playlist / content / offline training). --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="mb-2">
                <a href="{{ route('manage.task.index', $parentType === 'offline_training' ? ['scope' => 'non_kp'] : []) }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Back
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index', $parentType === 'offline_training' ? ['scope' => 'non_kp'] : []) }}">Task Management</a>
                    </div>
                    <h2 class="page-title">
                        <span class="badge bg-secondary me-2 align-middle" style="font-size:.65rem">
                            {{ \App\Models\Task::PARENT_TYPES[$parentType] ?? $parentType }}
                        </span>
                        {{ $parentName }}
                    </h2>
                </div>
                <div class="col-auto ms-auto d-flex gap-2">
                    <a href="{{ route('manage.task.trash') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-trash me-1"></i>Trash
                    </a>
                    {{-- Parent is auto-embedded: no need to pick it again in the form --}}
                    <a href="{{ route('manage.task.create', ['parent_type' => $parentType, 'parent_id' => $parentId]) }}" class="btn btn-primary px-3 rounded-3 d-inline-flex align-items-center gap-1.5 shadow-sm">
                        <i class="ti ti-plus fs-2"></i> Add Task
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search tasks..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="published" @selected(request('status') === 'published')>Published</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary w-100" type="submit">
                        <i class="ti ti-search"></i> Search
                    </button>
                </div>
            </form>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Type</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Responses</th>
                                <th class="w-1 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $task)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $task->title }}</div>
                                        @if ($task->parent_type === 'standalone')
                                            <div class="mt-1 d-flex align-items-center flex-wrap gap-1">
                                                <span class="text-muted small">Unique Code:</span>
                                                <span class="badge bg-secondary-lt text-secondary">{{ $task->access_code ?? '#N/A' }}</span>
                                                @if ($task->access_code)
                                                    <button type="button" class="btn btn-sm btn-link p-0 text-muted"
                                                            onclick="copyTextToClipboard('{{ $task->access_code }}', this)"
                                                            title="Copy unique code">
                                                        <i class="ti ti-copy"></i>
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-link p-0 ms-1 text-primary btn-task-qr"
                                                        data-qr-url="{{ route('manage.task.qr', $task->id) }}"
                                                        title="Share &amp; QR Code">
                                                    <i class="ti ti-qrcode"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-lt text-primary">
                                            {{ $task->typeLabel() }}
                                        </span>
                                    </td>
                                    <td>{{ $task->deadline?->format('d M Y H:i') ?? '-' }}</td>
                                    <td>
                                        @if ($task->status === 'published')
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-warning">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $pendingCount = (int) ($task->pending_submissions_count ?? 0);
                                            $completedCount = (int) ($task->completed_submissions_count ?? 0);
                                            $totalCount = $pendingCount + $completedCount;
                                        @endphp
                                        <div class="d-flex flex-column gap-1 align-items-start">
                                            @if ($pendingCount > 0)
                                                <a href="{{ route('manage.task.submissions', ['task' => $task, 'status' => 'pending']) }}"
                                                   class="text-decoration-none"
                                                   title="{{ $pendingCount }} response(s) waiting for review">
                                                    <span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size: 0.78rem;">
                                                        <span class="status-dot status-dot-animated bg-red" style="width: 7px; height: 7px;"></span>
                                                        <i class="ti ti-bell-ringing"></i>
                                                        <span>{{ $pendingCount }} Need Review</span>
                                                    </span>
                                                </a>
                                            @endif

                                            @if ($completedCount > 0)
                                                <a href="{{ route('manage.task.submissions', ['task' => $task, 'status' => 'completed']) }}"
                                                   class="text-decoration-none"
                                                   title="{{ $completedCount }} completed response(s)">
                                                    <span class="badge bg-success-lt text-success fw-semibold px-2 py-0.5 d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                                        <i class="ti ti-check"></i>
                                                        <span>{{ $completedCount }} Completed</span>
                                                    </span>
                                                </a>
                                            @endif

                                            @if ($totalCount === 0)
                                                <span class="badge bg-secondary-lt text-muted px-2 py-0.5" style="font-size: 0.75rem;">
                                                    0 responses
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-secondary task-menu-btn position-relative" title="Actions"
                                                data-submissions-url="{{ route('manage.task.submissions', $task) }}"
                                                data-pending="{{ $pendingCount }}"
                                                data-completed="{{ $completedCount }}"
                                                data-edit-url="{{ route('manage.task.edit', $task) }}"
                                                data-publish-url="{{ $task->status === 'draft' ? route('manage.task.publish', $task) : '' }}"
                                                data-qr-url="{{ $task->parent_type === 'standalone' ? route('manage.task.qr', $task->id) : '' }}"
                                                data-delete-url="{{ route('manage.task.destroy', $task) }}">
                                            <i class="ti ti-dots"></i>
                                            @if ($pendingCount > 0)
                                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="transform: translate(-40%, -40%) !important;">
                                                    <span class="visually-hidden">New responses</span>
                                                </span>
                                            @endif
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="empty py-4">
                                            <div class="empty-icon text-muted mb-3">
                                                <span class="avatar avatar-xl bg-primary-lt text-primary rounded-circle" style="width: 64px; height: 64px;">
                                                    <i class="ti ti-clipboard-list" style="font-size: 2rem;"></i>
                                                </span>
                                            </div>
                                            <h3 class="empty-title fw-bold text-dark mb-1">No Tasks in This Course Yet</h3>
                                            <p class="empty-subtitle text-muted mb-0" style="max-width: 460px; margin: 0 auto; line-height: 1.5;">
                                                <strong>{{ $parentName }}</strong> does not have any tasks yet. Click the <strong>"+ Add Task"</strong> button in the top right to create one.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Global task actions menu (outside table to avoid clipping) --}}
                <div id="taskActionsMenu" class="dropdown-menu" style="display:none; position:fixed; z-index:1080; min-width:220px;">
                    <span class="dropdown-header">TASK ACTIONS</span>
                    <div id="taskActionsItems"></div>
                </div>

            </div>

        </div>
    </div>

    {{-- Modal Pop-up QR Code & Share --}}
    <div class="modal fade" id="taskQrModal" tabindex="-1" aria-labelledby="taskQrModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="taskQrModalTitle">
                        <i class="ti ti-qrcode text-primary fs-2"></i> Share Task &amp; QR Code
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div id="taskQrLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted small mt-2">Loading QR Code...</div>
                    </div>
                    <div id="taskQrError" class="alert alert-danger d-none py-2 small mb-0"></div>
                    <div id="taskQrContent" class="d-none">
                        <div class="text-center mb-3">
                            <h4 class="fw-bold mb-1 text-dark" id="taskQrTaskTitle"></h4>
                            <span class="badge bg-secondary-lt text-secondary">Others</span>
                        </div>
                        <div class="text-center mb-3">
                            <div class="p-3 bg-white border rounded d-inline-block shadow-sm" id="taskQrSvgContainer" style="line-height:0; max-width:260px;"></div>
                            <div class="text-muted small mt-2">Scan with camera to unlock this task</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Redeem / Share Link</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="taskQrRedeemUrl" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('taskQrRedeemUrl', this)">
                                    <i class="ti ti-copy me-1"></i>Copy
                                </button>
                            </div>
                        </div>
                        <div class="mb-3" id="taskQrCodeGroup">
                            <label class="form-label small fw-semibold text-muted">Unique Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control fw-bold" id="taskQrAccessCode" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('taskQrAccessCode', this)">
                                    <i class="ti ti-copy me-1"></i>Copy
                                </button>
                            </div>
                        </div>
                        <div class="alert alert-info py-2 small mb-0" id="taskQrShareNote"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-secondary px-3 py-2" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script>
function copyTextToClipboard(text, btn) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
        if (btn) {
            var origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-check text-success"></i>';
            setTimeout(function() { btn.innerHTML = origHtml; }, 1500);
        }
    });
}

function copyInputText(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input || !input.value) return;
    navigator.clipboard.writeText(input.value).then(function() {
        var origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check me-1 text-success"></i>Copied!';
        setTimeout(function() { btn.innerHTML = origHtml; }, 1800);
    });
}

function openQrModal(qrUrl) {
    var modalEl = document.getElementById('taskQrModal');
    if (!modalEl) return;

    var loadingEl = document.getElementById('taskQrLoading');
    var errorEl = document.getElementById('taskQrError');
    var contentEl = document.getElementById('taskQrContent');

    loadingEl.classList.remove('d-none');
    errorEl.classList.add('d-none');
    contentEl.classList.add('d-none');

    // Show modal
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    } else if (window.jQuery && $(modalEl).modal) {
        $(modalEl).modal('show');
    }

    // Fetch QR data
    fetch(qrUrl, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(res) {
        if (!res.ok) throw new Error('Failed to load QR data');
        return res.json();
    })
    .then(function(data) {
        loadingEl.classList.add('d-none');
        contentEl.classList.remove('d-none');

        document.getElementById('taskQrTaskTitle').textContent = data.title || 'Task';
        document.getElementById('taskQrSvgContainer').innerHTML = data.qr_svg || '<span class="text-muted">No QR Available</span>';
        document.getElementById('taskQrRedeemUrl').value = data.redeem_url || '';

        var codeGroup = document.getElementById('taskQrCodeGroup');
        var codeInput = document.getElementById('taskQrAccessCode');
        if (data.access_code) {
            codeGroup.classList.remove('d-none');
            codeInput.value = data.access_code;
        } else {
            codeGroup.classList.add('d-none');
            codeInput.value = '';
        }

        document.getElementById('taskQrShareNote').textContent = data.share_note || '';
    })
    .catch(function(err) {
        loadingEl.classList.add('d-none');
        errorEl.textContent = 'Could not load QR code. Please try again.';
        errorEl.classList.remove('d-none');
    });
}

(function () {
    var menu = document.getElementById('taskActionsMenu');
    var items = document.getElementById('taskActionsItems');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    var openBtn = null;

    function closeMenu() {
        menu.style.display = 'none';
        openBtn = null;
        document.removeEventListener('click', outsideClick);
    }

    function outsideClick(e) {
        if (openBtn && !menu.contains(e.target) && !openBtn.contains(e.target)) closeMenu();
    }

    // Handle standalone QR button click in table row
    document.addEventListener('click', function(e) {
        var qrBtn = e.target.closest('.btn-task-qr');
        if (qrBtn) {
            e.preventDefault();
            openQrModal(qrBtn.dataset.qrUrl);
            return;
        }

        var menuQr = e.target.closest('.btn-action-qr');
        if (menuQr) {
            e.preventDefault();
            closeMenu();
            openQrModal(menuQr.dataset.qrUrl);
            return;
        }
    });

    document.querySelectorAll('.task-menu-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (openBtn === btn) { closeMenu(); return; }
            if (openBtn) closeMenu();
            openBtn = btn;
            var h = '';
            var pending = parseInt(btn.dataset.pending, 10) || 0;
            var completed = parseInt(btn.dataset.completed, 10) || 0;
            if (pending > 0) {
                h += '<a class="dropdown-item d-flex align-items-center justify-content-between text-warning fw-bold" href="' + btn.dataset.submissionsUrl + '">' +
                    '<span><i class="ti ti-inbox me-2 text-warning"></i>Responses</span>' +
                    '<span class="badge bg-warning text-dark fw-bold ms-2">' + pending + ' need review</span>' +
                    '</a>';
            } else if (completed > 0) {
                h += '<a class="dropdown-item d-flex align-items-center justify-content-between" href="' + btn.dataset.submissionsUrl + '">' +
                    '<span><i class="ti ti-inbox me-2 text-primary"></i>Responses</span>' +
                    '<span class="badge bg-success-lt text-success ms-2">' + completed + ' completed</span>' +
                    '</a>';
            } else {
                h += '<a class="dropdown-item d-flex align-items-center justify-content-between text-muted" href="' + btn.dataset.submissionsUrl + '">' +
                    '<span><i class="ti ti-inbox me-2 text-muted"></i>Responses</span>' +
                    '<span class="badge bg-secondary-lt text-muted ms-2">0</span>' +
                    '</a>';
            }
            h += '<a class="dropdown-item" href="' + btn.dataset.editUrl + '"><i class="ti ti-edit me-2 text-info"></i>Edit</a>';
            if (btn.dataset.publishUrl) {
                h += '<form action="' + btn.dataset.publishUrl + '" method="POST"><input type="hidden" name="_token" value="' + csrfToken + '"><button type="submit" class="dropdown-item"><i class="ti ti-send me-2 text-success"></i>Publish</button></form>';
            }
            if (btn.dataset.qrUrl) {
                h += '<a class="dropdown-item btn-action-qr" href="javascript:void(0)" data-qr-url="' + btn.dataset.qrUrl + '"><i class="ti ti-qrcode me-2 text-primary"></i>QR / Share</a>';
            }
            h += '<div class="dropdown-divider"></div>';
            h += '<form action="' + btn.dataset.deleteUrl + '" method="POST" onsubmit="return confirm(\'Move this task to trash? You can restore it later from the Trash page.\')"><input type="hidden" name="_token" value="' + csrfToken + '"><input type="hidden" name="_method" value="DELETE"><button type="submit" class="dropdown-item text-danger"><i class="ti ti-trash me-2 text-danger"></i>Delete</button></form>';
            items.innerHTML = h;
            var r = btn.getBoundingClientRect();
            menu.style.display = 'block';
            var mw = menu.offsetWidth, mh = menu.offsetHeight;
            var left = Math.min(r.right - mw, window.innerWidth - mw - 8);
            var top = r.bottom + 4;
            if (top + mh > window.innerHeight - 8) top = Math.max(8, r.top - mh - 4);
            menu.style.left = Math.max(8, left) + 'px';
            menu.style.top = Math.max(8, top) + 'px';
            setTimeout(function(){ document.addEventListener('click', outsideClick); }, 0);
        });
    });
})();
</script>
@endsection
