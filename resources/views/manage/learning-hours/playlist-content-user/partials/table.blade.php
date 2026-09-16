<!-- Table Legend & Learning Hour Information -->
<div class="card border-0 shadow-sm mb-4 info-guide-card">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3">
            <div class="avatar avatar-md bg-blue-lt text-blue rounded flex-shrink-0">
                <i class="ti ti-info-circle fs-2"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-2 mb-3">
                    <h4 class="card-title mb-0 fw-bold info-guide-title">Learning Hour Information & Reference Guide</h4>
                    <span class="badge info-guide-badge border px-2 py-1 text-wrap text-start">
                        <i class="ti ti-alert-circle me-1"></i> Master Learning Hour is a reference value. Actual Learning Hour represents the calculated learning time for the learner.
                    </span>
                </div>
                <div class="row g-3 small info-guide-grid">
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-2 info-guide-item rounded h-100">
                            <strong class="info-guide-item-title d-block mb-1"><i class="ti ti-player-play text-blue me-1"></i> Current Time:</strong>
                            <span class="info-guide-item-desc">Actual tracked time recorded for this learner (stored in seconds).</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-2 info-guide-item rounded h-100">
                            <strong class="info-guide-item-title d-block mb-1"><i class="ti ti-clock-check text-teal me-1"></i> Actual Learning Hour:</strong>
                            <span class="info-guide-item-desc">Calculated learning hours based on tracked learning time. Historical completed records may use content duration as fallback.</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-2 info-guide-item rounded h-100">
                            <strong class="info-guide-item-title d-block mb-1"><i class="ti ti-bookmark text-primary me-1"></i> Master Learning Hour:</strong>
                            <span class="info-guide-item-desc">Reference learning-hour value of the content. This is <strong>NOT</strong> the learner's actual learning hour.</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-2 info-guide-item rounded h-100">
                            <strong class="info-guide-item-title d-block mb-1"><i class="ti ti-clock text-azure me-1"></i> Duration:</strong>
                            <span class="info-guide-item-desc">Official duration of the content.</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-2 info-guide-item rounded h-100">
                            <strong class="info-guide-item-title d-block mb-1"><i class="ti ti-chart-pie text-indigo me-1"></i> Progress:</strong>
                            <span class="info-guide-item-desc">Completion percentage of the content (100% does not equal Master Learning Hour).</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-2 info-guide-item rounded h-100">
                            <strong class="info-guide-item-title d-block mb-1"><i class="ti ti-device-analytics text-green me-1"></i> Calculation Source:</strong>
                            <span class="info-guide-item-desc">Indicates whether Actual Learning Hour was determined via Actual Tracking, Historical Fallback, or Zero / Unfinished.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTable Card Container -->
<div class="card border-0 shadow-sm">
    <div class="card-header border-0 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="card-title mb-1">Playlist Content User Learning Hours</h3>
            <div class="text-muted small">Server-side DataTables with pure numeric sorting and Master vs Actual comparison for 244K+ records.</div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2 flex-shrink-0">
            <button type="button" id="btn-bulk-calculate" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Manually recalculates Learning Hours for Playlist Content User records and stores the result in learning_hour. Processing is performed in batches.">
                <i class="ti ti-calculator me-1"></i> Bulk Calculate Learning Hours
            </button>
            <button type="button" id="btn-refresh-table" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-refresh me-1"></i> Refresh
            </button>
        </div>
    </div>

    <div class="card-body p-0 mt-3">
        <div class="table-responsive" style="min-width: 0; width: 100%;">
            <table id="playlist-content-user-lh-table" class="table table-vcenter table-striped card-table w-100">
                <thead>
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="min-width: 170px;">User</th>
                        <th style="min-width: 190px;">Playlist</th>
                        <th style="min-width: 220px;">Content Title</th>
                        <th style="min-width: 90px;" class="text-center">Type</th>
                        <th style="min-width: 120px;">
                            Progress
                            <span class="cursor-help ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Completion percentage of the content.">
                                <i class="ti ti-info-circle text-muted"></i>
                            </span>
                        </th>
                        <th style="min-width: 120px;">
                            Current Time
                            <span class="cursor-help ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Actual tracked learning time stored in seconds.">
                                <i class="ti ti-info-circle text-muted"></i>
                            </span>
                        </th>
                        <th style="min-width: 90px;" class="text-center">
                            Duration
                            <span class="cursor-help ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Official duration of the content.">
                                <i class="ti ti-info-circle text-muted"></i>
                            </span>
                        </th>
                        <th style="min-width: 140px;" class="text-nowrap text-center">
                            Actual Learning Hour
                            <span class="cursor-help ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Learning hours calculated from actual tracked time. Historical completed records may use content duration as fallback.">
                                <i class="ti ti-info-circle text-muted"></i>
                            </span>
                        </th>
                        <th style="min-width: 140px;" class="text-nowrap text-center">
                            Master Learning Hour
                            <span class="cursor-help ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Reference/master learning-hour value defined for the content. It is not the learner's actual learning time.">
                                <i class="ti ti-info-circle text-muted"></i>
                            </span>
                        </th>
                        <th style="min-width: 140px;" class="text-center">
                            Calculation Source
                            <span class="cursor-help ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Shows whether the learning hour comes from actual tracking, historical fallback, or zero/unfinished state.">
                                <i class="ti ti-info-circle text-muted"></i>
                            </span>
                        </th>
                        <th style="min-width: 110px;" class="text-center">
                            Status
                            <span class="cursor-help ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Current completion status of the playlist content record.">
                                <i class="ti ti-info-circle text-muted"></i>
                            </span>
                        </th>
                        <th style="min-width: 130px;" class="text-nowrap">Completed At</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bulk Calculate Modal -->
<div class="modal fade" id="modal-bulk-calculate" tabindex="-1" aria-labelledby="modalBulkCalculateLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- State 1: Confirmation Form -->
            <div id="calc-confirm-state">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalBulkCalculateLabel">
                        <i class="ti ti-calculator text-primary me-1"></i> Calculate Playlist Content User Learning Hours?
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Process playlist content user records in batches of 500 and update the <code>learning_hour</code> column. Existing tracking data (<code>current_time</code>, <code>progress</code>, <code>finish</code>) will <strong>NOT</strong> be modified.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Calculation Scope</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="calc_mode" id="calc-mode-all" value="all" checked>
                            <label class="form-check-label" for="calc-mode-all">
                                <strong>All Playlist Records (244K+)</strong>
                                <div class="text-muted small">Processes all eligible Playlist Content User records across the platform.</div>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="calc_mode" id="calc-mode-filtered" value="filtered">
                            <label class="form-check-label" for="calc-mode-filtered">
                                <strong>Current Filtered Records</strong>
                                <div class="text-muted small">Only processes records matching the active filters on the page.</div>
                            </label>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="calc-dry-run">
                            <label class="form-check-label text-azure fw-semibold" for="calc-dry-run">
                                Dry Run (Preview only)
                            </label>
                            <div class="text-muted small">Calculates learning hours and returns summary statistics without updating the database.</div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="calc-force">
                            <label class="form-check-label" for="calc-force">
                                Force Recalculate
                            </label>
                            <div class="text-muted small">Recalculates records even if their learning_hour is already populated and matches.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="btn-start-calculate" class="btn btn-primary px-4">
                        <i class="ti ti-check me-1"></i> Calculate Learning Hours
                    </button>
                </div>
            </div>

            <!-- State 2: In-Progress Loading -->
            <div id="calc-loading-state" class="d-none text-center p-5">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                <h4 class="fw-bold mb-2">Calculating Learning Hours...</h4>
                <p class="text-muted small mb-0">
                    Processing records safely in batches with concurrency protection.<br>
                    Please wait while the calculation completes.
                </p>
            </div>

            <!-- State 3: Result Summary -->
            <div id="calc-result-state" class="d-none">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-success" id="calc-result-title">
                        <i class="ti ti-circle-check me-1"></i> Bulk Calculation Completed
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="calc-result-alert" class="alert alert-success border-0 bg-success-lt mb-3 py-2"></div>

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted py-1">Execution Mode:</td>
                                    <td class="fw-bold text-end py-1" id="res-mode">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Dry Run:</td>
                                    <td class="fw-bold text-end py-1" id="res-dry-run">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Total Scanned:</td>
                                    <td class="fw-bold text-end font-monospace py-1" id="res-scanned">0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Records Updated:</td>
                                    <td class="fw-bold text-end font-monospace text-success py-1" id="res-updated">0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Skipped (Already Accurate):</td>
                                    <td class="fw-bold text-end font-monospace text-muted py-1" id="res-skipped">0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Concurrency Skipped (Heartbeat Active):</td>
                                    <td class="fw-bold text-end font-monospace text-warning py-1" id="res-concurrency-skipped">0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Historical Fallback Applied:</td>
                                    <td class="fw-bold text-end font-monospace text-azure py-1" id="res-fallback">0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Batches Processed:</td>
                                    <td class="fw-bold text-end font-monospace py-1" id="res-batches">0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Failed Records:</td>
                                    <td class="fw-bold text-end font-monospace text-danger py-1" id="res-failed">0</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Duration:</td>
                                    <td class="fw-bold text-end font-monospace py-1" id="res-duration">0s</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
