@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none mb-0 pb-0">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <a href="{{ route('manage.classification.index') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 btn-back">
                <i class="ti ti-arrow-left fs-3"></i><span>Back to Classification History</span>
            </a>
            <span class="badge bg-primary-lt text-primary px-3 py-1 rounded-pill small fw-semibold">
                <i class="ti ti-database me-1"></i>Table: classification
            </span>
        </div>

        <!-- Hero Header Box -->
        <div class="card border-0 shadow-sm p-4 mb-3 rounded-4 bg-white option-hero-card">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <!-- Left Group: Icon Box + Title & Subtitle -->
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box" 
                         style="width: 72px; height: 72px; background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); color: #003b6d; border: 1px solid #c7d2fe;">
                        <i class="ti ti-category-2" style="font-size: 2.2rem !important;"></i>
                    </div>

                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h2 class="h3 fw-bold mb-0 text-dark">Main Classification Management</h2>
                            <span class="badge bg-success-lt text-success px-3 py-1 rounded-pill small fw-semibold">
                                <i class="ti ti-check me-1"></i>Live Database
                            </span>
                        </div>
                        <p class="text-muted small mb-0">
                            Manage main training classifications registered directly in the <code>classification</code> table.
                        </p>
                    </div>
                </div>

                <!-- Right Group: Primary Action Button -->
                <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                    <button type="button" 
                            class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm rounded-3" 
                            style="background-color: #003b6d; border-color: #003b6d;"
                            data-bs-toggle="modal" 
                            data-bs-target="#addMainClassificationModal">
                        <i class="ti ti-plus fs-3"></i><span>Add Classification</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="row g-2 mt-3 pt-3 border-top">
                <div class="col-4 col-md-3">
                    <div class="p-2 rounded-3 bg-light-subtle d-flex align-items-center gap-2 metric-box">
                        <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle"><i class="ti ti-category-2"></i></span>
                        <div>
                            <div class="fw-bold fs-4 text-dark mb-0 lh-1">{{ $totalCount }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">Total Classifications</div>
                        </div>
                    </div>
                </div>
                <div class="col-4 col-md-3">
                    <div class="p-2 rounded-3 bg-light-subtle d-flex align-items-center gap-2 metric-box">
                        <span class="avatar avatar-sm bg-success-lt text-success rounded-circle"><i class="ti ti-circle-check"></i></span>
                        <div>
                            <div class="fw-bold fs-4 text-success mb-0 lh-1">{{ $activeCount }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">Active</div>
                        </div>
                    </div>
                </div>
                <div class="col-4 col-md-3">
                    <div class="p-2 rounded-3 bg-light-subtle d-flex align-items-center gap-2 metric-box">
                        <span class="avatar avatar-sm bg-secondary-lt text-secondary rounded-circle"><i class="ti ti-circle-x"></i></span>
                        <div>
                            <div class="fw-bold fs-4 text-muted mb-0 lh-1">{{ $inactiveCount }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">Inactive</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body mt-0 pt-0">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />
        <x-alert type="error" />

        {{-- TOOLBAR FILTER --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3 quick-filter-card">
            <div class="card-body p-3">
                <form action="{{ route('manage.classification.main.index') }}" method="GET" id="classificationFilterForm">
                    <div class="row g-2 align-items-center">
                        <!-- Entries Select -->
                        <div class="col-6 col-sm-4 col-md-2 col-lg-auto" style="min-width: 120px;">
                            <select id="classification-entries-select" name="entries" class="form-select rounded-3" onchange="this.form.submit()">
                                <option value="10" {{ request('entries') == 10 ? 'selected' : '' }}>10 Entries</option>
                                <option value="25" {{ (request('entries') == 25 || !request()->has('entries')) ? 'selected' : '' }}>25 Entries</option>
                                <option value="50" {{ request('entries') == 50 ? 'selected' : '' }}>50 Entries</option>
                                <option value="100" {{ request('entries') == 100 ? 'selected' : '' }}>100 Entries</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-6 col-sm-4 col-md-2 col-lg-auto" style="min-width: 140px;">
                            <select name="status" class="form-select rounded-3" id="classification-status-select" onchange="this.form.submit()">
                                <option value="" {{ (request('status') === null || request('status') === '' || request('status') === 'all') ? 'selected' : '' }}>All Status</option>
                                <option value="1" {{ (request('status') === '1' || strtolower((string)request('status')) === 'active') ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ (request('status') === '0' || strtolower((string)request('status')) === 'inactive') ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="col-12 col-md-4 col-lg flex-grow-1">
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text" 
                                       name="q" 
                                       id="classification-search-input" 
                                       class="form-control rounded-3" 
                                       placeholder="Search classification..." 
                                       value="{{ request('q') ?? (request('search') ?? request('s')) }}" 
                                       autocomplete="off">
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-auto d-flex align-items-center gap-1">
                            <button type="submit" class="btn btn-primary btn-icon px-3 rounded-3" title="Search">
                                <i class="ti ti-search fs-2"></i>
                            </button>
                            <a href="{{ route('manage.classification.main.index') }}" class="btn btn-outline-secondary btn-icon px-3 rounded-3" title="Reset Filters">
                                <i class="ti ti-refresh fs-2"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- MAIN DATA TABLE --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header border-0 bg-transparent py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h3 class="card-title mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="ti ti-category-2 text-primary"></i>
                        List Classification
                    </h3>
                    <div class="text-muted small">Manage main training classifications registered in the system.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-muted border px-2 py-1">
                        {{ count($items) }} row(s) displayed
                    </span>
                    <button type="button" 
                            class="btn btn-primary btn-sm rounded-3 px-3 fw-semibold shadow-sm" 
                            style="background-color: #003b6d; border-color: #003b6d;"
                            data-bs-toggle="modal" 
                            data-bs-target="#addMainClassificationModal">
                        <i class="ti ti-plus me-1"></i>Add Classification
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div id="table-default" class="table-responsive">
                    @if (count($items) > 0)
                        <table class="table table-vcenter card-table table-hover classification-table w-100 mb-0" id="ClassificationTable">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th width="60" class="text-center" data-orderable="false">No</th>
                                    <th>Classification Name</th>
                                    <th width="240">Slug</th>
                                    <th class="text-center" width="120">Status</th>
                                    <th class="text-center" width="180">Created At</th>
                                    <th width="160" class="text-end pe-4" data-orderable="false">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td class="text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle fw-bold">
                                                    {{ strtoupper(substr($item->classification_name ?? 'C', 0, 1)) }}
                                                </span>
                                                <div>
                                                    <div class="fw-semibold text-dark item-name-text">{{ $item->classification_name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark font-monospace border px-2 py-1 rounded-2">
                                                {{ $item->slug }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if ($item->is_active)
                                                <span class="badge bg-success-lt text-success px-2 py-1 rounded-pill fw-semibold">Active</span>
                                            @else
                                                <span class="badge bg-secondary-lt text-secondary px-2 py-1 rounded-pill fw-semibold">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-muted small">
                                            {{ $item->created_at ? $item->created_at->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-list justify-content-end">
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm rounded-pill btn-edit-main"
                                                        data-id="{{ $item->id }}"
                                                        data-name="{{ $item->classification_name }}"
                                                        data-slug="{{ $item->slug }}"
                                                        data-active="{{ $item->is_active ? '1' : '0' }}"
                                                        title="Edit Classification">
                                                    <i class="ti ti-edit me-1"></i>Edit
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm rounded-pill btn-delete-main"
                                                        data-id="{{ $item->id }}"
                                                        data-name="{{ $item->classification_name }}"
                                                        title="Delete Classification">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5 px-3">
                            <div class="avatar avatar-xl bg-light-subtle text-muted rounded-circle mx-auto mb-3" style="width: 72px; height: 72px;">
                                <i class="ti ti-category-2 fs-1"></i>
                            </div>
                            <h3 class="fw-bold text-dark mb-1">No Classifications Found</h3>
                            <p class="text-muted small mb-4">No classification records match your filter criteria or search keyword.</p>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <a href="{{ route('manage.classification.main.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                                    <i class="ti ti-refresh me-1"></i>Reset Filters
                                </a>
                                <button type="button" class="btn btn-primary rounded-pill px-4" style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="modal" data-bs-target="#addMainClassificationModal">
                                    <i class="ti ti-plus me-1"></i>Add Classification
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODALS                                                                    --}}
{{-- ========================================================================= --}}

{{-- 1. ADD CLASSIFICATION MODAL --}}
<div class="modal modal-blur fade" id="addMainClassificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('manage.classification.main.store') }}" method="POST" id="formAddMainClassification">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="avatar avatar-xs bg-primary-lt text-primary rounded-circle"><i class="ti ti-plus"></i></span>
                        Add New Classification
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="alert alert-danger d-none modal-error-alert rounded-3 mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Classification Name</label>
                        <input type="text" 
                               name="classification_name" 
                               id="add-classification-name"
                               class="form-control rounded-3" 
                               placeholder="e.g. Technology, Human Resources" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Slug</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted small"><i class="ti ti-link"></i></span>
                            <input type="text" 
                                   name="slug" 
                                   id="add-classification-slug"
                                   class="form-control rounded-end-3 font-monospace" 
                                   placeholder="e.g. technology" 
                                   required>
                        </div>
                        <small class="text-muted">Auto-generated from name. You can customize it if needed.</small>
                    </div>

                    <div class="mb-0">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <span class="form-check-label fw-semibold">Active Status</span>
                        </label>
                        <small class="text-muted">Active classifications are selectable across training and content forms.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i>Save Classification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 2. EDIT CLASSIFICATION MODAL --}}
<div class="modal modal-blur fade" id="editMainClassificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="" method="POST" id="formEditMainClassification">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-classification-id" name="id">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="avatar avatar-xs bg-primary-lt text-primary rounded-circle"><i class="ti ti-edit"></i></span>
                        Edit Classification
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="alert alert-danger d-none modal-error-alert rounded-3 mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Classification Name</label>
                        <input type="text" 
                               name="classification_name" 
                               id="edit-classification-name"
                               class="form-control rounded-3" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Slug</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted small"><i class="ti ti-link"></i></span>
                            <input type="text" 
                                   name="slug" 
                                   id="edit-classification-slug"
                                   class="form-control rounded-end-3 font-monospace" 
                                   required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit-classification-active" value="1">
                            <span class="form-check-label fw-semibold">Active Status</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i>Update Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 3. DELETE CONFIRMATION MODAL --}}
<div class="modal modal-blur fade" id="deleteMainConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center py-4">
                <div class="avatar avatar-xl bg-danger-lt text-danger rounded-circle mx-auto mb-3" style="width: 64px; height: 64px;">
                    <i class="ti ti-alert-triangle fs-1"></i>
                </div>
                <h4 class="fw-bold mb-1">Delete Classification?</h4>
                <p class="text-muted small mb-0">Are you sure you want to permanently delete <strong id="deleteTargetName"></strong>?</p>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="confirmDeleteMainBtn">
                    <i class="ti ti-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 4. USAGE WARNING MODAL (BLOCKED DELETE) --}}
<div class="modal modal-blur fade" id="deactivateWarningModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center py-4">
                <div class="avatar avatar-xl bg-warning-lt text-warning rounded-circle mx-auto mb-3" style="width: 64px; height: 64px;">
                    <i class="ti ti-shield-lock fs-1"></i>
                </div>
                <h4 class="fw-bold mb-2">Classification Cannot Be Deleted</h4>
                <p class="text-muted mb-3" id="deactivateWarningText">This classification is currently in use.</p>
                <div class="alert alert-info small text-start rounded-3 mb-0">
                    <i class="ti ti-info-circle me-1"></i>
                    To protect historical learning records, sub-classifications, and course examples, in-use master records cannot be removed. You can deactivate it instead via Edit modal.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-dismiss="modal">Understood</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
<style>
    .classification-table thead th {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
        padding-top: 12px;
        padding-bottom: 12px;
    }
    .classification-table tbody td {
        vertical-align: middle;
        padding-top: 12px;
        padding-bottom: 12px;
    }
    .metric-box {
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Dark Mode Custom Overrides */
    [data-bs-theme="dark"] .option-hero-card,
    body.dark-mode .option-hero-card,
    body.theme-dark .option-hero-card,
    [data-bs-theme="dark"] .quick-filter-card,
    body.dark-mode .quick-filter-card,
    body.theme-dark .quick-filter-card {
        background: var(--tblr-bg-surface, #1f2937) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] .hero-icon-box,
    body.dark-mode .hero-icon-box,
    body.theme-dark .hero-icon-box {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #93c5fd !important;
    }

    [data-bs-theme="dark"] .metric-box,
    body.dark-mode .metric-box,
    body.theme-dark .metric-box {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        var table = null;

        if ($('#ClassificationTable').length) {
            table = $('#ClassificationTable').DataTable({
                pageLength: {{ request('entries', 25) }},
                autoWidth: false,
                responsive: true,
                order: [],
                columnDefs: [
                    { orderable: false, targets: [0, -1] }
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                    zeroRecords: 'No classifications found',
                    info: 'Showing _START_ - _END_ of _TOTAL_ entries',
                    paginate: {
                        previous: '‹',
                        next: '›'
                    }
                }
            });

            $('#classification-entries-select').on('change', function () {
                var len = parseInt($(this).val(), 10);
                if (table) {
                    table.page.len(len).draw();
                }
            });
        }

        // Live search connection
        var searchInput = document.getElementById('classification-search-input');
        if (searchInput && table) {
            searchInput.addEventListener('input', function () {
                table.search(this.value.trim()).draw();
            });
        }

        // Helper: String to Slug
        function slugify(text) {
            return text.toString().toLowerCase().trim()
                .replace(/\s+/g, '-')           // Replace spaces with -
                .replace(/&/g, '-and-')         // Replace & with 'and'
                .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
                .replace(/\-\-+/g, '-')         // Replace multiple - with single -
                .replace(/^-+/, '')             // Trim - from start of text
                .replace(/-+$/, '');            // Trim - from end of text
        }

        // Auto slug generation on add
        $('#add-classification-name').on('input', function () {
            var val = $(this).val();
            $('#add-classification-slug').val(slugify(val));
        });

        // AJAX Form Submission Helper
        function submitAjaxForm(form, btn, alertBox, modalId) {
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
            alertBox.addClass('d-none').text('');

            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (res) {
                    btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Saved');
                    var modalEl = document.getElementById(modalId);
                    if (modalEl) {
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    window.location.reload();
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Submit');
                    var msg = 'Failed to process request.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alertBox.html(msg).removeClass('d-none');
                }
            });
        }

        // Form 1: Add Classification
        $('#formAddMainClassification').on('submit', function (e) {
            e.preventDefault();
            submitAjaxForm($(this), $(this).find('button[type="submit"]'), $(this).find('.modal-error-alert'), 'addMainClassificationModal');
        });

        // Form 2: Edit Classification
        $('#formEditMainClassification').on('submit', function (e) {
            e.preventDefault();
            submitAjaxForm($(this), $(this).find('button[type="submit"]'), $(this).find('.modal-error-alert'), 'editMainClassificationModal');
        });

        // Edit Trigger
        $(document).on('click', '.btn-edit-main', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var slug = $(this).data('slug');
            var active = $(this).data('active');

            $('#edit-classification-id').val(id);
            $('#edit-classification-name').val(name);
            $('#edit-classification-slug').val(slug);
            $('#edit-classification-active').prop('checked', active == '1');
            $('#formEditMainClassification').attr('action', '/manage/classification/classification/' + id);

            $('#formEditMainClassification .modal-error-alert').addClass('d-none').text('');
            var modal = new bootstrap.Modal(document.getElementById('editMainClassificationModal'));
            modal.show();
        });

        // Delete Trigger with Safety Usage Check
        var pendingDeleteId = null;

        $(document).on('click', '.btn-delete-main', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');

            $.ajax({
                url: '/manage/classification/classification/' + id + '/usage',
                type: 'GET',
                success: function (res) {
                    if (res.success && res.data && res.data.is_used) {
                        $('#deactivateWarningText').text(res.data.message);
                        var warnModal = new bootstrap.Modal(document.getElementById('deactivateWarningModal'));
                        warnModal.show();
                    } else {
                        pendingDeleteId = id;
                        $('#deleteTargetName').text(name);
                        var delModal = new bootstrap.Modal(document.getElementById('deleteMainConfirmModal'));
                        delModal.show();
                    }
                },
                error: function () {
                    alert('Failed to check classification usage.');
                }
            });
        });

        // Confirm Permanent Delete Execution
        $('#confirmDeleteMainBtn').on('click', function () {
            if (!pendingDeleteId) return;

            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');

            $.ajax({
                url: '/manage/classification/classification/' + pendingDeleteId,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function () {
                    window.location.reload();
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="ti ti-trash me-1"></i> Delete');
                    var msg = 'Failed to delete classification.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alert(msg);
                }
            });
        });
    });
</script>
@endsection
