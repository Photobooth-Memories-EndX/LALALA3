@extends('layouts.sidebar-master')

@section('style')
<style>
.admin-news-create-preview {
    width: 100%;
    max-height: 260px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid var(--tblr-border-color);
    display: none;
}
.admin-news-create-placeholder {
    width: 100%;
    height: 180px;
    border-radius: 12px;
    background: var(--tblr-bg-surface-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: .5rem;
    color: var(--tblr-secondary);
    border: 1px dashed var(--tblr-border-color);
}
.btn-back {
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.btn-back:hover {
    opacity: 0.8;
    transform: translateX(-3px);
}
</style>
@endsection

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.news.index') }}" class="btn-back d-inline-flex align-items-center text-decoration-none fw-semibold small" style="color: #0c5b9e !important;">
                <i class="ti ti-arrow-left me-1 fs-5"></i> Back
            </a>
        </div>

        <div class="row align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-blue-lt">
                        <i class="ti ti-news fs-2"></i>
                    </div>
                    <div>
                        <h2 class="page-title mb-1">Create News</h2>
                        <div class="text-secondary">Write and publish a new news article</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <form action="{{ route('news.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
            @csrf

            <div class="row g-4">

                {{-- Main Form --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title">Article Content</h3>
                        </div>
                        <div class="card-body">

                            <div class="mb-3">
                                <label class="form-label required">Title</label>
                                <input type="text"
                                       name="title"
                                       class="form-control @error('title') is-invalid @enderror"
                                       placeholder="Enter news title"
                                       value="{{ old('title') }}"
                                       required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <input type="text"
                                       name="category"
                                       class="form-control @error('category') is-invalid @enderror"
                                       placeholder="Enter category"
                                       value="{{ old('category') }}">
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Description</label>
                                <textarea name="description"
                                          id="description"
                                          rows="10"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Write the news content here..."
                                          required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Sidebar: Image Upload --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title">Featured Image</h3>
                        </div>
                        <div class="card-body">

                            <div class="mb-3">
                                <div id="admin-news-img-placeholder" class="admin-news-create-placeholder mb-3">
                                    <i class="ti ti-photo-plus" style="font-size:2.5rem;"></i>
                                    <span class="small">Image preview will appear here</span>
                                </div>
                                <img id="admin-news-img-preview"
                                     class="admin-news-create-preview mb-3"
                                     alt="Image preview">

                                <label class="form-label required">Upload Image</label>
                                <input type="file"
                                       name="image"
                                       id="newsImageInput"
                                       class="form-control @error('image') is-invalid @enderror"
                                       accept="image/jpeg,image/png,image/jpg,image/gif"
                                       onchange="previewNewsImage(this)"
                                       required>
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint mt-1">JPG, PNG or GIF — max 2MB</div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-success" name="action" value="draft">
                            <i class="ti ti-device-floppy me-1"></i>Save as Draft
                        </button>
                        <button type="submit" class="btn btn-primary" name="action" value="publish">
                            <i class="ti ti-send me-1"></i>Save & Publish
                        </button>
                    </div>
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
                        <i class="ti ti-device-floppy"></i> <span class="btn-save-text">Save as Draft & Exit</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
function previewNewsImage(input) {
    var preview     = document.getElementById('admin-news-img-preview');
    var placeholder = document.getElementById('admin-news-img-placeholder');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src           = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

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
    window.location.href = "{{ route('manage.news.index') }}";
});

// 4. "Save as Draft & Exit" handler
$(document).on('click', '#btnSaveDraftAndExit', function (e) {
    e.preventDefault();
    var saveBtn = this;
    var btnText = saveBtn.querySelector('.btn-save-text');
    saveBtn.disabled = true;
    if (btnText) btnText.textContent = 'Saving Draft...';

    var newsForm = document.querySelector('form');
    if (newsForm) {
        var formData = new FormData(newsForm);
        formData.set('action', 'draft');

        fetch(newsForm.action, {
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
            window.location.href = "{{ route('manage.news.index') }}";
        })
        .catch(function (err) {
            console.error('AJAX draft save failed, submitting fallback:', err);
            var actionInput = newsForm.querySelector('input[name="action"]');
            if (!actionInput) {
                actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                newsForm.appendChild(actionInput);
            }
            actionInput.value = 'draft';
            newsForm.submit();
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
        window.location.href = "{{ route('manage.news.index') }}";
        return;
    }
});
</script>
@endsection
