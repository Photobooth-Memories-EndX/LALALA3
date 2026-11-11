@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <!-- Back Button to Category Index -->
        <div class="mb-3">
            <a href="{{ route('manage.classification.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none">
                <i class="ti ti-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Edit Category
                </h2>

                <div class="text-muted mt-1">
                    Update OWL LMS learning category details.
                </div>
            </div>
        </div>

    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- HERO --}}
        <div class="card category-edit-hero border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="text-muted text-uppercase small fw-semibold mb-1">
                            Category Management
                        </div>

                        <h1 class="mb-2">
                            Update Category
                        </h1>

                        <div class="text-muted">
                            Update category information used for content, playlists, assessments, and reporting.
                        </div>
                    </div>

                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <span class="avatar avatar-lg bg-warning-lt text-warning">
                            <i class="ti ti-edit fs-2"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- FORM --}}
        <form
            id="categoryEditForm"
            class="card border-0 shadow-sm rounded-4 overflow-hidden"
            action="{{ route('manage.classification.update', $classification->slug) }}"
            method="post">
            @csrf
            @method('PUT')

            <div class="card-header border-0">
                <div>
                    <h3 class="card-title mb-1">
                        Category Information
                    </h3>

                    <div class="text-muted small">
                        Edit category name and details.
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="mb-4">
                            <label class="form-label required">
                                Category Name
                            </label>

                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-category"></i>
                                </span>

                                <input
                                    type="text"
                                    name="classification_name"
                                    class="form-control @error('classification_name') is-invalid @enderror"
                                    placeholder="Example: Leadership Development"
                                    value="{{ old('classification_name', $classification->classification_name) }}"
                                    required
                                />
                            </div>

                            @error('classification_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <small class="form-hint">
                                Category name is used across content, learning paths, assessment, and filtering.
                            </small>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">
                                Current Slug
                            </label>

                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-link"></i>
                                </span>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{ $classification->slug }}"
                                    readonly>
                            </div>

                            <small class="form-hint">
                                Slug is used as a unique identifier for this category.
                            </small>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card bg-warning-lt border-0 h-100">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">
                                    Update Tips
                                </div>

                                <ul class="mb-0 small">
                                    <li>
                                        Keep the category name clear and unambiguous.
                                    </li>
                                    <li>
                                        Avoid changing widely used categories unless necessary.
                                    </li>
                                    <li>
                                        Verify the content filters and learning reports after updating.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4 btn-delete-single-classification" data-id="{{ $classification->id }}" data-name="{{ $classification->classification_name }}">
                        <i class="ti ti-trash me-1"></i>
                        Delete Category
                    </button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4">
                        <i class="ti ti-device-floppy me-1"></i>
                        Update Category
                    </button>
                </div>
            </div>

        </form>

    </div>
</div>

{{-- Unsaved Changes Confirmation Modal --}}
<div class="modal modal-blur fade" id="unsavedChangesModal" tabindex="-1" role="dialog" aria-labelledby="unsavedChangesModalLabel" aria-hidden="true" data-bs-backdrop="static" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow rounded-3 border-0">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center bg-warning-lt text-warning rounded" style="width: 20px; height: 20px; font-size: 14px;">
                        <i class="ti ti-alert-triangle" style="font-size: 14px;"></i>
                    </span>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="unsavedChangesModalLabel">Unsaved Changes</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="text-dark mb-0" style="color: #212529 !important; font-size: 0.95rem; line-height: 1.5;">
                    Are you sure you want to leave? Any unsaved changes will be lost, or you can stay to finish editing.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center gap-1" id="btnDiscardAndLeave">
                    <i class="ti ti-trash"></i> Discard & Leave
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-info text-white" id="btnKeepEditing" data-bs-dismiss="modal" data-dismiss="modal">
                        Keep Editing
                    </button>
                    <button type="button" class="btn btn-warning d-inline-flex align-items-center gap-1" id="btnSaveDraftAndExit">
                        <i class="ti ti-device-floppy"></i> <span class="btn-save-text">Save & Exit</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
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

    .category-edit-hero {
        background:
            radial-gradient(
                circle at top right,
                rgba(245, 159, 0, .18),
                transparent 35%
            ),
            linear-gradient(
                135deg,
                rgba(245, 159, 0, .06),
                var(--tblr-bg-surface)
            );

        border: 1px solid var(--tblr-border-color);
    }

    [data-bs-theme="dark"] .category-edit-hero,
    body.theme-dark .category-edit-hero {
        background:
            radial-gradient(
                circle at top right,
                rgba(245, 159, 0, .25),
                transparent 35%
            ),
            linear-gradient(
                135deg,
                rgba(245, 159, 0, .10),
                var(--tblr-bg-surface)
            );
    }
</style>
@endsection

@section('script')
<script>
    function openUnsavedChangesModal() {
        var modalEl = document.getElementById('unsavedChangesModal');
        if (!modalEl) return;

        if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
            $(modalEl).modal('show');
        } else if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
            var modalInstance = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
            modalInstance.show();
        } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            var modalInstance = window.bootstrap.Modal.getOrCreateInstance ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : new window.bootstrap.Modal(modalEl);
            modalInstance.show();
        } else {
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');
            if (!document.querySelector('.modal-backdrop')) {
                var bd = document.createElement('div');
                bd.className = 'modal-backdrop fade show';
                document.body.appendChild(bd);
            }
        }
    }

    function closeUnsavedChangesModal() {
        var modalEl = document.getElementById('unsavedChangesModal');
        if (!modalEl) return;

        if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
            $(modalEl).modal('hide');
        } else if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
            var modalInstance = bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(modalEl) : null;
            if (modalInstance) {
                modalInstance.hide();
            } else {
                (new bootstrap.Modal(modalEl)).hide();
            }
        } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            var modalInstance = window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance(modalEl) : null;
            if (modalInstance) {
                modalInstance.hide();
            }
        } else {
            modalEl.style.display = 'none';
            modalEl.classList.remove('show');
            document.body.classList.remove('modal-open');
            var bd = document.querySelector('.modal-backdrop');
            if (bd) bd.remove();
        }
    }

    // 1. Back button click handler (both jQuery and native)
    $(document).on('click', '.btn-back', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openUnsavedChangesModal();
    });

    // 2. Modal dismissal handlers ("X" and "Keep Editing")
    $(document).on('click', '#unsavedChangesModal .btn-close, #unsavedChangesModal [data-bs-dismiss="modal"], #unsavedChangesModal [data-dismiss="modal"], #btnKeepEditing', function (e) {
        e.preventDefault();
        closeUnsavedChangesModal();
    });

    // 3. "Discard & Leave" handler
    $(document).on('click', '#btnDiscardAndLeave', function (e) {
        e.preventDefault();
        window.location.href = "{{ route('manage.classification.index') }}";
    });

    // 4. "Save & Exit" handler
    $(document).on('click', '#btnSaveDraftAndExit', function (e) {
        e.preventDefault();
        var saveBtn = this;
        var btnText = saveBtn.querySelector('.btn-save-text');
        saveBtn.disabled = true;
        if (btnText) btnText.textContent = 'Updating...';

        var form = document.getElementById('categoryEditForm') || document.querySelector('form');
        if (form) {
            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (err) { throw err; });
                }
                return response.json();
            })
            .then(function (data) {
                window.location.href = "{{ route('manage.classification.index') }}";
            })
            .catch(function (err) {
                console.error('AJAX update failed, submitting fallback:', err);
                form.submit();
            });
        }
    });

    // Vanilla fallback listener
    document.addEventListener('click', function (e) {
        var backBtn = e.target.closest('.btn-back');
        if (backBtn) {
            e.preventDefault();
            e.stopPropagation();
            openUnsavedChangesModal();
            return;
        }

        var dismissBtn = e.target.closest('#unsavedChangesModal .btn-close, #unsavedChangesModal [data-bs-dismiss="modal"], #unsavedChangesModal [data-dismiss="modal"], #btnKeepEditing');
        if (dismissBtn) {
            e.preventDefault();
            closeUnsavedChangesModal();
            return;
        }

        var discardBtn = e.target.closest('#btnDiscardAndLeave');
        if (discardBtn) {
            e.preventDefault();
            window.location.href = "{{ route('manage.classification.index') }}";
            return;
        }
    });

    $(document).on('click', '.btn-delete-single-classification', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Classification?',
            html: 'Apakah Anda yakin ingin menghapus <strong>"' + name + '"</strong>?<br><span class="text-muted small mt-1 d-block">Data akan dihapus permanen jika tidak memiliki relasi dengan Learning Content atau Training Records.</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ti ti-trash me-1"></i> Ya, Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    html: '<span class="text-muted small">Memeriksa relasi data dan menghapus...</span>',
                    allowOutsideClick: false,
                    didOpen: function () { Swal.showLoading(); }
                });

                $.ajax({
                    url: '/manage/classification/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Dihapus!',
                            text: res.message || 'Classification berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function () {
                            window.location.href = "{{ route('manage.classification.index') }}";
                        });
                    },
                    error: function (xhr) {
                        var msg = 'Gagal menghapus classification.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({ icon: 'error', title: 'Tidak Dapat Dihapus', text: msg });
                    }
                });
            }
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
