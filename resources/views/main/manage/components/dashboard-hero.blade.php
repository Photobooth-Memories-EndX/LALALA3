<div class="card mb-4 overflow-hidden border-0 shadow-sm" style="border-radius: 20px !important; border: 1px solid rgba(148, 163, 184, 0.14) !important;">
    <div class="card-body text-white" style="
        height: 136px !important;
        min-height: 136px !important;
        max-height: 136px !important;
        padding: 18px 28px !important;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #09172c 0%, #0d274d 30%, #153e77 65%, #1d5598 100%) !important;
    ">
        {{-- Radial Glow Efek Cahaya Halus --}}
        <div style="
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 85% 20%, rgba(56, 189, 248, 0.15) 0%, transparent 32%);
            pointer-events: none;
            z-index: 1;
        "></div>

        <div class="row align-items-center g-2 w-100" style="position: relative; z-index: 2;">

            {{-- Sisi Kiri: Badge, Judul, Subtitle --}}
            <div class="col-lg-9">

                {{-- Badge Admin --}}
                <div class="d-inline-flex align-items-center gap-1.5 hero-admin-badge mb-1.5 rounded-pill px-2.5 py-0.5">
                    <i class="ti ti-shield-lock hero-admin-badge-icon"></i>
                    <span>ADMIN CONTROL CENTER</span>
                </div>

                {{-- Judul Selamat Datang --}}
                <h1 class="mb-0.5 text-white fw-bold text-truncate" style="font-size: 1.25rem; line-height: 1.25; letter-spacing: -0.2px;">
                    Welcome back, {{ auth()->user()->name ?? 'ESTIHINO HERMAWAN' }}
                </h1>

                {{-- Subtitle --}}
                <p class="mb-0 text-white text-truncate" style="font-size: 12.5px; line-height: 1.35; opacity: 0.85;">
                    Manage all OWL LMS modules from a centralized dashboard.
                </p>

            </div>

            {{-- Sisi Kanan: Watermark Icon Proporsional --}}
            <div class="col-lg-3 d-none d-lg-flex justify-content-end align-items-center">
                <i class="ti ti-layout-dashboard text-white" style="font-size: 68px; opacity: 0.09; line-height: 1;"></i>
            </div>

        </div>
    </div>
</div>
