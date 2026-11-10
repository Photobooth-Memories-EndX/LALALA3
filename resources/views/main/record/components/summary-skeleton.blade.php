<div class="row g-3 mb-4 summary-skeleton-row" aria-busy="true" aria-label="Loading summary metrics">
    @for ($i = 0; $i < 4; $i++)
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 placeholder placeholder-wave flex-shrink-0 me-3" 
                         style="width: 48px; height: 48px; border-radius: 10px; opacity: 0.15; background-color: currentColor;"></div>
                    <div class="flex-grow-1 placeholder-glow min-w-0">
                        <span class="placeholder col-7 placeholder-sm d-block mb-2" style="height: 12px; border-radius: 4px;"></span>
                        <span class="placeholder col-5 placeholder-lg d-block" style="height: 24px; border-radius: 4px;"></span>
                    </div>
                </div>
            </div>
        </div>
    @endfor
</div>
