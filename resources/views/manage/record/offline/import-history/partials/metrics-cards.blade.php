{{-- Evaluation & Persistence Metrics (Simplified Exactly 6 Cards) --}}
@php
    $metricCounts = $metrics ?? [
        'valid'           => (int) ($batch->valid_rows ?? 0),
        'warning'         => (int) ($batch->warning_rows ?? 0),
        'duplicate'       => (int) ($batch->duplicate_rows ?? 0),
        'invalid'         => (int) ($batch->invalid_rows ?? 0),
        'invalid_company' => method_exists($batch, 'rows') ? (int) $batch->rows()->where('validation_status', \App\Models\ImportBatchRow::STATUS_INVALID_COMPANY)->count() : 0,
        'all_total'       => (int) ($batch->total_rows ?? 0),
    ];
@endphp

<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="fw-bold text-dark text-uppercase small mb-0 d-flex align-items-center gap-2">
            <i class="ti ti-chart-bar text-primary fs-3"></i>
            <span>Evaluation & Persistence Metrics</span>
        </h5>
    </div>

    <div class="row g-2 metrics-grid-6">
        <!-- 1. VALID -->
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="metric-status-card metric-status-valid shadow-sm">
                <div class="metric-val text-success">{{ number_format($metricCounts['valid']) }}</div>
                <div class="metric-lbl">VALID</div>
            </div>
        </div>

        <!-- 2. WARNING -->
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="metric-status-card metric-status-warning shadow-sm">
                <div class="metric-val text-warning">{{ number_format($metricCounts['warning']) }}</div>
                <div class="metric-lbl">WARNING</div>
            </div>
        </div>

        <!-- 3. DUPLICATE -->
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="metric-status-card metric-status-duplicate shadow-sm">
                <div class="metric-val text-info">{{ number_format($metricCounts['duplicate']) }}</div>
                <div class="metric-lbl">DUPLICATE</div>
            </div>
        </div>

        <!-- 4. INVALID -->
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="metric-status-card metric-status-invalid shadow-sm">
                <div class="metric-val text-danger">{{ number_format($metricCounts['invalid']) }}</div>
                <div class="metric-lbl">INVALID</div>
            </div>
        </div>

        <!-- 5. INVALID COMPANY -->
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="metric-status-card metric-status-invalid-company shadow-sm">
                <div class="metric-val text-orange" style="color: #ea580c !important;">{{ number_format($metricCounts['invalid_company']) }}</div>
                <div class="metric-lbl">INVALID COMPANY</div>
            </div>
        </div>

        <!-- 6. ALL TOTAL (Prominent) -->
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="metric-status-card metric-status-all-total shadow-sm">
                <div class="metric-val text-primary fw-bolder">{{ number_format($metricCounts['all_total']) }}</div>
                <div class="metric-lbl fw-bold text-primary">ALL TOTAL</div>
            </div>
        </div>
    </div>
</div>
