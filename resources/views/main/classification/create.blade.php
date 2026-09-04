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
                    Create Category
                </h2>

                <div class="text-muted mt-1">
                    Add a new category to organize content and learning modules in OWL LMS.
                </div>
            </div>
        </div>

    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- HERO --}}
        <div class="card category-create-hero border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="text-muted text-uppercase small fw-semibold mb-1">
                            Category Management
                        </div>

                        <h1 class="mb-2">
                            Create New Category
                        </h1>

                        <div class="text-muted">
                            Categories are used to organize learning content, playlists, assessments, and reports for better organization.
                        </div>
                    </div>

                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <span class="avatar avatar-lg bg-primary-lt text-primary">
                            <i class="ti ti-category fs-2"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- FORM --}}
        <form
            id="categoryForm"
            class="card border-0 shadow-sm rounded-4 overflow-hidden"
            action="{{ route('manage.classification.store') }}"
            method="post">
            @csrf

            <div class="card-header border-0">
                <div>
                    <h3 class="card-title mb-1">
                        Category Information
                    </h3>

                    <div class="text-muted small">
                        Enter the category information to be used in OWL LMS.
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row">
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
                                    value="{{ old('classification_name') }}"
                                    required
                                />
                            </div>

                            @error('classification_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <small class="form-hint">
                                Category names are used for content, playlists, assessments, and reporting.
                            </small>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card bg-primary-lt border-0">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">
                                    Tips
                                </div>

                                <ul class="mb-0 small">
                                    <li>
                                        Use a clear and easy-to-understand category name.
                                    </li>
                                    <li>
                                        Avoid using overly long category names.
                                    </li>
                                    <li>
                                        Make sure the category name is unique.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="ti ti-device-floppy me-1"></i>
                        Save Category
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
                    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="btnSaveDraftAndExit">
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

    .category-create-hero {
        background:
            radial-gradient(
                circle at top right,
                rgba(32,107,196,.15),
                transparent 35%
            ),
            linear-gradient(
                135deg,
                rgba(32,107,196,.05),
                var(--tblr-bg-surface)
            );

        border: 1px solid var(--tblr-border-color);
    }

    [data-bs-theme="dark"] .category-create-hero,
    body.theme-dark .category-create-hero {
        background:
            radial-gradient(
                circle at top right,
                rgba(32,107,196,.25),
                transparent 35%
            ),
            linear-gradient(
                135deg,
                rgba(32,107,196,.10),
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
        if (btnText) btnText.textContent = 'Saving...';

        var form = document.getElementById('categoryForm') || document.querySelector('form');
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
                console.error('AJAX save failed, submitting fallback:', err);
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
</script>
@endsection
