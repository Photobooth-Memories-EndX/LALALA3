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
</style>
@endsection

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
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
            <div class="col-auto">
                <a href="{{ route('news.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
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
                        <a href="{{ route('news.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i>Publish News
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
</script>
@endsection
