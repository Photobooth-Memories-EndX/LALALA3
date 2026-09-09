<div class="card owl-main-card mb-3">
    <div class="card-header d-flex align-items-center">
        <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">3</span>
        <div>
            <h3 class="card-title mb-0">Duration &amp; Learning Hours</h3>
            <p class="text-muted mb-0 small">Total duration and learning hours calculated automatically from assigned materials, or manually adjusted.</p>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Playlist Total Duration --}}
            <div class="col-md-6 col-12">
                <label for="total_duration" class="form-label fw-semibold">Playlist Total Duration (HH:MM:SS)</label>
                <input type="text"
                       class="form-control font-monospace @error('total_duration') is-invalid @enderror"
                       id="total_duration"
                       name="total_duration"
                       value="{{ old('total_duration', '00:00:00') }}"
                       placeholder="00:00:00"
                       pattern="^(\d+):([0-5]\d):([0-5]\d)$"
                       title="Format: HH:MM:SS (e.g. 01:30:00)">
                <small class="form-hint mt-1">
                    Format: <code>HH:MM:SS</code> (e.g. 01:30:00). Enter duration manually or click Synchronize to calculate from selected materials.
                </small>
                @error('total_duration')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Learning Hours & Synchronize Button --}}
            <div class="col-md-6 col-12">
                <label for="learning_hours" class="form-label fw-semibold">Learning Hours</label>
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm-7 col-md-8">
                        <input type="number"
                               step="0.0001"
                               min="0"
                               class="form-control font-monospace @error('learning_hours') is-invalid @enderror"
                               id="learning_hours"
                               name="learning_hours"
                               value="{{ old('learning_hours', '0.0000') }}"
                               placeholder="0.0000">
                    </div>
                    <div class="col-12 col-sm-5 col-md-4">
                        <button type="button"
                                class="btn btn-outline-primary w-100"
                                id="btn-sync-playlist"
                                data-url="{{ route('playlist.synchronize-learning-hours', 'preview') }}">
                            <i class="ti ti-refresh me-1 icon-sync"></i>
                            <span class="btn-text">Synchronize</span>
                        </button>
                    </div>
                </div>
                <small class="form-hint mt-1">
                    Calculated using: <code>round(total seconds / 3600, 4)</code>. Enter manually or click Synchronize.
                </small>
                @error('learning_hours')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>