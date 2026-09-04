<div class="card mb-4 overflow-hidden border-0 shadow-sm" style="border-radius: 22px !important;">
    <div class="card-body text-white" style="
        height: 170px !important;
        min-height: 170px !important;
        max-height: 170px !important;
        padding: 24px 34px !important;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #001b44 0%, #002b66 20%, #004b99 45%, #2a7bd1 70%, #82b8f5 88%, #dbeeff 100%) !important;
    ">
        {{-- Radial Glow Efek Cahaya --}}
        <div style="
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 85% 20%, rgba(255, 255, 255, 0.22) 0%, transparent 28%);
            pointer-events: none;
            z-index: 1;
        "></div>

        <div class="row align-items-center g-3 w-100" style="position: relative; z-index: 2;">

            {{-- Sisi Kiri: Badge, Judul, Subtitle, & Tombol --}}
            <div class="col-lg-8">

                {{-- Badge Admin --}}
                <div class="d-inline-flex align-items-center gap-1 bg-white text-primary mb-2 rounded-pill px-2 py-1" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                    <i class="ti ti-shield-lock text-primary"></i>
                    ADMIN CONTROL CENTER
                </div>

                {{-- Judul Selamat Datang --}}
                <h1 class="mb-1 text-white fw-bold text-truncate" style="font-size: 1.35rem; line-height: 1.25; letter-spacing: -0.2px;">
                    Welcome back, {{ auth()->user()->name ?? 'ESTIHONO HERMAWAN' }}
                </h1>

                {{-- Subtitle --}}
                <p class="mb-3 text-white text-truncate" style="font-size: 13px; line-height: 1.35; opacity: 0.85;">
                    Manage all OWL LMS modules from a centralized dashboard.
                </p>

                {{-- Tombol Aksi
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ url('manage/content/create') }}"
                       class="btn btn-sm bg-white text-primary fw-bold rounded-pill px-3 py-2 border-0 shadow-sm" style="font-size: 12px; color: #002b66 !important;">
                        <i class="ti ti-plus me-1"></i> Add Content
                    </a>

                    <a href="{{ url('manage/user') }}"
                       class="btn btn-sm text-white fw-bold rounded-pill px-3 py-2" style="font-size: 12px; background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.35); backdrop-filter: blur(4px);">
                        <i class="ti ti-users me-1"></i> Manage Users
                    </a>
                </div> --}}

            </div>

            {{-- Sisi Kanan: Watermark Icon --}}
            <div class="col-lg-4 d-none d-lg-flex justify-content-end align-items-center">
                <i class="ti ti-layout-dashboard text-white" style="font-size: 100px; opacity: 0.15; line-height: 1;"></i>
            </div>

        </div>
    </div>
</div>
