@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">

            <div class="col">
                <h2 class="page-title">
                    Manage Category
                </h2>
            </div>

            <div class="col-auto ms-auto d-print-none">
                <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                    <li class="breadcrumb-item">
                        <a href="#">Manage</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <a href="#">Category</a>
                    </li>
                </ol>
            </div>

        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        {{-- HERO --}}
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
                            Kelola kategori pembelajaran OWL LMS untuk content, classification, dan reporting.
                        </div>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <a href="{{ route('classification.create') }}"
                           class="btn btn-primary rounded-pill px-4">
                            <i class="ti ti-plus me-1"></i>
                            Add Category
                        </a>
                    </div>

                </div>
            </div>
        </div>

        {{-- STATS --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card category-stat border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">
                                    Total Categories
                                </div>

                                <h2 class="mb-0">
                                    {{ count($classification) }}
                                </h2>
                            </div>

                            <span class="avatar bg-primary-lt text-primary">
                                <i class="ti ti-category"></i>
                            </span>
                        </div>
                    </div>
                </div>
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
                        Daftar seluruh kategori yang tersedia pada OWL LMS.
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
                                                    <a href="{{ route('classification.edit', $list->slug) }}"
                                                       class="btn btn-outline-primary btn-sm rounded-pill"
                                                       title="Edit Category">
                                                        <i class="ti ti-edit me-1"></i>
                                                        Edit
                                                    </a>

                                                    <form action="{{ route('classification.destroy', $list->slug) }}"
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

                            <a href="{{ route('classification.create') }}"
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

    .dataTables_wrapper .dataTables_filter input {
        border-radius: 999px;
        padding-left: 14px;
        padding-right: 14px;
    }

    .dataTables_wrapper .dataTables_length select {
        min-width: 75px;
        border-radius: 999px;
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
        if ($('#MyTable').length) {
            $('#MyTable').DataTable({
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
