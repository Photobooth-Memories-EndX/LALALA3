<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card">
    <div class="card-body p-4">
        <form action="{{ route('user.learning.dashboard') }}" method="GET">
            <div class="row g-3 align-items-end">

                <div class="col-lg-4">
                    <h3 class="card-title mb-1">Learning On Going</h3>
                    <div class="text-muted small">
                        Daftar kelas yang sedang Anda ikuti.
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1" for="ongoing_search">
                        Search
                    </label>

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

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1" for="ongoing_status">
                        Learning Program
                    </label>

                    <select name="ongoing_status"
                            id="ongoing_status"
                            class="form-select rounded-3">

                        <option value="all" {{ request('ongoing_status', 'all') == 'all' ? 'selected' : '' }}>
                            All Learning
                        </option>

                        <option value="mandatory" {{ request('ongoing_status') == 'mandatory' ? 'selected' : '' }}>
                            Mandatory Learning
                        </option>

                        <option value="optional" {{ request('ongoing_status') == 'optional' ? 'selected' : '' }}>
                            Optional Learning
                        </option>

                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small text-muted mb-1" for="ongoing_year">
                        Tahun
                    </label>

                    <select name="ongoing_year"
                            id="ongoing_year"
                            class="form-select rounded-3">

                        <option value="all" {{ request('ongoing_year', 'all') == 'all' ? 'selected' : '' }}>
                            All Years
                        </option>

                        @foreach (range(date('Y'), 2020) as $yearOption)
                            <option value="{{ $yearOption }}" {{ request('ongoing_year') == $yearOption ? 'selected' : '' }}>
                                {{ $yearOption }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button type="submit" class="btn btn-primary rounded-3">
                            <i class="ti ti-filter me-1"></i>
                            Filter
                        </button>

                        <a href="{{ route('user.learning.dashboard') }}" class="btn btn-outline-secondary rounded-3">
                            <i class="ti ti-refresh me-1"></i>
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>