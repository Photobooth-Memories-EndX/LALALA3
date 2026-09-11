<div class="row g-3 mb-3">
    <!-- Card 1: Total User -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                    <i class="ti ti-users fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total User</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalUser ?? $user->total()) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: PTKP User -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                    <i class="ti ti-id fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">PTKP User</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalPtkp ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Active User -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #38b000; color: #ffffff;">
                    <i class="ti ti-user-check fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Active User</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalActive ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Inactive User -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #e63946; color: #ffffff;">
                    <i class="ti ti-user-x fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Inactive User</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalInactive ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>