<div class="modal fade gallery-filter-modal" id="galleryFilterModal" tabindex="-1" aria-hidden="true" data-gallery-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title fw-bold">
                    <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                    More Filters
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                {{-- 1. Uploader / Organizer (Full Width) --}}
                <div class="learning-filter-group mb-3">
                    <label class="form-label fw-semibold" for="gallery-modal-uploader">Uploader / Organizer</label>
                    <select id="gallery-modal-uploader" name="uploader" class="form-select rounded-3" data-gallery-modal-uploader>
                        <option value="">All Uploaders / Organizers</option>
                        @foreach($uploaders as $uploader)
                            <option value="{{ $uploader }}" @selected($filters['uploader'] === $uploader)>{{ $uploader }}</option>
                        @endforeach
                    </select>
                </div>

                <hr class="my-3">

                {{-- 2. Type & Sort by (2 Kolom) --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="gallery-modal-type">Type / Category</label>
                        <select id="gallery-modal-type" name="category[]" class="form-select rounded-3">
                            <option value="">All Types</option>
                            <option value="On-site" @selected(in_array('On-site', $filters['category'], true))>On-site</option>
                            <option value="Off-site" @selected(in_array('Off-site', $filters['category'], true))>Off-site</option>
                            @foreach($categories as $category)
                                @if(!in_array($category, ['On-site', 'Off-site'], true))
                                    <option value="{{ $category }}" @selected(in_array($category, $filters['category'], true))>{{ $category }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="gallery-modal-sort">Sort by</label>
                        <select id="gallery-modal-sort" name="sort" class="form-select rounded-3">
                            <option value="latest" @selected($filters['sort'] === 'latest')>Newest</option>
                            <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest</option>
                            <option value="az" @selected($filters['sort'] === 'az')>Ascending (A–Z)</option>
                            <option value="za" @selected($filters['sort'] === 'za')>Descending (Z–A)</option>
                            <option value="most_viewed" @selected($filters['sort'] === 'most_viewed')>Most Viewed</option>
                            <option value="least_viewed" @selected($filters['sort'] === 'least_viewed')>Least Viewed</option>
                        </select>
                    </div>
                </div>

                <hr class="my-3">

                {{-- 3. Event Date Range (2 Kolom) --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2"><i class="ti ti-calendar me-1"></i>Event Date</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="gallery-date-from" class="form-label small text-muted mb-1"><i class="ti ti-calendar-event me-1"></i>From Date</label>
                            <input id="gallery-date-from" type="{{ $filters['date_from'] ? 'date' : 'text' }}"
                                   name="date_from" value="{{ $filters['date_from'] }}"
                                   class="form-control rounded-3" placeholder="dd/mm/yyyy"
                                   onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="gallery-date-to" class="form-label small text-muted mb-1"><i class="ti ti-calendar-event me-1"></i>To Date</label>
                            <input id="gallery-date-to" type="{{ $filters['date_to'] ? 'date' : 'text' }}"
                                   name="date_to" value="{{ $filters['date_to'] }}"
                                   class="form-control rounded-3" placeholder="dd/mm/yyyy"
                                   onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                <!-- Reset Button (Placed on the left of Apply Filter) -->
                <a href="{{ route('gallery.user.index') }}"
                   id="gallery-modal-reset-btn"
                   class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1"
                   data-gallery-modal-reset>
                    <i class="ti ti-rotate me-1"></i>
                    <span>Reset</span>
                </a>

                <!-- Apply Filter Button -->
                <button type="submit" name="gallery_source" value="advanced" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" data-gallery-modal-apply>
                    <i class="ti ti-check me-1"></i>
                    <span>Apply Filter</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateGalleryModalResetState() {
        var modal = document.getElementById('galleryFilterModal');
        var resetBtn = document.getElementById('gallery-modal-reset-btn');
        if (!modal || !resetBtn) return;

        var uploader = modal.querySelector('[name="uploader"]')?.value || '';
        var category = modal.querySelector('[name="category[]"]')?.value || '';
        var sort = modal.querySelector('[name="sort"]')?.value || 'latest';
        var dateFrom = modal.querySelector('[name="date_from"]')?.value || '';
        var dateTo = modal.querySelector('[name="date_to"]')?.value || '';

        var hasFilter = uploader !== '' || category !== '' || (sort !== 'latest' && sort !== '') || dateFrom !== '' || dateTo !== '';

        if (hasFilter) {
            resetBtn.className = 'btn btn-outline-primary rounded-3 fw-semibold';
            resetBtn.style.opacity = '1';
        } else {
            resetBtn.className = 'btn btn-outline-secondary rounded-3 opacity-50 text-muted';
            resetBtn.style.opacity = '0.5';
        }
    }

    var modal = document.getElementById('galleryFilterModal');
    if (modal) {
        modal.querySelectorAll('select, input').forEach(function(el) {
            el.addEventListener('change', updateGalleryModalResetState);
            el.addEventListener('input', updateGalleryModalResetState);
        });

        modal.addEventListener('shown.bs.modal', updateGalleryModalResetState);
        updateGalleryModalResetState();
    }
});
</script>
