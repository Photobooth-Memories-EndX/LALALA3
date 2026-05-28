<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card">
    <div class="card-body p-4">
        <form action="{{ route('user.learning.dashboard') }}" method="GET">

            <div class="row g-3 align-items-center mb-3">
                <div class="col-lg-4">
                    <h3 class="card-title mb-1">Learning On Going</h3>
                    <div class="text-muted small">
                        Daftar kelas yang sedang Anda ikuti.
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small text-muted mb-1" for="ongoing_search">Search</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text"
                                       name="ongoing_search"
                                       id="ongoing_search"
                                       value="{{ request('ongoing_search') }}"
                                       class="form-control rounded-3"
                                       placeholder="Cari kelas atau klasifikasi">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1" for="ongoing_status">Learning Program</label>
                            <select name="ongoing_status" id="ongoing_status" class="form-select rounded-3">
                                <option value="all" {{ request('ongoing_status', 'all') == 'all' ? 'selected' : '' }}>All Learning</option>
                                <option value="mandatory" {{ request('ongoing_status') == 'mandatory' ? 'selected' : '' }}>Mandatory Learning</option>
                                <option value="optional" {{ request('ongoing_status') == 'optional' ? 'selected' : '' }}>Optional Learning</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1" for="ongoing_year">Tahun</label>
                            <select name="ongoing_year" id="ongoing_year" class="form-select rounded-3">
                                <option value="all" {{ request('ongoing_year', 'all') == 'all' ? 'selected' : '' }}>All Years</option>
                                @foreach (range(date('Y'), 2020) as $yearOption)
                                    <option value="{{ $yearOption }}" {{ request('ongoing_year') == $yearOption ? 'selected' : '' }}>
                                        {{ $yearOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1" for="ongoing_activity_sort">Activity Sort</label>
                    <select name="ongoing_activity_sort" id="ongoing_activity_sort" class="form-select rounded-3">
                        <option value="newest_activity" {{ request('ongoing_activity_sort', 'newest_activity') == 'newest_activity' ? 'selected' : '' }}>Newest Activity</option>
                        <option value="oldest_activity" {{ request('ongoing_activity_sort') == 'oldest_activity' ? 'selected' : '' }}>Oldest Activity</option>
                        <option value="recently_accessed" {{ request('ongoing_activity_sort') == 'recently_accessed' ? 'selected' : '' }}>Recently Accessed</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1" for="ongoing_progress_sort">Completion Status / Progress</label>
                    <select name="ongoing_progress_sort" id="ongoing_progress_sort" class="form-select rounded-3">
                        <option value="all" {{ request('ongoing_progress_sort', 'all') == 'all' ? 'selected' : '' }}>All Progress</option>
                        <option value="progress_highest" {{ request('ongoing_progress_sort') == 'progress_highest' ? 'selected' : '' }}>Progress Tertinggi</option>
                        <option value="progress_lowest" {{ request('ongoing_progress_sort') == 'progress_lowest' ? 'selected' : '' }}>Progress Terendah</option>
                        <option value="not_started" {{ request('ongoing_progress_sort') == 'not_started' ? 'selected' : '' }}>Not Started 0%</option>
                        <option value="in_progress" {{ request('ongoing_progress_sort') == 'in_progress' ? 'selected' : '' }}>In Progress 1% - 99%</option>
                        <option value="almost_done" {{ request('ongoing_progress_sort') == 'almost_done' ? 'selected' : '' }}>Almost Done 80% - 99%</option>
                        <option value="completed" {{ request('ongoing_progress_sort') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button type="submit" class="btn btn-primary rounded-3 flex-fill flex-md-grow-0">
                            <i class="ti ti-filter me-1"></i>
                            Filter
                        </button>

                        <a href="{{ route('user.learning.dashboard') }}"
                           class="btn btn-outline-secondary rounded-3 flex-fill flex-md-grow-0">
                            <i class="ti ti-refresh me-1"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>