@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none mb-0 pb-0">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <div class="mb-3">
            <a href="{{ url('/manage') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                <i class="ti ti-arrow-left fs-3"></i><span>Back</span>
            </a>
        </div>

        <!-- Category Management Hero Header Box -->
        <div class="card border-0 shadow-sm p-4 mb-3 rounded-3 bg-white">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <!-- Left Group: Icon Box + Title & Subtitle -->
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-category-2" style="font-size: 2rem !important;"></i>
                    </div>

                    <!-- Title & Subtitle -->
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Category Management</h2>
                        <p class="text-muted small mb-0">Manage OWL LMS learning categories for content organization, classification, and reporting.</p>
                    </div>
                </div>

                <!-- Right Group: Primary Action Button -->
                <div class="flex-shrink-0">
                    <a href="{{ route('manage.classification.create') }}" class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" 
                       style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-plus fs-2"></i><span>Add Category</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body mt-0 pt-0">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        {{-- SEARCH & ENTRIES TOOLBAR (Quick Filter) --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <!-- Label Header Quick Filter -->
                <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                    Quick Filter
                </div>
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

                        <!-- Action Buttons (Search submit, More Filters & Reset) -->
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <!-- 1. Search Submit Button -->
                            <button type="submit" class="btn btn-primary btn-icon px-3" title="Search">
                                <i class="ti ti-search fs-2"></i>
                            </button>

                            <!-- 2. More Filters Modal Trigger Button (Icon Only - No Text) -->
                            <button type="button" class="btn btn-outline-primary btn-icon px-3 position-relative" data-bs-toggle="modal" data-bs-target="#categoryFilterModal" title="More Filters">
                                <i class="ti ti-adjustments-horizontal fs-2"></i>
                            </button>

                            <!-- 3. Reset Button -->
                            <a href="{{ route('manage.classification.index') }}" class="btn btn-outline-secondary btn-icon px-3" title="Reset Filters">
                                <i class="ti ti-refresh fs-2"></i>
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

<!-- Category / Classification More Filters Modal (Shell) -->
<div class="modal fade" id="categoryFilterModal" tabindex="-1" aria-labelledby="categoryFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="categoryFilterModalLabel">
                    <i class="ti ti-adjustments-horizontal me-2"></i>More Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-filter-off text-muted fs-1 mb-2"></i>
                <p class="text-muted mb-0">No additional filters available for categories.</p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
