<!-- More Filters Modal for Profile Learning History -->
<div class="modal fade" id="profileMoreFiltersModal" tabindex="-1" aria-labelledby="profileMoreFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="profile-more-filters-modal-form" onsubmit="return false;">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="profileMoreFiltersModalLabel">
                        <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <!-- Date Range (from_date & to_date) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Date Range (Learning Date)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">From Date</label>
                                <input type="text" id="profile-modal-from-date" name="from_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('from_date') ?? request('date_from') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">To Date</label>
                                <input type="text" id="profile-modal-to-date" name="to_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('to_date') ?? request('date_to') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Progress Range (progress_range) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block">Progress Range</label>
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

                    <!-- Duration (duration) -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark small d-block">Duration</label>
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
                <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                    <!-- Reset Button -->
                    <button type="button" id="btn-profile-modal-reset" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1" data-bs-dismiss="modal">
                        <i class="ti ti-rotate me-1"></i>
                        <span>Reset</span>
                    </button>

                    <!-- Apply Filter Button -->
                    <button type="button" id="btn-profile-modal-apply" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" style="background-color: #0c5b9e; border-color: #0c5b9e;" data-bs-dismiss="modal">
                        <i class="ti ti-check me-1"></i>
                        <span>Apply Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
