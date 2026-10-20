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
                    <a href="{{ route('manage.task.create', ['parent_type' => $parentType, 'parent_id' => $parentId]) }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> Create New Task
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
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Responses</th>
                                <th class="w-1">Actions</th>
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
                                    <td>{{ $task->deadline?->format('d M Y H:i') ?? '-' }}</td>
                                    <td>
                                        @if ($task->status === 'published')
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-warning">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $task->pending_submissions_count }} pending</span>
                                        <span class="badge bg-success">{{ $task->completed_submissions_count }} completed</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-secondary task-menu-btn" title="Actions"
                                                data-submissions-url="{{ route('manage.task.submissions', $task) }}"
                                                data-pending="{{ $task->pending_submissions_count }}"
                                                data-edit-url="{{ route('manage.task.edit', $task) }}"
                                                data-publish-url="{{ $task->status === 'draft' ? route('manage.task.publish', $task) : '' }}"
                                                data-qr-url="{{ $task->parent_type === 'standalone' ? route('manage.task.qr', $task->id) : '' }}"
                                                data-delete-url="{{ route('manage.task.destroy', $task) }}">
                                            <i class="ti ti-dots"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No tasks here yet.
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
                            <span class="badge bg-secondary-lt text-secondary">Global Task</span>
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
            h += '<a class="dropdown-item" href="' + btn.dataset.submissionsUrl + '"><i class="ti ti-inbox me-2 text-primary"></i>Responses (' + btn.dataset.pending + ')</a>';
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
