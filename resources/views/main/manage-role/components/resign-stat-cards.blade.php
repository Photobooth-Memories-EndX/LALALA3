{{-- Resigned Employee Stat Cards --}}
<div class="row g-3 mb-3">

    <!-- Card 1: Total Resigned Employees -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border border-light-subtle shadow-sm p-3 rounded-3 bg-white stat-card-item h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                     style="width: 44px; height: 44px; background-color: #d63939; color: #ffffff;">
                    <i class="ti ti-user-x fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Resigned Employees</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalResigned) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Companies -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border border-light-subtle shadow-sm p-3 rounded-3 bg-white stat-card-item h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                     style="width: 44px; height: 44px; background-color: #003b6d; color: #ffffff;">
                    <i class="ti ti-building-skyscraper fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Companies</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalCompanies) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Divisions -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border border-light-subtle shadow-sm p-3 rounded-3 bg-white stat-card-item h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                     style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                    <i class="ti ti-building-community fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Divisions</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalDivisions) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Departments -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border border-light-subtle shadow-sm p-3 rounded-3 bg-white stat-card-item h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                     style="width: 44px; height: 44px; background-color: #38b000; color: #ffffff;">
                    <i class="ti ti-building fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Departments</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalDepartments) }}</div>
                </div>
            </div>
        </div>
    </div>

</div>
