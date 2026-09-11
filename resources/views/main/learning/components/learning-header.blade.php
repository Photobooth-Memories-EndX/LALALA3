@if (($variant ?? 'table') === 'dashboard')
    <section class="manage-learning-hero mb-4" aria-labelledby="manage-learning-hero-title">
        <div class="manage-learning-hero__content">
            <div class="manage-learning-hero__badge">
                <i class="ti ti-school" aria-hidden="true"></i>
                <span>Manage Learning</span>
            </div>

            <h1 id="manage-learning-hero-title" class="manage-learning-hero__title">
                Learning dashboard with fast summary and server-side detail table.
            </h1>

            <div class="manage-learning-hero__actions">
                <a href="{{ route('learning.manage.all') }}"
                   class="btn manage-learning-hero__action manage-learning-hero__action--primary">
                    <i class="ti ti-table" aria-hidden="true"></i>
                    <span>Open Learning Table</span>
                </a>
            </div>
        </div>

        <aside class="manage-learning-hero__completion" aria-label="Learning completion summary">
            <div class="manage-learning-hero__completion-label">Completion Rate</div>
            <div class="manage-learning-hero__completion-value">
                {{ number_format($completionRate, 1) }}%
            </div>
            <p class="manage-learning-hero__completion-summary">
                {{ number_format($totalLearning) }} total records with
                {{ number_format($finish) }} finished and
                {{ number_format($ongoing) }} ongoing.
            </p>
        </aside>
    </section>
@else
    <!-- TOMBOL BACK RATA KIRI ATAS -->
    <div class="mb-3">
        <a href="{{ route('manage.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
            <i class="ti ti-arrow-left"></i>
            <span>Back</span>
        </a>
    </div>

    <div class="card border-0 shadow-sm p-4 mb-3 rounded-3 bg-white manage-learning-header">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 manage-learning-icon-box"
                     style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d; border-radius: 12px;">
                    <i class="ti ti-school" style="font-size: 2rem !important;"></i>
                </div>
                <div>
                    <h2 class="h3 fw-bold mb-1 text-dark">{{ $title ?? 'Manage Learning' }}</h2>
                    <p class="text-muted small mb-0">Monitor learning progress, status, and employee training completion in OWL LMS.</p>
                </div>
            </div>
            {{-- <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <a href="{{ route('learning.manage.index') }}" class="btn btn-outline-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                    <i class="ti ti-layout-dashboard fs-2"></i> Dashboard
                </a>
            </div> --}}
        </div>
    </div>
@endif

@once
    @push('styles')
        <style>
            [data-bs-theme="dark"] .manage-learning-header,
            body.dark-mode .manage-learning-header,
            body.theme-dark .manage-learning-header {
                background-color: #1e293b !important;
                color: #f8fafc !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
            }

            [data-bs-theme="dark"] .manage-learning-header .text-dark,
            body.dark-mode .manage-learning-header .text-dark,
            body.theme-dark .manage-learning-header .text-dark {
                color: #f8fafc !important;
            }

            [data-bs-theme="dark"] .manage-learning-icon-box,
            body.dark-mode .manage-learning-icon-box,
            body.theme-dark .manage-learning-icon-box {
                background-color: rgba(0, 59, 109, 0.35) !important;
                color: #38bdf8 !important;
            }

            @media (max-width: 768px) {
                .manage-learning-header {
                    padding: 1.25rem !important;
                }
                .manage-learning-icon-box {
                    width: 56px !important;
                    height: 56px !important;
                }
                .manage-learning-icon-box i {
                    font-size: 1.6rem !important;
                }
            }
            .manage-learning-hero {
                display: grid;
                grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr);
                align-items: center;
                gap: clamp(24px, 4vw, 48px);
                position: relative;
                isolation: isolate;
                min-height: 218px;
                overflow: hidden;
                padding: clamp(28px, 3vw, 38px);
                border: 1px solid rgba(255, 255, 255, .18);
                border-radius: 22px;
                background:
                    radial-gradient(circle at 86% 16%, rgba(255, 255, 255, .18) 0%, transparent 28%),
                    linear-gradient(
                        135deg,
                        #001b44 0%,
                        #002b66 22%,
                        #004b99 50%,
                        #2a7bd1 76%,
                        #6aaed6 100%
                    );
                color: #fff;
                box-shadow: 0 16px 36px rgba(0, 27, 68, .18);
            }

            .manage-learning-hero__content {
                min-width: 0;
            }

            .manage-learning-hero__badge {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 12px;
                padding: 6px 11px;
                border: 1px solid rgba(255, 255, 255, .22);
                border-radius: 999px;
                background: rgba(255, 255, 255, .14);
                color: #fff;
                font-size: 11px;
                font-weight: 700;
                line-height: 1;
                letter-spacing: .06em;
                text-transform: uppercase;
            }

            .manage-learning-hero__title {
                max-width: 720px;
                margin: 0 0 22px;
                color: #fff;
                font-size: clamp(1.55rem, 2.5vw, 2.15rem);
                font-weight: 700;
                line-height: 1.18;
                letter-spacing: -.025em;
                text-wrap: balance;
                text-shadow: 0 6px 20px rgba(0, 27, 68, .18);
            }

            .manage-learning-hero__actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 10px;
            }

            .manage-learning-hero__action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                min-height: 42px;
                padding: 9px 16px;
                border-radius: 11px;
                font-size: 14px;
                font-weight: 700;
                line-height: 1.2;
                transition: background-color .2s ease, border-color .2s ease,
                    color .2s ease, transform .2s ease, box-shadow .2s ease;
            }

            .manage-learning-hero__action--primary {
                border-color: #fff;
                background: #fff;
                color: #002b66;
                box-shadow: 0 8px 20px rgba(0, 27, 68, .18);
            }

            .manage-learning-hero__action--primary:hover,
            .manage-learning-hero__action--primary:focus {
                border-color: #eef6ff;
                background: #eef6ff;
                color: #001b44;
                transform: translateY(-1px);
                box-shadow: 0 10px 24px rgba(0, 27, 68, .24);
            }

            .manage-learning-hero__action--secondary {
                border-color: rgba(255, 255, 255, .62);
                background: rgba(0, 27, 68, .12);
                color: #fff;
            }

            .manage-learning-hero__action--secondary:hover,
            .manage-learning-hero__action--secondary:focus {
                border-color: #fff;
                background: rgba(255, 255, 255, .14);
                color: #fff;
                transform: translateY(-1px);
            }

            .manage-learning-hero__action:focus-visible {
                outline: 3px solid rgba(255, 255, 255, .45);
                outline-offset: 3px;
            }

            .manage-learning-hero__completion {
                min-width: 0;
                padding: clamp(20px, 2.4vw, 26px);
                border: 1px solid rgba(255, 255, 255, .2);
                border-radius: 16px;
                background: rgba(0, 27, 68, .42);
                color: #fff;
                box-shadow: 0 14px 30px rgba(0, 27, 68, .18);
                -webkit-backdrop-filter: blur(6px);
                backdrop-filter: blur(6px);
            }

            .manage-learning-hero__completion-label {
                margin-bottom: 5px;
                color: rgba(255, 255, 255, .78);
                font-size: 11px;
                font-weight: 700;
                line-height: 1.3;
                letter-spacing: .09em;
                text-transform: uppercase;
            }

            .manage-learning-hero__completion-value {
                margin-bottom: 9px;
                color: #fff;
                font-size: clamp(2.35rem, 4.4vw, 3.25rem);
                font-weight: 800;
                line-height: 1;
                letter-spacing: -.04em;
                text-shadow: 0 8px 20px rgba(0, 27, 68, .22);
            }

            .manage-learning-hero__completion-summary {
                max-width: 330px;
                margin: 0;
                color: rgba(255, 255, 255, .86);
                font-size: 13px;
                line-height: 1.5;
            }

            [data-bs-theme="dark"] .manage-learning-hero,
            body.dark-mode .manage-learning-hero,
            body.theme-dark .manage-learning-hero {
                border-color: rgba(255, 255, 255, .12);
                background:
                    radial-gradient(circle at 86% 16%, rgba(130, 184, 245, .15) 0%, transparent 28%),
                    linear-gradient(
                        135deg,
                        #00112c 0%,
                        #001b44 24%,
                        #003366 52%,
                        #1d5996 78%,
                        #4e82be 100%
                    );
                box-shadow: 0 18px 42px rgba(0, 0, 0, .32);
            }

            [data-bs-theme="dark"] .manage-learning-hero__completion,
            body.dark-mode .manage-learning-hero__completion,
            body.theme-dark .manage-learning-hero__completion {
                border-color: rgba(255, 255, 255, .16);
                background: rgba(0, 17, 44, .5);
                box-shadow: 0 14px 30px rgba(0, 0, 0, .22);
            }

            @media (max-width: 991.98px) {
                .manage-learning-hero {
                    grid-template-columns: minmax(0, 1fr);
                    gap: 24px;
                    min-height: 0;
                    padding: 28px;
                }

                .manage-learning-hero__completion {
                    width: 100%;
                }
            }

            @media (max-width: 767.98px) {
                .manage-learning-hero {
                    padding: 22px;
                    border-radius: 18px;
                }

                .manage-learning-hero__title {
                    margin-bottom: 18px;
                    font-size: clamp(1.45rem, 7vw, 1.8rem);
                }

                .manage-learning-hero__completion {
                    padding: 20px;
                }
            }

            @media (max-width: 575.98px) {
                .manage-learning-hero__actions {
                    align-items: stretch;
                    flex-direction: column;
                }

                .manage-learning-hero__action {
                    width: 100%;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .manage-learning-hero__action {
                    transition: none;
                }
            }
        </style>
    @endpush
@endonce
