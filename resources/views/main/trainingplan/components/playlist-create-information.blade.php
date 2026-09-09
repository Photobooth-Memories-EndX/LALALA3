<div class="card owl-main-card mb-3">
    <div class="card-header d-flex align-items-center">
        <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">1</span>
        <h3 class="card-title mb-0">Playlist Information</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Title --}}
            <div class="col-12">
                <label for="title" class="form-label required">Title</label>
                <input type="text"
                       class="form-control @error('title') is-invalid @enderror"
                       placeholder="Enter playlist title"
                       name="title"
                       id="title"
                       value="{{ old('title') }}"
                       required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- URL Link --}}
            <div class="col-12">
                <label for="url-link" class="form-label">URL Link</label>
                <input type="url"
                       name="url_link"
                       id="url-link"
                       class="form-control @error('url_link') is-invalid @enderror"
                       value="{{ old('url_link') }}"
                       placeholder="https://...">
                <small class="form-hint">Optional. An external link related to this playlist.</small>
                @error('url_link')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>