<div class="col-12">

    <div class="card border-0 shadow-sm owl-empty-state">

        <div class="card-body text-center py-6">

            {{-- ICON --}}
            <div class="mb-4">
                <i class="ti ti-search fs-1 text-secondary opacity-50"></i>
            </div>

            {{-- HEADING --}}
            <h3 class="fw-bold mb-2">
                No matching articles found
            </h3>

            {{-- DESCRIPTION --}}
            <p class="text-secondary mb-5">
                Try adjusting your search terms or filters to find what you're looking for.
            </p>

            {{-- ACTIONS --}}
            <div class="d-flex gap-2 justify-content-center flex-wrap">

                <a href="{{ route('news.user.index') }}"
                   class="btn btn-primary rounded-pill px-4">
                    <i class="ti ti-arrow-left me-2"></i>
                    Back to All Articles
                </a>

                <button class="btn btn-outline-primary rounded-pill px-4"
                        onclick="document.getElementById('owl-search-input')?.focus()">
                    <i class="ti ti-search me-2"></i>
                    Try Different Search
                </button>

            </div>

        </div>

    </div>

</div>
