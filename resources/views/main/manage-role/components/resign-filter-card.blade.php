{{-- Quick Filter Card for Resign Management --}}
<div class="card border border-light-subtle shadow-sm rounded-4 mb-4 role-filter-card">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold filter-label-head" style="font-size: 0.85rem;">
                <i class="ti ti-filter me-1 text-primary"></i> Quick Filter
            </div>
            <div class="text-muted small d-none d-md-block">
                <span>Filter data karyawan soft-deleted secara instan</span>
            </div>
        </div>

        <form id="resign-filter-form" onsubmit="return false;" class="m-0 p-0">
            <div class="d-flex align-items-center gap-2 flex-wrap flex-lg-nowrap w-100">

                <!-- 1. Entries Select -->
                <div style="width: 120px; flex-shrink: 0;">
                    <select class="form-select rounded-3" id="filter-entries" aria-label="Entries per page">
                        @foreach ([10, 15, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ $opt === 15 ? 'selected' : '' }}>{{ $opt }} Entries</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Company Select -->
                <div style="min-width: 160px; flex: 1 1 160px;">
                    <select class="form-select rounded-3" id="filter-company" aria-label="Filter Company">
                        <option value="">All Companies</option>
                        @foreach ($companies as $comp)
                            <option value="{{ $comp }}">{{ $comp }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Division Select -->
                <div style="min-width: 170px; flex: 1 1 170px;">
                    <select class="form-select rounded-3" id="filter-division" aria-label="Filter Division">
                        <option value="">All Division</option>
                        @foreach ($divisions as $div)
                            <option value="{{ $div }}">{{ $div }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Live Search Input Box -->
                <div class="position-relative flex-grow-1" style="min-width: 220px;">
                    <input type="text" id="resign-search-input"
                           class="form-control rounded-3"
                           placeholder="Search by name, Employee ID (NIP), SAP Code..."
                           autocomplete="off">
                </div>

                <!-- 5. Action Buttons (More Filters, Reset) -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-auto">
                    <!-- More Filters Modal Trigger Button -->
                    <button type="button" class="btn btn-outline-primary px-3 position-relative"
                            style="color: #003b6d; border-color: #003b6d;"
                            data-bs-toggle="modal"
                            data-bs-target="#moreResignFiltersModal"
                            title="More Filters (Department & Resign Date)">
                        <i class="ti ti-adjustments-horizontal fs-2"></i>
                        <span id="more-filters-badge" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="display: none;">
                            <span class="visually-hidden">Active filter</span>
                        </span>
                    </button>

                    <!-- Reset Button -->
                    <button type="button" id="btn-reset-all-filters" class="btn btn-outline-primary px-3"
                            style="color: #003b6d; border-color: #003b6d;"
                            title="Reset All Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- More Resign Filters Modal (Department & Resign Date) -->
<div class="modal fade" id="moreResignFiltersModal" tabindex="-1" aria-labelledby="moreResignFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="moreResignFiltersModalLabel">
                    <i class="ti ti-adjustments-horizontal text-primary"></i> Filter Lanjutan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-4">
                <!-- Department Filter -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark" for="modal-filter-department">Department</label>
                    <select class="form-select rounded-3" id="modal-filter-department">
                        <option value="">Semua Department</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Resign Date Range -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark mb-1">Rentang Tanggal Resign (Soft-Delete)</label>
                    <div class="text-muted small mb-2">Filter berdasarkan tanggal kapan akun karyawan mengalami soft-delete.</div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1" for="modal-filter-date-from">Dari Tanggal</label>
                            <input type="date" class="form-control rounded-3" id="modal-filter-date-from">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1" for="modal-filter-date-to">Sampai Tanggal</label>
                            <input type="date" class="form-control rounded-3" id="modal-filter-date-to">
                        </div>
                    </div>
                </div>

                <!-- Quick Date Presets -->
                <div class="mb-2">
                    <label class="form-label small text-muted mb-1">Pilihan Cepat Rentang Waktu:</label>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-date-preset" data-preset="all">Semua Waktu</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-date-preset" data-preset="30days">30 Hari Terakhir</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-date-preset" data-preset="thisyear">Tahun Ini</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-date-preset" data-preset="lastyear">Tahun Lalu</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top bg-light rounded-bottom-4 d-flex justify-content-between">
                <button type="button" id="btn-reset-modal-filters" class="btn btn-outline-secondary px-3">
                    <i class="ti ti-rotate-clockwise me-1"></i> Reset Filter Ini
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="btn-apply-modal-filters" class="btn btn-primary px-4"
                            style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i> Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
