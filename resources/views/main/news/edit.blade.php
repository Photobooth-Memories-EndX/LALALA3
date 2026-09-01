@extends('layouts.sidebar-master')

@section('style')
<style>
.admin-news-edit-current-img {
    width: 100%;
    max-height: 220px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid var(--tblr-border-color);
}
.admin-news-edit-preview {
    width: 100%;
    max-height: 220px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid var(--tblr-border-color);
    display: none;
}
.admin-news-edit-placeholder {
    width: 100%;
    height: 160px;
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
    color: #0c5b9e !important;
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.btn-back:hover {
    opacity: 0.85;
    transform: translateX(-3px);
}
</style>
@endsection

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <a href="{{ route('manage.news.index') }}" class="btn-back mb-3 d-inline-flex align-items-center gap-1 text-decoration-none fw-semibold small">
            <i class="ti ti-arrow-left"></i>
            <span>Back</span>
        </a>
        <div class="row align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-{{ ($news->active ?? 'Active') == 'Active' ? 'blue' : 'warning' }}-lt">
                        <i class="ti ti-pencil fs-2"></i>
                    </div>
                    <div>
                        <h2 class="page-title mb-1">Edit News</h2>
                        <div class="text-secondary">Update news article content and image</div>
                    </div>
                </div>
            </div>
            <div class="col-auto d-flex align-items-center gap-2">
                <span class="badge bg-{{ ($news->active ?? 'Active') == 'Active' ? 'success' : 'warning' }}-lt text-{{ ($news->active ?? 'Active') == 'Active' ? 'success' : 'warning' }} fs-5">
                    {{ $news->active ?? 'Active' }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        <form action="{{ route('news.update', $news->slug) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
            @csrf
            @method('PUT')

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
                                       value="{{ old('title', $news->title) }}"
                                       required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category</label>
                                    <input type="text"
                                           name="category"
                                           class="form-control @error('category') is-invalid @enderror"
                                           placeholder="Enter category"
                                           value="{{ old('category', $news->category) }}">
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Status</label>
                                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                        <option value="approved" {{ old('status', $news->status ?? 'approved') == 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="reject" {{ old('status', $news->status) == 'reject' ? 'selected' : '' }}>Reject</option>
                                        <option value="pending" {{ old('status', $news->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Description</label>
                                <textarea name="description"
                                          id="description"
                                          rows="10"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Write the news content here..."
                                          required>{{ old('description', $news->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Sidebar: Image --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title">Featured Image</h3>
                        </div>
                        <div class="card-body">

                            {{-- Current Image --}}
                            @if($news->image)
                            <div id="admin-news-current-img-wrapper" class="mb-3">
                                <p class="text-secondary small mb-2">Current image:</p>
                                <img src="{{ asset('storage/news/'.$news->image) }}"
                                     class="admin-news-edit-current-img"
                                     alt="{{ $news->title }}"
                                     onerror="this.outerHTML='<div class=\'admin-news-edit-placeholder\'><i class=\'ti ti-photo-off\' style=\'font-size:2rem;\'></i><span class=\'small\'>Image not found</span></div>'">
                                <div class="mt-2">
                                    <button type="button"
                                            class="btn btn-sm btn-danger btn-remove-img"
                                            data-url="{{ route('news.deleteImage', $news->slug) }}">
                                        <i class="ti ti-trash me-1"></i>Delete Image
                                    </button>
                                </div>
                            </div>
                            @endif

                            {{-- New Image Upload --}}
                            <div class="mb-3">
                                <img id="admin-news-edit-preview"
                                     class="admin-news-edit-preview mb-3"
                                     alt="New image preview">

                                @if(!$news->image)
                                <div id="admin-news-edit-placeholder" class="admin-news-edit-placeholder mb-3">
                                    <i class="ti ti-photo-plus" style="font-size:2rem;"></i>
                                    <span class="small">Upload a new image</span>
                                </div>
                                @endif

                                <label class="form-label">Replace Image</label>
                                <input type="file"
                                       name="image"
                                       id="newsImageInput"
                                       class="form-control @error('image') is-invalid @enderror"
                                       accept="image/jpeg,image/png,image/jpg,image/gif"
                                       onchange="previewNewsEditImage(this)">
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint mt-1">JPG, PNG or GIF — max 2MB. Leave empty to keep current.</div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        @if(($news->active ?? 'Active') == 'Active')
                            <button type="submit" class="btn btn-warning" name="action" value="unpublish">
                                <i class="ti ti-eye-off me-1"></i>Unpublish
                            </button>
                        @else
                            <button type="submit" class="btn btn-primary" name="action" value="publish">
                                <i class="ti ti-send me-1"></i>Save & Publish
                            </button>
                        @endif
                        <button type="submit" class="btn btn-info text-white" name="action" value="save">
                            <i class="ti ti-device-floppy me-1"></i>Save Changes
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
function previewNewsEditImage(input) {
    var preview     = document.getElementById('admin-news-edit-preview');
    var placeholder = document.getElementById('admin-news-edit-placeholder');
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

$(document).on('click', '.btn-remove-img', function(e) {
    e.preventDefault();
    if (!confirm('Are you sure you want to delete this image?')) return;
    var url     = $(this).data('url');
    var wrapper = $(this).closest('#admin-news-current-img-wrapper');
    $.ajax({
        type: 'PUT',
        url:  url,
        data: { '_token': '{{ csrf_token() }}' },
        success: function(data) {
            if (data.status == 200) {
                wrapper.fadeOut(300, function() { $(this).remove(); });
            }
        },
        error: function() {
            alert('Failed to delete image. Please try again.');
        }
    });
});

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
