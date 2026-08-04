<div class="modal fade gallery-filter-modal" id="galleryFilterModal" tabindex="-1"
    aria-labelledby="galleryFilterModalTitle" data-gallery-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="gallery-modal-eyebrow mb-1">Refine results</p>
                    <h2 class="modal-title" id="galleryFilterModalTitle">Filter Gallery</h2>
                </div>
                <a href="#" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></a>
            </div>
            <div class="modal-body">
                <div class="gallery-filter-sections">
                    <fieldset class="gallery-filter-group">
                        <legend>Category</legend>
                        <div class="gallery-check-grid">
                            @forelse($categories as $category)
                                <label class="gallery-check-option">
                                    <input type="checkbox" name="category[]" value="{{ $category }}" @checked(in_array($category, $filters['category'], true))>
                                    <span>{{ $category }}</span>
                                </label>
                            @empty
                                <p class="gallery-option-empty">No categories available.</p>
                            @endforelse
                        </div>
                    </fieldset>
                    <div class="gallery-filter-two-column">
                        <div class="gallery-filter-group">
                            <label for="gallery-modal-uploader" class="gallery-filter-legend">Uploader</label>
                            <select id="gallery-modal-uploader" name="modal_uploader" class="form-select" data-gallery-modal-uploader>
                                <option value="">All Uploaders</option>
                                @foreach($uploaders as $uploader)
                                    <option value="{{ $uploader }}" @selected($filters['uploader'] === $uploader)>{{ $uploader }}</option>
                                @endforeach
                            </select>
                        </div>
                        <fieldset class="gallery-filter-group">
                            <legend>Views</legend>
                            <div class="gallery-radio-stack">
                                @foreach(['' => 'All Views', 'most_viewed' => 'Most Viewed', 'least_viewed' => 'Least Viewed'] as $value => $label)
                                    <label class="gallery-radio-option">
                                        <input type="radio" name="modal_views" value="{{ $value }}" data-gallery-modal-views @checked($filters['views'] === ($value ?: null))>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                    <fieldset class="gallery-filter-group">
                        <legend>Year</legend>
                        <div class="gallery-check-grid gallery-check-grid-compact">
                            @forelse($years as $year)
                                <label class="gallery-check-option">
                                    <input type="checkbox" name="year[]" value="{{ $year }}" @checked(in_array($year, $filters['year'], true))>
                                    <span>{{ $year }}</span>
                                </label>
                            @empty
                                <p class="gallery-option-empty">No event years available.</p>
                            @endforelse
                        </div>
                    </fieldset>
                    <fieldset class="gallery-filter-group">
                        <legend>Month</legend>
                        <div class="gallery-check-grid gallery-month-grid">
                            @foreach($monthOptions as $month => $label)
                                <label class="gallery-check-option">
                                    <input type="checkbox" name="month[]" value="{{ $month }}" @checked(in_array($month, $filters['month'], true))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <fieldset class="gallery-filter-group">
                        <legend>Event Date</legend>
                        <div class="gallery-date-grid">
                            <div>
                                <label for="gallery-date-from" class="form-label">From</label>
                                <input id="gallery-date-from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
                            </div>
                            <div>
                                <label for="gallery-date-to" class="form-label">To</label>
                                <input id="gallery-date-to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
                                <p class="invalid-feedback mb-0" data-gallery-date-error>Date To must be the same as or later than Date From.</p>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="gallery-filter-group">
                        <legend>Likes</legend>
                        <div class="gallery-radio-stack gallery-radio-inline">
                            @foreach(['' => 'All Likes', 'most_liked' => 'Most Liked', 'least_liked' => 'Least Liked'] as $value => $label)
                                <label class="gallery-radio-option">
                                    <input type="radio" name="likes" value="{{ $value }}" @checked($filters['likes'] === ($value ?: null))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('gallery.user.index') }}" class="btn gallery-modal-reset" data-gallery-modal-reset><i class="ti ti-refresh" aria-hidden="true"></i> Reset</a>
                <button type="submit" name="gallery_source" value="advanced" class="btn gallery-modal-apply" data-gallery-modal-apply><i class="ti ti-check" aria-hidden="true"></i> Apply Filters</button>
            </div>
        </div>
    </div>
</div>
