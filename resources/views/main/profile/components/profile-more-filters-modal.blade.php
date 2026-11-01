<!-- More Filters Modal for Profile Learning History (Standardized to Content OWL Design) -->
<div class="modal fade" id="profileMoreFiltersModal" tabindex="-1" aria-labelledby="profileMoreFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="profile-more-filters-modal-form" onsubmit="return false;">
                @csrf
                <!-- Standardized Modal Header matching Content OWL -->
                <div class="modal-header px-4 py-3 border-bottom bg-white">
                    <div class="d-flex align-items-center gap-2.5 flex-grow-1 min-w-0">
                        <div class="avatar avatar-sm bg-primary-lt text-primary rounded-2 flex-shrink-0">
                            <i class="ti ti-adjustments-horizontal fs-3"></i>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" id="profileMoreFiltersModalLabel">More Filters</h5>
                            <small class="text-muted d-block lh-1">Filter learning history by date range, progress, and duration</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body px-4 py-3 bg-light">
                    <!-- Section 1: Date Range -->
                    <div class="border rounded-3 p-3 mb-3 bg-white owl-modal-card">
                        <label class="form-label fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5">
                            <i class="ti ti-calendar text-primary"></i>
                            <span>Date Range (Learning Date)</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="form-label small text-muted mb-1">From Date</label>
                                <input type="text" id="profile-modal-from-date" name="from_date"
                                       onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'"
                                       class="form-control rounded-3" placeholder="dd/mm/yyyy"
                                       value="{{ request('from_date') ?? request('date_from') }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-muted mb-1">To Date</label>
                                <input type="text" id="profile-modal-to-date" name="to_date"
                                       onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'"
                                       class="form-control rounded-3" placeholder="dd/mm/yyyy"
                                       value="{{ request('to_date') ?? request('date_to') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Progress Range -->
                    <div class="border rounded-3 p-3 mb-3 bg-white owl-modal-card">
                        <label class="form-label fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5">
                            <i class="ti ti-progress text-primary"></i>
                            <span>Progress Range</span>
                        </label>
                        @php $currProgress = request('progress_range', 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_progress_range" id="profilePrAll" value="all" {{ ($currProgress === 'all' || blank($currProgress)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profilePrAll">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_progress_range" id="profilePr0" value="0" {{ $currProgress === '0' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profilePr0">0%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_progress_range" id="profilePr25" value="1-25" {{ $currProgress === '1-25' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profilePr25">1 - 25%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_progress_range" id="profilePr50" value="26-50" {{ $currProgress === '26-50' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profilePr50">26 - 50%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_progress_range" id="profilePr75" value="51-75" {{ $currProgress === '51-75' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profilePr75">51 - 75%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_progress_range" id="profilePr99" value="76-99" {{ $currProgress === '76-99' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profilePr99">76 - 99%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_progress_range" id="profilePr100" value="100" {{ $currProgress === '100' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profilePr100">100%</label>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Duration -->
                    <div class="border rounded-3 p-3 bg-white owl-modal-card">
                        <label class="form-label fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5">
                            <i class="ti ti-clock text-primary"></i>
                            <span>Duration</span>
                        </label>
                        @php $currDur = request('duration', 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_duration" id="profileDurAll" value="all" {{ ($currDur === 'all' || blank($currDur)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profileDurAll">All Durations</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_duration" id="profileDur10" value="lt10" {{ ($currDur === 'lt10' || $currDur === 'under_10' || $currDur === '< 10 Minutes') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profileDur10">&lt; 10 Minutes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_duration" id="profileDur30" value="10-30" {{ ($currDur === '10-30' || $currDur === '10_30' || $currDur === '10 - 30 Minutes') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profileDur30">10 - 30 Minutes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_duration" id="profileDur60" value="30-60" {{ ($currDur === '30-60' || $currDur === '30_60' || $currDur === '30 - 60 Minutes') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profileDur60">30 - 60 Minutes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_modal_duration" id="profileDurAbove60" value="gt60" {{ ($currDur === 'gt60' || $currDur === 'above_60' || $currDur === '> 1 Hour') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="profileDurAbove60">&gt; 1 Hour</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Standardized Modal Footer -->
                <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-end align-items-center gap-2 border-top">
                    <!-- Reset Button -->
                    <button type="button" id="btn-profile-modal-reset" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1 rounded-3" data-bs-dismiss="modal">
                        <i class="ti ti-rotate me-1"></i>
                        <span>Reset</span>
                    </button>

                    <!-- Apply Filter Button -->
                    <button type="button" id="btn-profile-modal-apply" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1 rounded-3" style="background-color: #0c5b9e; border-color: #0c5b9e;" data-bs-dismiss="modal">
                        <i class="ti ti-check me-1"></i>
                        <span>Apply Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
