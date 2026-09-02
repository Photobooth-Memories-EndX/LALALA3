<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3 p-md-4">
        <form id="filter-form" onsubmit="return false;">
            <!-- Row 1: 5 fields on Desktop (3 + 2 + 2 + 2 + 3 = 12 cols) -->
            <div class="row g-3 mb-3">
                <!-- Playlist / Training Plan -->
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Playlist / Training Plan</label>
                    <select class="form-select" id="filter-playlist" name="trainingplan_id">
                        <option value="all" selected>All Playlists</option>
                        @foreach($trainingPlans as $tp)
                            <option value="{{ $tp->id }}">{{ $tp->title }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Calculation Source -->
                <div class="col-6 col-md-6 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Calculation Source</label>
                    <select class="form-select" id="filter-calculation-source" name="calculation_source">
                        <option value="all" selected>All Sources</option>
                        <option value="actual">Actual Tracking Only</option>
                        <option value="historical">Historical Fallback Only</option>
                        <option value="zero">Zero / Unfinished Only</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Status</label>
                    <select class="form-select" id="filter-finish-status" name="finish_status">
                        <option value="all" selected>All Status</option>
                        <option value="completed">Completed Only</option>
                        <option value="in_progress">In Progress Only</option>
                    </select>
                </div>

                <!-- Content Type -->
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Content Type</label>
                    <select class="form-select" id="filter-type" name="content_type">
                        <option value="all" selected>All Types</option>
                        <option value="Video">Video</option>
                        <option value="Document">Document</option>
                        <option value="YouTube">YouTube</option>
                    </select>
                </div>

                <!-- Classification -->
                <div class="col-6 col-md-4 col-xl-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Classification</label>
                    <select class="form-select" id="filter-classification" name="classification_slug">
                        <option value="all" selected>All Classifications</option>
                        @foreach($classifications as $c)
                            <option value="{{ $c->slug }}">{{ $c->classification_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Row 2: Completion Year | Min Hours | Max Hours | Action Buttons -->
            <div class="row g-3 align-items-end">
                <!-- Completion Year -->
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Completion Year</label>
                    <select class="form-select" id="filter-year" name="year">
                        <option value="all" selected>All Years</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Min Actual Hours -->
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Min Hours</label>
                    <input type="number" step="0.0001" min="0" class="form-control" id="filter-min-hour" name="min_hour" placeholder="e.g. 0.25">
                </div>

                <!-- Max Actual Hours -->
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Max Hours</label>
                    <input type="number" step="0.0001" min="0" class="form-control" id="filter-max-hour" name="max_hour" placeholder="e.g. 1.50">
                </div>

                <!-- Buttons (Right aligned on desktop, baseline aligned with inputs) -->
                <div class="col-12 col-md-12 col-xl-6 d-flex align-items-end justify-content-xl-end gap-2 mt-3 mt-xl-0">
                    <button type="button" id="btn-apply-filters" class="btn btn-primary px-3">
                        <i class="ti ti-adjustments-horizontal me-1"></i> Apply Filter
                    </button>
                    <button type="button" id="btn-reset-filters" class="btn btn-outline-secondary px-3">
                        <i class="ti ti-filter-x me-1"></i> Reset
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
