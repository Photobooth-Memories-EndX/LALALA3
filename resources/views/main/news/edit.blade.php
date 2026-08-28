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
</style>
@endsection

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-blue-lt">
                        <i class="ti ti-pencil fs-2"></i>
                    </div>
                    <div>
                        <h2 class="page-title mb-1">Edit News</h2>
                        <div class="text-secondary">Update news article content and image</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <a href="{{ route('manage.news.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

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
                        <a href="{{ route('manage.news.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>Update News
                        </button>
                    </div>
                </div>
            </div>

        </form>

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
</script>
@endsection
