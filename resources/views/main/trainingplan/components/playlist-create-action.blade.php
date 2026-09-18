<div class="card owl-main-card mb-4">
    <div class="card-header d-flex align-items-center">
        <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">5</span>
        <h3 class="card-title mb-0">Deadline &amp; Actions</h3>
    </div>
    <div class="card-body">
        {{-- Deadline Configuration --}}
        <div class="mb-3">
            <h4 class="form-label font-weight-bold mb-1">Playlist Deadline Settings</h4>
            <p class="text-muted small mb-2">Define whether this playlist has a deadline range for learners.</p>

            <div class="form-check cursor-pointer mb-2">
                <input type="hidden" name="has_deadline" value="0">
                <input class="form-check-input"
                       type="checkbox"
                       id="has_deadline"
                       name="has_deadline"
                       value="1"
                       @if(old('has_deadline')) checked @endif>
                <label class="form-check-label fw-medium" for="has_deadline">Set Playlist Deadline?</label>
            </div>

            <div id="deadline-fields" style="{{ old('has_deadline') ? '' : 'display:none;' }}">
                <div class="row g-2 mt-1">
                    <div class="col-md-6 col-12">
                        <label for="from_date" class="form-label small fw-semibold">From Date</label>
                        <input type="date"
                               class="form-control @error('from_date') is-invalid @enderror"
                               name="from_date"
                               id="from_date"
                               value="{{ old('from_date') }}">
                        @error('from_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-12">
                        <label for="to_date" class="form-label small fw-semibold">To Date</label>
                        <input type="date"
                               class="form-control @error('to_date') is-invalid @enderror"
                               name="to_date"
                               id="to_date"
                               value="{{ old('to_date') }}">
                        @error('to_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Action Buttons --}}
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <a href="{{ route('playlist.index') }}" class="btn btn-light">
                <i class="ti ti-arrow-left me-1"></i> Cancel
            </a>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="submit" form="trainingPlanForm" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" name="action" value="draft" formnovalidate>
                    <i class="ti ti-device-floppy"></i>
                    <span>Save as Draft</span>
                </button>

                <button type="submit" form="trainingPlanForm" class="btn btn-primary d-inline-flex align-items-center gap-1" name="action" value="publish">
                    <i class="ti ti-check"></i>
                    <span>Save &amp; Publish</span>
                </button>
            </div>
        </div>
    </div>
</div>