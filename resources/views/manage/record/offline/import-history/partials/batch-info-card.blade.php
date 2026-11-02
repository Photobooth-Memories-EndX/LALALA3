{{-- Batch Information Card (Simplified 6 Key Headers) --}}
<div class="card batch-info-card border-0 mb-4 shadow-sm">
    <div class="card-header border-bottom py-2.5 px-4 bg-transparent d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="ti ti-info-circle text-primary fs-2"></i>
            <h4 class="card-title fw-bold text-dark mb-0">Batch Information</h4>
        </div>
    </div>
    <div class="card-body p-4">
        {{-- Baris Pertama: 4 Kolom di Desktop (Source Type, Batch Status, Imported By, Duration) --}}
        <div class="row g-3 mb-3 pb-3 border-bottom batch-info-row-1">
            <!-- 1. Source Type -->
            <div class="col-6 col-md-3">
                <div class="batch-info-label">Source Type</div>
                <div class="batch-info-value d-flex align-items-center gap-1.5 mt-1">
                    @if($batch->source_type === \App\Models\ImportBatch::SOURCE_PTKP)
                        <span class="badge badge-outline text-primary border-primary fw-bold px-2.5 py-1">PTKP</span>
                    @else
                        <span class="badge badge-outline text-warning border-warning fw-bold px-2.5 py-1" style="color: #f76707 !important; border-color: #f76707 !important;">Non-PTKP</span>
                    @endif
                </div>
            </div>

            <!-- 2. Batch Status -->
            <div class="col-6 col-md-3">
                <div class="batch-info-label">Batch Status</div>
                <div class="batch-info-value d-flex align-items-center gap-1.5 mt-1">
                    @if($batch->status === \App\Models\ImportBatch::STATUS_COMPLETED)
                        <span class="badge bg-success-lt text-success fw-bold px-2.5 py-1"><i class="ti ti-check me-1"></i>Completed</span>
                    @elseif($batch->status === \App\Models\ImportBatch::STATUS_COMPLETED_WITH_WARNINGS)
                        <span class="badge bg-warning-lt text-warning fw-bold px-2.5 py-1"><i class="ti ti-alert-triangle me-1"></i>Completed with Warnings</span>
                    @elseif($batch->status === \App\Models\ImportBatch::STATUS_FAILED)
                        <span class="badge bg-danger-lt text-danger fw-bold px-2.5 py-1"><i class="ti ti-x me-1"></i>Failed</span>
                    @elseif($batch->status === \App\Models\ImportBatch::STATUS_IMPORTING)
                        <span class="badge bg-cyan-lt text-cyan fw-bold px-2.5 py-1"><i class="ti ti-loader me-1 spin"></i>Processing</span>
                    @else
                        <span class="badge bg-secondary-lt text-secondary fw-bold px-2.5 py-1">{{ $batch->status }}</span>
                    @endif
                </div>
            </div>

            <!-- 3. Imported By -->
            <div class="col-6 col-md-3">
                <div class="batch-info-label">Imported By</div>
                <div class="batch-info-value mt-1">
                    <span class="fw-bold text-dark">{{ strtoupper($batch->importedBy?->name ?? 'System') }}</span>
                    @if($batch->importedBy?->id_emp)
                        <span class="text-muted small">({{ $batch->importedBy->id_emp }})</span>
                    @elseif($batch->importedBy?->email)
                        <span class="text-muted small">({{ $batch->importedBy->email }})</span>
                    @endif
                </div>
            </div>

            <!-- 4. Duration -->
            <div class="col-6 col-md-3">
                <div class="batch-info-label">Duration</div>
                <div class="batch-info-value mt-1 d-flex align-items-center gap-1.5 text-dark fw-semibold">
                    <i class="ti ti-clock text-primary fs-3 flex-shrink-0"></i>
                    <span>{{ $duration ?? '-' }}</span>
                </div>
            </div>
        </div>

        {{-- Baris Kedua: 2 Kolom di Desktop (Original Filename, Import Date) --}}
        <div class="row g-3 batch-info-row-2">
            <!-- 5. Original Filename -->
            <div class="col-12 col-md-6">
                <div class="batch-info-label">Original Filename</div>
                <div class="batch-info-value mt-1 d-flex align-items-center gap-2">
                    <i class="ti ti-file-spreadsheet text-success fs-2 flex-shrink-0"></i>
                    @if($batch->hasStoredFile())
                        <a href="{{ route('manage.record.offline.import-history.download', $batch->batch_uuid) }}" class="text-decoration-none text-primary fw-semibold text-truncate d-inline-flex align-items-center gap-1" title="Download original file">
                            <span class="text-truncate">{{ $batch->original_filename }}</span>
                            <i class="ti ti-download fs-3 ms-1 text-muted"></i>
                        </a>
                    @else
                        <span class="text-truncate fw-semibold text-dark" title="{{ $batch->original_filename }}">{{ $batch->original_filename }}</span>
                    @endif
                    @if($batch->file_size)
                        <span class="badge bg-secondary-lt text-muted flex-shrink-0">{{ number_format($batch->file_size / 1024, 1) }} KB</span>
                    @endif
                </div>
            </div>

            <!-- 6. Import Date (Started At – Finished At) -->
            <div class="col-12 col-md-6">
                <div class="batch-info-label">Import Date</div>
                <div class="batch-info-value mt-1 d-flex align-items-center gap-1.5 text-dark fw-semibold" title="Started At: {{ $batch->started_at ? $batch->started_at->format('Y-m-d H:i:s') : '-' }} | Finished At: {{ $batch->finished_at ? $batch->finished_at->format('Y-m-d H:i:s') : '-' }}">
                    <i class="ti ti-calendar-time text-primary fs-3 flex-shrink-0"></i>
                    <span class="text-nowrap">
                        {{ $batch->started_at ? $batch->started_at->format('Y-m-d H:i:s') : '-' }}
                        <span class="text-muted mx-1">–</span>
                        {{ $batch->finished_at ? $batch->finished_at->format('Y-m-d H:i:s') : '-' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Error Summary Alert (if import failed or has rollback error) --}}
        @if($batch->error_summary)
            <div class="alert alert-danger mt-3 mb-0 shadow-sm rounded-3">
                <div class="d-flex align-items-start gap-2">
                    <i class="ti ti-alert-triangle fs-2 text-danger flex-shrink-0 mt-0.5"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Execution Failure / Rollback Details</h6>
                        <p class="small mb-0 font-monospace">{{ $batch->error_summary }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
