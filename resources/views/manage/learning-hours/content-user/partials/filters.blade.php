<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form id="filter-form" class="row g-3 align-items-end" onsubmit="return false;">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Calculation Status</label>
                <select class="form-select" id="filter-lh-status" name="learning_hours_status">
                    <option value="all" selected>All Records</option>
                    <option value="uncalculated">Not Calculated Only</option>
                    <option value="calculated">Calculated Only</option>
                </select>
            </div>

            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Enrollment Status</label>
                <select class="form-select" id="filter-status" name="status">
                    <option value="all" selected>All Status</option>
                    <option value="mandatory">Mandatory</option>
                    <option value="optional">Optional</option>
                </select>
            </div>

            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Content Type</label>
                <select class="form-select" id="filter-type" name="content_type">
                    <option value="all" selected>All Types</option>
                    <option value="Video">Video</option>
                    <option value="Document">Document</option>
                    <option value="YouTube">YouTube</option>
                </select>
            </div>

            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Classification</label>
                <select class="form-select" id="filter-classification" name="classification_slug">
                    <option value="all" selected>All Classifications</option>
                    @foreach($classifications as $c)
                        <option value="{{ $c->slug }}">{{ $c->classification_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Completion Year</label>
                <select class="form-select" id="filter-year" name="year">
                    <option value="all" selected>All Years</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 col-lg-2 d-flex flex-wrap gap-2 justify-content-md-end ms-auto">
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
