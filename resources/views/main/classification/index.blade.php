@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <!-- Back Button to Manage Dashboard -->
        <div class="mb-3">
            <a href="{{ route('manage.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none">
                <i class="ti ti-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-primary-lt">
                        <i class="ti ti-category fs-2"></i>
                    </div>
                    <div>
                        <h2 class="page-title mb-1">
                            Manage Category
                        </h2>
                        <div class="text-secondary">Manage OWL LMS learning categories for content organization, classification, and reporting.</div>
                    </div>
                </div>
            </div>

            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('manage.classification.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add Category
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        {{-- HERO WITH TOTAL CATEGORIES STAT INSIDE --}}
        <div class="card category-hero border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-body p-4">
                <div class="row align-items-center g-3">
                    <div class="col-lg-8">
                        <div class="text-muted text-uppercase small fw-semibold mb-1">
                            Learning Management
                        </div>

                        <h1 class="mb-2">
                            Category Management
                        </h1>

                        <div class="text-muted">
                            Manage OWL LMS learning categories for content organization, classification, and reporting.
                        </div>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <div class="d-inline-flex align-items-center gap-3 bg-white bg-opacity-75 p-3 rounded-4 border shadow-sm text-start">
                            <span class="avatar avatar-md bg-primary-lt text-primary rounded-circle">
                                <i class="ti ti-category fs-2"></i>
                            </span>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Total Categories</div>
                                <div class="h2 mb-0 fw-bold">{{ count($classification) }} <span class="fs-4 text-muted fw-normal">Categories</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEARCH & ENTRIES TOOLBAR --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <form action="{{ route('manage.classification.index') }}" method="GET" id="manageCategoryFilterForm">
                    <div class="d-flex align-items-center gap-2">
                        <!-- Relocated Entries Dropdown -->
                        <div class="entries-select-wrapper" style="min-width: 130px;">
                            <select id="category-entries-select" class="form-select rounded-3">
                                <option value="10">10 Entries</option>
                                <option value="25" selected>25 Entries</option>
                                <option value="50">50 Entries</option>
                                <option value="100">100 Entries</option>
                            </select>
                        </div>

                        <!-- Live Autocomplete Search Input -->
                        <div class="position-relative manage-search-container flex-grow-1">
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text" name="q" id="manage-classification-search-input" class="form-control" placeholder="Search category..." value="{{ request('q') ?? (request('search') ?? request('s')) }}" autocomplete="off">
                            </div>
                            <div id="manage-classification-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" 
                                 style="display: none; z-index: 2000; background-color: #ffffff;">
                                <!-- Container 1: Scrollable List Area (Max 280px height with vertical scrollbar) -->
                                <div id="manage-classification-autocomplete-list" class="list-group list-group-flush" 
                                     style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>
                                
                                <!-- Container 2: Fixed/Sticky Bottom Footer -->
                                <div id="manage-classification-search-footer" class="border-top p-2 text-center" 
                                     style="display: none; position: sticky; bottom: 0; z-index: 2005; background-color: #f8f9fa;"></div>
                            </div>
                        </div>

                        <!-- Action Buttons (Search submit & Refresh) -->
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary rounded-3 px-3" style="background-color: #0c5b9e; border-color: #0c5b9e;" title="Search">
                                <i class="ti ti-search"></i>
                            </button>
                            <a href="{{ route('manage.classification.index') }}" class="btn btn-outline-primary rounded-3 px-3 btn-quick-reset" style="border-color: #0c5b9e; color: #0c5b9e; background-color: transparent;" title="Reset Filter">
                                <i class="ti ti-refresh" style="font-size: 1.1rem; color: #0c5b9e !important;"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

            <div class="card-header border-0">
                <div>
                    <h3 class="card-title mb-1">
                        List Category
                    </h3>

                    <div class="text-muted small">
                       View all categories available in OWL LMS.
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div id="table-default" class="table-responsive">

                    @unless(count($classification) == 0)

                        <table class="table table-vcenter card-table category-table w-100" id="MyTable">
                            <thead>
                                <tr>
                                    <th width="60">No</th>
                                    <th>Name</th>
                                    <th width="180" class="text-end">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($classification as $list)
                                    <tr>
                                        <td class="text-muted">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle">
                                                    {{ strtoupper(substr($list->classification_name ?? 'C', 0, 1)) }}
                                                </span>

                                                <div>
                                                    <div class="category-name">
                                                        {{ $list->classification_name }}
                                                    </div>

                                                    <div class="category-slug">
                                                        /{{ $list->slug ?? '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="btn-list justify-content-end">

                                                @if(!empty($list->slug))
                                                    <a href="{{ route('manage.classification.edit', $list->slug) }}"
                                                       class="btn btn-outline-primary btn-sm rounded-pill"
                                                       title="Edit Category">
                                                        <i class="ti ti-edit me-1"></i>
                                                        Edit
                                                    </a>

                                                    <form action="{{ route('manage.classification.destroy', $list->slug) }}"
                                                          method="POST"
                                                          class="d-inline delete-form">

                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="button"
                                                                class="btn btn-outline-danger btn-sm rounded-pill btn-delete-category"
                                                                data-name="{{ $list->classification_name }}">
                                                            <i class="ti ti-trash me-1"></i>
                                                            Delete
                                                        </button>

                                                    </form>
                                                @else
                                                    <button class="btn btn-outline-secondary btn-sm rounded-pill" disabled>
                                                        <i class="ti ti-alert-circle me-1"></i>
                                                        No Slug
                                                    </button>
                                                @endif

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    @else

                        <div class="text-center p-5">
                            <i class="ti ti-category-off text-muted"
                               style="font-size: 56px;"></i>

                            <h3 class="mt-3 mb-1">
                                No Category Data Found
                            </h3>

                            <div class="text-muted mb-3">
                                Belum ada kategori yang dibuat.
                            </div>

                            <a href="{{ route('manage.classification.create') }}"
                               class="btn btn-primary rounded-pill">
                                <i class="ti ti-plus me-1"></i>
                                Add First Category
                            </a>
                        </div>

                    @endunless

                </div>
            </div>
        </div>

    </div>
</div>

{{-- DELETE MODAL --}}
<div class="modal modal-blur fade"
     id="deleteCategoryModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">

            <div class="modal-header border-0">
                <h5 class="modal-title">
                    Delete Category
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                </button>
            </div>

            <div class="modal-body">
                <div class="text-center">

                    <span class="avatar avatar-xl bg-red-lt text-red mx-auto mb-3">
                        <i class="ti ti-trash fs-1"></i>
                    </span>

                    <h3 class="mb-2">
                        Delete this category?
                    </h3>

                    <p class="text-muted mb-0">
                        Category
                        <strong id="deleteCategoryName"></strong>
                        will be permanently deleted.
                    </p>

                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button"
                        class="btn btn-outline-secondary rounded-pill"
                        data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button"
                        id="confirmDeleteCategory"
                        class="btn btn-danger rounded-pill">
                    <i class="ti ti-trash me-1"></i>
                    Delete Category
                </button>
            </div>

        </div>
    </div>
</div>

@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    .rounded-4 {
        border-radius: 20px !important;
    }

    .btn-back {
        color: #0c5b9e !important;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }

    .btn-back:hover {
        opacity: 0.85;
        transform: translateX(-3px);
    }

    .btn-quick-reset {
        border-color: #0c5b9e !important;
        color: #0c5b9e !important;
        background-color: transparent !important;
        transition: background-color .15s ease-in-out, border-color .15s ease-in-out, color .15s ease-in-out;
    }

    .btn-quick-reset:hover {
        background-color: #e6f0fa !important;
        border-color: #0c5b9e !important;
        color: #0c5b9e !important;
    }

    .btn-quick-reset:hover i {
        color: #0c5b9e !important;
    }

    [data-bs-theme="dark"] .btn-quick-reset:hover {
        background-color: rgba(12, 91, 158, 0.25) !important;
        border-color: #3b82f6 !important;
        color: #60a5fa !important;
    }

    [data-bs-theme="dark"] .btn-quick-reset:hover i {
        color: #60a5fa !important;
    }

    .manage-classification-search-container,
    .manage-category-search-container {
        position: relative;
        width: 100%;
    }

    #manage-classification-search-results,
    #manage-category-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 2000;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        overflow: hidden !important;
    }

    #manage-classification-autocomplete-list,
    #manage-category-autocomplete-list {
        max-height: 280px !important;
        overflow-y: auto !important;
        background-color: #ffffff !important;
        scrollbar-width: thin;
    }

    #manage-category-search-results::-webkit-scrollbar {
        width: 6px;
    }

    #manage-category-search-results::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2);
        border-radius: 4px;
    }

    #manage-category-search-results .dropdown-item {
        padding: .6rem .85rem;
        transition: background-color .15s ease;
        text-decoration: none;
    }

    #manage-category-search-results .dropdown-item:hover,
    #manage-category-search-results .dropdown-item:focus {
        background-color: rgba(15, 94, 156, .08);
    }

    #manage-category-search-results .dropdown-item:not(:last-child) {
        border-bottom: 1px solid rgba(0, 0, 0, .05);
    }

    [data-bs-theme="dark"] #manage-category-search-results,
    body.dark-mode #manage-category-search-results,
    body.theme-dark #manage-category-search-results {
        background: #1f2937;
        border-color: rgba(255, 255, 255, 0.12);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }

    [data-bs-theme="dark"] #manage-category-search-results .dropdown-item,
    body.dark-mode #manage-category-search-results .dropdown-item,
    body.theme-dark #manage-category-search-results .dropdown-item {
        color: #f8fafc;
    }

    [data-bs-theme="dark"] #manage-category-search-results .dropdown-item:not(:last-child),
    body.dark-mode #manage-category-search-results .dropdown-item:not(:last-child),
    body.theme-dark #manage-category-search-results .dropdown-item:not(:last-child) {
        border-bottom-color: rgba(255, 255, 255, 0.08);
    }

    [data-bs-theme="dark"] #manage-category-search-results .dropdown-item:hover,
    [data-bs-theme="dark"] #manage-category-search-results .dropdown-item:focus,
    body.dark-mode #manage-category-search-results .dropdown-item:hover,
    body.dark-mode #manage-category-search-results .dropdown-item:focus,
    body.theme-dark #manage-category-search-results .dropdown-item:hover,
    body.theme-dark #manage-category-search-results .dropdown-item:focus {
        background: rgba(106, 174, 214, 0.16);
    }

    [data-bs-theme="dark"] #manage-category-search-results .dropdown-item .text-dark,
    body.dark-mode #manage-category-search-results .dropdown-item .text-dark,
    body.theme-dark #manage-category-search-results .dropdown-item .text-dark {
        color: #f8fafc !important;
    }

    .category-hero {
        background:
            radial-gradient(circle at top right, rgba(32, 107, 196, .14), transparent 35%),
            linear-gradient(135deg, rgba(32, 107, 196, .05), var(--tblr-bg-surface));
        border: 1px solid var(--tblr-border-color) !important;
    }

    .category-stat {
        transition: all .2s ease;
        border: 1px solid var(--tblr-border-color) !important;
    }

    .category-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, .10) !important;
    }

    .category-table {
        font-size: 13px;
    }

    .category-table thead th {
        background: var(--tblr-bg-surface-secondary);
        color: var(--tblr-muted);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    .category-table tbody td {
        vertical-align: middle;
        padding-top: 14px;
        padding-bottom: 14px;
    }

    .category-name {
        font-weight: 600;
        color: var(--tblr-body-color);
    }

    .category-slug {
        font-size: 12px;
        color: var(--tblr-muted);
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        display: none !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 10px !important;
    }

    [data-bs-theme="dark"] .category-hero,
    body.theme-dark .category-hero,
    body.dark .category-hero {
        background:
            radial-gradient(circle at top right, rgba(32, 107, 196, .24), transparent 35%),
            linear-gradient(135deg, rgba(32, 107, 196, .10), var(--tblr-bg-surface));
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        var table = null;
        if ($('#MyTable').length) {
            table = $('#MyTable').DataTable({
                pageLength: 25,
                autoWidth: false,
                responsive: true,

                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'All']
                ],

                language: {
                    search: '',
                    searchPlaceholder: 'Search category...',
                    lengthMenu: '_MENU_',
                    zeroRecords: 'No category found',
                    info: 'Showing _START_ - _END_ of _TOTAL_ categories',
                    infoEmpty: 'No category available',
                    infoFiltered: '(filtered from _MAX_ total categories)',
                    paginate: {
                        previous: '‹',
                        next: '›'
                    }
                }
            });

            // Bind inline entries selector to DataTable page length
            $('#category-entries-select').on('change', function () {
                var len = parseInt($(this).val(), 10);
                table.page.len(len).draw();
            });
        }

        // Connect live search input to DataTable
        var searchInput = document.getElementById('manage-classification-search-input');
        if (searchInput && table) {
            searchInput.addEventListener('input', function () {
                table.search(this.value.trim()).draw();
            });
        }

        let deleteForm = null;

        $(document).on('click', '.btn-delete-category', function () {
            deleteForm = $(this).closest('form');

            $('#deleteCategoryName').text($(this).data('name'));

            let modal = new bootstrap.Modal(
                document.getElementById('deleteCategoryModal')
            );

            modal.show();
        });

        $('#confirmDeleteCategory').on('click', function () {
            if (deleteForm) {
                deleteForm.submit();
            }
        });
    });
</script>
@endsection
