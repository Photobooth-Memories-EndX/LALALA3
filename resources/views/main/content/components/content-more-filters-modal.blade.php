<!-- More Filters Modal -->
<div class="modal fade" id="moreContentFiltersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('content.index') }}" method="GET">
                <div class="modal-body py-3">
                    <input type="hidden" name="q" value="{{ request('q') ?? (request('s') ?? request('search')) }}">
                    <input type="hidden" name="active" value="{{ request('active') ?? request('status') }}">
                    <input type="hidden" name="type" value="{{ request('type') }}">
                    <input type="hidden" name="classification_id" value="{{ request('classification_id') ?? request('classification') }}">
                    <input type="hidden" name="year" value="{{ request('year') }}">
                    <input type="hidden" name="month" value="{{ request('month') }}">

                    <!-- Author Dropdown -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Author</label>
                        <select name="author" class="form-select rounded-3">
                            <option value="">All Authors</option>
                            @foreach($authors ?? [] as $author)
                                <option value="{{ $author }}" {{ request('author') == $author ? 'selected' : '' }}>{{ $author }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Created Date (dd/mm/yyyy) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Created Date</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">From Date</label>
                                <input type="text" name="created_from_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('created_from_date') ?? request('created_from') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">To Date</label>
                                <input type="text" name="created_to_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('created_to_date') ?? request('created_to') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Period Range (dd/mm/yyyy) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Period</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">From Date</label>
                                <input type="text" name="deadline_from_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('deadline_from_date') ?? (request('period_from') ?? request('date_from')) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">To Date</label>
                                <input type="text" name="deadline_to_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('deadline_to_date') ?? (request('period_to') ?? request('date_to')) }}">
                            </div>
                        </div>
                    </div>

                    <!-- Deadline & Sort by Row -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">Deadline</label>
                            @php $currentDeadline = request('deadline_status') ?? request('deadline'); @endphp
                            <select name="deadline_status" class="form-select rounded-3">
                                <option value="all" {{ ($currentDeadline == 'all' || blank($currentDeadline)) ? 'selected' : '' }}>All</option>
                                <option value="expired" {{ $currentDeadline == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="today" {{ $currentDeadline == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="less_than_7_days" {{ $currentDeadline == 'less_than_7_days' ? 'selected' : '' }}>Less than 7 Days</option>
                                <option value="less_than_30_days" {{ $currentDeadline == 'less_than_30_days' ? 'selected' : '' }}>Less than 30 Days</option>
                                <option value="more_than_30_days" {{ $currentDeadline == 'more_than_30_days' ? 'selected' : '' }}>More than 30 Days</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">Sort By</label>
                            <select name="sort" class="form-select rounded-3">
                                <option value="" {{ blank(request('sort')) ? 'selected' : '' }}>All</option>
                                <option value="newest" {{ (request('sort') == 'newest' || request('sort') == 'newest_course') ? 'selected' : '' }}>Newest</option>
                                <option value="oldest" {{ (request('sort') == 'oldest' || request('sort') == 'oldest_course') ? 'selected' : '' }}>Oldest</option>
                                <option value="asc" {{ (request('sort') == 'asc' || request('sort') == 'title_asc' || request('sort') == 'title') ? 'selected' : '' }}>Title (A-Z)</option>
                                <option value="desc" {{ (request('sort') == 'desc' || request('sort') == 'title_desc') ? 'selected' : '' }}>Title (Z-A)</option>
                                <option value="views" {{ request('sort') == 'views' ? 'selected' : '' }}>Most Views</option>
                                <option value="lh_asc" {{ (request('sort') == 'lh_asc' || request('sort') == 'learning_hours_asc') ? 'selected' : '' }}>Learning Hours (Lowest)</option>
                                <option value="lh_desc" {{ (request('sort') == 'lh_desc' || request('sort') == 'learning_hours_desc') ? 'selected' : '' }}>Learning Hours (Highest)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Sort Views Radio -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block">Sort Views</label>
                        @php $currViewOrder = request('view_order') ?? request('sort_views'); @endphp
                        <div class="d-flex gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="view_order" id="contentViewDefault" value="" {{ blank($currViewOrder) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentViewDefault">Default</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="view_order" id="contentViewMost" value="most_viewed" {{ ($currViewOrder === 'most_viewed' || $currViewOrder === 'most') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentViewMost">Most Viewed</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="view_order" id="contentViewLeast" value="least_viewed" {{ ($currViewOrder === 'least_viewed' || $currViewOrder === 'least') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentViewLeast">Least Viewed</label>
                            </div>
                        </div>
                    </div>

                    <!-- Duration Radio -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block">Duration</label>
                        @php $currDuration = request('duration', 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="duration" id="contentDurAll" value="all" {{ ($currDuration === 'all' || blank($currDuration)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentDurAll">All Durations</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="duration" id="contentDur10" value="under_10" {{ ($currDuration === 'under_10' || $currDuration === 'lt10') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentDur10">&lt; 10 Menit</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="duration" id="contentDur30" value="10_30" {{ ($currDuration === '10_30' || $currDuration === '10-30') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentDur30">10 - 30 Menit</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="duration" id="contentDur60" value="30_60" {{ ($currDuration === '30_60' || $currDuration === '30-60') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentDur60">30 - 60 Menit</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="duration" id="contentDurAbove60" value="above_60" {{ ($currDuration === 'above_60' || $currDuration === 'gt60') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentDurAbove60">&gt; 1 Jam</label>
                            </div>
                        </div>
                    </div>

                    <!-- Learning Hour Filter (Numeric Range & Presets) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block">
                            <i class="ti ti-clock me-1 text-primary"></i> Learning Hour
                        </label>

                        <!-- Numeric Range Min & Max -->
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">Min Learning Hour (h)</label>
                                <input type="number" step="0.0001" min="0" name="min_learning_hour" class="form-control rounded-3" placeholder="e.g. 0.2500" value="{{ request('min_learning_hour') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">Max Learning Hour (h)</label>
                                <input type="number" step="0.0001" min="0" name="max_learning_hour" class="form-control rounded-3" placeholder="e.g. 1.5000" value="{{ request('max_learning_hour') }}">
                            </div>
                        </div>

                        <!-- Preset Radios -->
                        @php $currLH = request('learning_hour', 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="learning_hour" id="contentLhAll" value="all" {{ ($currLH === 'all' || blank($currLH)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentLhAll">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="learning_hour" id="contentLh1" value="lt1h" {{ $currLH === 'lt1h' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentLh1">&lt; 1 Hour</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="learning_hour" id="contentLh3" value="1-3h" {{ $currLH === '1-3h' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentLh3">1 - 3 Hours</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="learning_hour" id="contentLhAbove3" value="gt3h" {{ $currLH === 'gt3h' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentLhAbove3">&gt; 3 Hours</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="learning_hour" id="contentLhMissing" value="missing_lh" {{ $currLH === 'missing_lh' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentLhMissing">Missing LH</label>
                            </div>
                        </div>
                    </div>

                    <!-- Exam Status Radio -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block">Exam Status</label>
                        @php $currExam = request('exam', ''); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exam" id="contentExamAll" value="" {{ blank($currExam) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentExamAll">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exam" id="contentExamOk" value="OK" {{ ($currExam === 'OK' || $currExam === 'Yes' || $currExam === '1') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentExamOk">With Exam (OK)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exam" id="contentExamNot" value="NOT" {{ ($currExam === 'NOT' || $currExam === 'No' || $currExam === '0') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentExamNot">Without Exam (NOT)</label>
                            </div>
                        </div>
                    </div>

                    <!-- Playlist Only Radio -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark small d-block">Playlist Only / Status</label>
                        @php $currPlaylist = request('playlistonly') ?? request('playlist'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="playlistonly" id="contentPlaylistAll" value="" {{ blank($currPlaylist) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentPlaylistAll">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="playlistonly" id="contentPlaylistYes" value="Y" {{ ($currPlaylist === 'Y' || $currPlaylist === 'Yes' || $currPlaylist === '1') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentPlaylistYes">Playlist Only (Yes)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="playlistonly" id="contentPlaylistNo" value="N" {{ ($currPlaylist === 'N' || $currPlaylist === 'No' || $currPlaylist === '0') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="contentPlaylistNo">Non-Playlist (No)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                    <!-- Reset Button (Placed on the left of Apply Filter) -->
                    <a href="{{ route('content.index') }}" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1">
                        <i class="ti ti-rotate me-1"></i>
                        <span>Reset</span>
                    </a>

                    <!-- Apply Filter Button -->
                    <button type="submit" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1">
                        <i class="ti ti-check me-1"></i>
                        <span>Apply Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
