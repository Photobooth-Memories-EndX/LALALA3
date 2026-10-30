<div class="row g-3 mb-4" id="import-summary-cards-container">
    <!-- Card 1: Total Batches -->
    <div class="col-6 col-md-3">
        <div class="card import-summary-card shadow-sm h-100 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="summary-icon bg-blue-lt text-primary">
                    <i class="ti ti-stack-2"></i>
                </div>
                <div>
                    <div class="summary-label">Total Batches</div>
                    <div class="summary-number" id="card-total-batches">{{ number_format($summary['total_batches'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Completed Batches -->
    <div class="col-6 col-md-3">
        <div class="card import-summary-card shadow-sm h-100 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="summary-icon bg-green-lt text-success">
                    <i class="ti ti-circle-check"></i>
                </div>
                <div>
                    <div class="summary-label">Import Berhasil</div>
                    <div class="summary-number text-success" id="card-completed-batches">{{ number_format($summary['completed_batches'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Completed with Warnings -->
    <div class="col-6 col-md-3">
        <div class="card import-summary-card shadow-sm h-100 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="summary-icon bg-yellow-lt text-warning">
                    <i class="ti ti-alert-triangle"></i>
                </div>
                <div>
                    <div class="summary-label">Dengan Warning</div>
                    <div class="summary-number text-warning" id="card-warning-batches">{{ number_format($summary['warning_batches'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Failed Batches -->
    <div class="col-6 col-md-3">
        <div class="card import-summary-card shadow-sm h-100 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="summary-icon bg-red-lt text-danger">
                    <i class="ti ti-alert-circle"></i>
                </div>
                <div>
                    <div class="summary-label">Import Gagal</div>
                    <div class="summary-number text-danger" id="card-failed-batches">{{ number_format($summary['failed_batches'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
