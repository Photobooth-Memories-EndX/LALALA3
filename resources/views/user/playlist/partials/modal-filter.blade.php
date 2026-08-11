<div class="modal fade" id="playlistMoreFiltersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title fw-bold">
                    <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                    More Filters
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-2">
                {{-- Author --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Author</div>
                    <select id="playlistFilterAuthor" name="author" class="form-select rounded-3">
                        <option value="">All Authors</option>
                        @foreach ($playlistAuthors as $author)
                            <option value="{{ $author }}">{{ $author }}</option>
                        @endforeach
                    </select>
                </div>

                <hr class="my-3">

                {{-- Created Date --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Created Date</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="playlistFilterCreatedFromDate">From Date</label>
                            <input type="text" id="playlistFilterCreatedFromDate" class="form-control rounded-3" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="playlistFilterCreatedToDate">To Date</label>
                            <input type="text" id="playlistFilterCreatedToDate" class="form-control rounded-3" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Period --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Period</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="playlistFilterDeadlineFromDate">From Date</label>
                            <input type="text" id="playlistFilterDeadlineFromDate" class="form-control rounded-3" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="playlistFilterDeadlineToDate">To Date</label>
                            <input type="text" id="playlistFilterDeadlineToDate" class="form-control rounded-3" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Sort By --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Sort By</div>
                    <select id="playlistFilterSort" class="form-select rounded-3">
                        <option value="all" selected>All</option>
                        <option value="oldest">Oldest</option>
                        <option value="newest">Newest</option>
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>

                <hr class="my-3">

                {{-- Sort Views --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Sort Views</div>
                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_view_order" value="" checked>
                            <span class="form-check-label">Default</span>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_view_order" value="most_viewed">
                            <span class="form-check-label">Most Viewed</span>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_view_order" value="least_viewed">
                            <span class="form-check-label">Least Viewed</span>
                        </label>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Playlist Duration --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Playlist Duration</div>
                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_duration" value="all" checked>
                            <span class="form-check-label">All Durations</span>
                        </label>
                        @foreach ($durationOptions as $val => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="playlist_duration" value="{{ $val }}">
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <hr class="my-3">

                {{-- Total Content --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Total Content</div>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ($totalPageOptions as $val => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="playlist_total_pages[]" value="{{ $val }}">
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <hr class="my-3">

                {{-- LEARNING HOUR --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Learning Hour</div>
                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_learning_hour" value="all" checked>
                            <span class="form-check-label">All</span>
                        </label>
                        @foreach ($learningHourOptions as $val => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="playlist_learning_hour" value="{{ $val }}">
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn content-reset-btn rounded-3" id="playlistResetMoreFilters">
                    <i class="ti ti-refresh me-1"></i>
                    Reset
                </button>
                <button type="button" class="btn btn-primary rounded-3" id="playlistApplyMoreFilters" data-bs-dismiss="modal" data-dismiss="modal">
                    <i class="ti ti-check me-1"></i>
                    Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>
