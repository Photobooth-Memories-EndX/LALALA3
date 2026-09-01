<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form id="filter-form" class="row g-3 align-items-end" onsubmit="return false;">
            <!-- Playlist / Training Plan -->
            <div class="col-md-4 col-lg-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Playlist / Training Plan</label>
                <select class="form-select" id="filter-playlist" name="trainingplan_id">
                    <option value="all" selected>All Playlists</option>
                    @foreach($trainingPlans as $tp)
                        <option value="{{ $tp->id }}">{{ $tp->title }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Calculation Source -->
            <div class="col-md-4 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Calculation Source</label>
                <select class="form-select" id="filter-calculation-source" name="calculation_source">
                    <option value="all" selected>All Sources</option>
                    <option value="actual">Actual Tracking Only</option>
                    <option value="historical">Historical Fallback Only</option>
                    <option value="zero">Zero / Unfinished Only</option>
                </select>
            </div>

            <!-- Finish / Completion Status -->
            <div class="col-md-4 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Status</label>
                <select class="form-select" id="filter-finish-status" name="finish_status">
                    <option value="all" selected>All Status</option>
                    <option value="completed">Completed Only</option>
                    <option value="in_progress">In Progress Only</option>
                </select>
            </div>

            <!-- Content Type -->
            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Content Type</label>
                <select class="form-select" id="filter-type" name="content_type">
                    <option value="all" selected>All Types</option>
                    <option value="Video">Video</option>
                    <option value="Document">Document</option>
                    <option value="YouTube">YouTube</option>
                </select>
            </div>

            <!-- Classification -->
            <div class="col-md-3 col-lg-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Classification</label>
                <select class="form-select" id="filter-classification" name="classification_slug">
                    <option value="all" selected>All Classifications</option>
                    @foreach($classifications as $c)
                        <option value="{{ $c->slug }}">{{ $c->classification_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Completion Year -->
            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Completion Year</label>
                <select class="form-select" id="filter-year" name="year">
                    <option value="all" selected>All Years</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Min Actual Hours -->
            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Min Hours</label>
                <input type="number" step="0.0001" min="0" class="form-control" id="filter-min-hour" name="min_hour" placeholder="e.g. 0.25">
            </div>

            <!-- Max Actual Hours -->
            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Max Hours</label>
                <input type="number" step="0.0001" min="0" class="form-control" id="filter-max-hour" name="max_hour" placeholder="e.g. 1.50">
            </div>

            <!-- Buttons -->
            <div class="col-md-6 col-lg-3 d-flex flex-wrap gap-2 justify-content-md-end ms-auto">
                <button type="button" id="btn-apply-filters" class="btn btn-primary">
                    <i class="ti ti-adjustments-horizontal me-1"></i> Apply Filter
                </button>
                <button type="button" id="btn-reset-filters" class="btn btn-outline-secondary">
                    <i class="ti ti-filter-x me-1"></i> Reset
                </button>
            </div>
        </form>
    </div>
</div>
