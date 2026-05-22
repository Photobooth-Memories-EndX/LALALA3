<div class="card border-0 shadow-sm mb-4 content-filter-card">
    <div class="card-body">
        <form method="GET" action="{{ route('content.user.index') }}" class="row g-3">

            <div class="col-12">
                <label class="form-label">Search Content</label>
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>
                    <input type="text"
                           name="s"
                           value="{{ $filterSearch }}"
                           class="form-control"
                           placeholder="Search learning, content, author...">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="classification" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach ($classifications as $classification)
                        <option value="{{ $classification->id }}" {{ $filterClassification == $classification->id ? 'selected' : '' }}>
                            {{ $classification->classification_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="Document" {{ $filterType == 'Document' ? 'selected' : '' }}>Document</option>
                    <option value="Video" {{ $filterType == 'Video' ? 'selected' : '' }}>Video</option>
                    <option value="YouTube" {{ $filterType == 'YouTube' ? 'selected' : '' }}>YouTube</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Author</label>
                <select name="author" class="form-select" onchange="this.form.submit()">
                    <option value="">All Authors</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author }}" {{ $filterAuthor == $author ? 'selected' : '' }}>
                            {{ $author }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Sort Date</label>
                <select name="date_order" class="form-select" onchange="this.form.submit()">
                    <option value="newest" {{ $filterDateOrder == 'newest' ? 'selected' : '' }}>Newest</option>
                    <option value="oldest" {{ $filterDateOrder == 'oldest' ? 'selected' : '' }}>Oldest</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Sort Views</label>
                <select name="view_order" class="form-select" onchange="this.form.submit()">
                    <option value="">Default</option>
                    <option value="most_viewed" {{ $filterViewOrder == 'most_viewed' ? 'selected' : '' }}>Most Viewed</option>
                    <option value="least_viewed" {{ $filterViewOrder == 'least_viewed' ? 'selected' : '' }}>Least Viewed</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-search me-1"></i>
                    Search
                </button>

                @if ($filterSearch || $filterClassification || $filterType || $filterAuthor || $filterViewOrder || $filterDateOrder != 'newest')
                    <a href="{{ route('content.user.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-refresh"></i>
                    </a>
                @endif
            </div>

        </form>
    </div>
</div>