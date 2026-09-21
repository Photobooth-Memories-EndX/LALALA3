<div class="row g-3 mb-4 record-navigation-row">
    <!-- Card 1: KP OWL -->
    <div class="col-12 col-sm-6 col-lg-3">
        <a href="{{ route('manage.record.online') }}" class="card card-link text-decoration-none h-100 record-nav-card {{ request()->routeIs('manage.record.online*') ? 'record-nav-card--active' : '' }}">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="avatar avatar-sm rounded-3 record-nav-icon" style="background-color: #e0f2fe; color: #0284c7;">
                            <i class="ti ti-device-laptop"></i>
                        </span>
                        @if(request()->routeIs('manage.record.online*'))
                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1" style="font-size: 0.725rem; font-weight: 600;">ACTIVE</span>
                        @endif
                    </div>
                    <h3 class="card-title fw-bold mb-1" style="font-size: 1.05rem;">KP OWL</h3>
                    <div class="record-nav-subtitle fw-semibold small mb-1" style="color: #0c5b9e;">Online Learning Record</div>
                </div>
                <p class="text-muted small mb-0 mt-2" style="font-size: 0.8rem; line-height: 1.35;">Data from OWL Content & Playlist</p>
            </div>
        </a>
    </div>

    <!-- Card 2: Non KP OWL -->
    <div class="col-12 col-sm-6 col-lg-3">
        <a href="{{ route('manage.record.offline') }}" class="card card-link text-decoration-none h-100 record-nav-card {{ request()->routeIs('manage.record.offline*') ? 'record-nav-card--active' : '' }}">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="avatar avatar-sm rounded-3 record-nav-icon" style="background-color: #fef3c7; color: #d97706;">
                            <i class="ti ti-certificate"></i>
                        </span>
                        @if(request()->routeIs('manage.record.offline*'))
                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1" style="font-size: 0.725rem; font-weight: 600;">ACTIVE</span>
                        @endif
                    </div>
                    <h3 class="card-title fw-bold mb-1" style="font-size: 1.05rem;">Non KP OWL</h3>
                    <div class="record-nav-subtitle fw-semibold small mb-1" style="color: #b45309;">Offline Training Record</div>
                </div>
                <p class="text-muted small mb-0 mt-2" style="font-size: 0.8rem; line-height: 1.35;">Manual Training & Excel Import</p>
            </div>
        </a>
    </div>

    <!-- Card 3: All KP OWL -->
    <div class="col-12 col-sm-6 col-lg-3">
        <a href="{{ route('manage.record.all') }}" class="card card-link text-decoration-none h-100 record-nav-card {{ request()->routeIs('manage.record.all*') ? 'record-nav-card--active' : '' }}">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="avatar avatar-sm rounded-3 record-nav-icon" style="background-color: #ede9fe; color: #7c3aed;">
                            <i class="ti ti-stack-2"></i>
                        </span>
                        @if(request()->routeIs('manage.record.all*'))
                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1" style="font-size: 0.725rem; font-weight: 600;">ACTIVE</span>
                        @endif
                    </div>
                    <h3 class="card-title fw-bold mb-1" style="font-size: 1.05rem;">All KP OWL</h3>
                    <div class="record-nav-subtitle fw-semibold small mb-1" style="color: #6d28d9;">Combined Learning Record</div>
                </div>
                <p class="text-muted small mb-0 mt-2" style="font-size: 0.8rem; line-height: 1.35;">Online & Offline Learning Records</p>
            </div>
        </a>
    </div>

    <!-- Card 4: Export KP OWL -->
    <div class="col-12 col-sm-6 col-lg-3">
        <a href="{{ route('manage.record.export-all') }}" class="card card-link text-decoration-none h-100 record-nav-card {{ request()->routeIs('manage.record.export-all*') ? 'record-nav-card--active' : '' }}">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="avatar avatar-sm rounded-3 record-nav-icon" style="background-color: #dcfce7; color: #15803d;">
                            <i class="ti ti-download"></i>
                        </span>
                        @if(request()->routeIs('manage.record.export-all*'))
                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1" style="font-size: 0.725rem; font-weight: 600;">ACTIVE</span>
                        @endif
                    </div>
                    <h3 class="card-title fw-bold mb-1" style="font-size: 1.05rem;">Export KP OWL</h3>
                    <div class="record-nav-subtitle fw-semibold small mb-1" style="color: #15803d;">Learning Record Export</div>
                </div>
                <p class="text-muted small mb-0 mt-2" style="font-size: 0.8rem; line-height: 1.35;">Export Online, Offline or All Records</p>
            </div>
        </a>
    </div>
</div>

@once
    @push('styles')
        <style>
            .record-nav-card {
                border-radius: 14px !important;
                border: 1px solid #e2e8f0 !important;
                background: #ffffff !important;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
            }

            .record-nav-card:hover {
                transform: translateY(-3px);
                border-color: #93c5fd !important;
                box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            }

            .record-nav-card--active {
                border: 2px solid #0c5b9e !important;
                background: #f8fafc !important;
                box-shadow: 0 6px 18px rgba(12, 91, 158, 0.12) !important;
            }

            .record-nav-icon {
                width: 32px !important;
                height: 32px !important;
                min-width: 32px !important;
                border-radius: 8px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
            }

            .record-nav-icon i {
                font-size: 16px !important;
                line-height: 1 !important;
            }

            [data-bs-theme="dark"] .record-nav-card,
            body.dark-mode .record-nav-card,
            body.theme-dark .record-nav-card {
                background: #1e293b !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            }

            [data-bs-theme="dark"] .record-nav-card:hover,
            body.dark-mode .record-nav-card:hover,
            body.theme-dark .record-nav-card:hover {
                border-color: #3b82f6 !important;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
            }

            [data-bs-theme="dark"] .record-nav-card--active,
            body.dark-mode .record-nav-card--active,
            body.theme-dark .record-nav-card--active {
                border: 2px solid #38bdf8 !important;
                background: rgba(14, 165, 233, 0.08) !important;
                box-shadow: 0 6px 18px rgba(56, 189, 248, 0.18) !important;
            }

            [data-bs-theme="dark"] .record-nav-card .card-title,
            body.dark-mode .record-nav-card .card-title,
            body.theme-dark .record-nav-card .card-title {
                color: #f8fafc !important;
            }

            [data-bs-theme="dark"] .record-nav-card .record-nav-subtitle,
            body.dark-mode .record-nav-card .record-nav-subtitle,
            body.theme-dark .record-nav-card .record-nav-subtitle {
                color: #93c5fd !important;
            }

            [data-bs-theme="dark"] .record-nav-card .record-nav-icon,
            body.dark-mode .record-nav-card .record-nav-icon,
            body.theme-dark .record-nav-card .record-nav-icon {
                filter: brightness(0.9);
            }
        </style>
    @endpush
@endonce
