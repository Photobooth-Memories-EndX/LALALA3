{{-- Stat Cards --}}
<div class="row g-3 mb-3">
    <!-- Card 1: Total Content -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                    <i class="ti ti-books fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Content</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalContent ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Active -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #38b000; color: #ffffff;">
                    <i class="ti ti-check fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Active</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($activeContent ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Inactive -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #e63946; color: #ffffff;">
                    <i class="ti ti-x fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Inactive</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($inactiveContent ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Playlist Only -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                    <i class="ti ti-playlist fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Playlist Content</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($playlistOnly ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
