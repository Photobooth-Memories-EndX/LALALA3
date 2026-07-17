@php
    $user = auth()->user();

    $ongoingCourseVal = $ongoingCourse ?? 84;
    $completedCourseVal = $completedCourse ?? 58;

    // Total Course adalah penjumlahan dari Completed + On Going
    $totalCourseVal = $ongoingCourseVal + $completedCourseVal;

    // Nilai di dalam lingkaran jam belajar (Learning Hours)
    $learningHours = $learningHours ?? 20.8;

    $totalLearning = max($totalCourseVal, 1);
    $completedPercent = round(($completedCourseVal / $totalLearning) * 100, 2);

    $donutBlend = 1.2;
    $donutBlendStart = max($completedPercent - $donutBlend, 0);
    $donutBlendEnd = min($completedPercent + $donutBlend, 100);

    $donutSlicePolygon = function ($fromPct, $toPct) {
        $points = ['50% 50%'];
        $fromDeg = $fromPct * 3.6;
        $toDeg = $toPct * 3.6;
        $range = $toDeg - $fromDeg;
        $steps = max(2, (int) ceil(abs($range) / 12));

        for ($i = 0; $i <= $steps; $i++) {
            $deg = $fromDeg + ($range * $i / $steps);
            $rad = deg2rad($deg);

            $x = 50 + 50 * sin($rad);
            $y = 50 - 50 * cos($rad);

            $points[] = round($x, 2) . '%' . ' ' . round($y, 2) . '%';
        }

        return 'polygon(' . implode(', ', $points) . ')';
    };

    $completedSlicePath = $donutSlicePolygon(0, $completedPercent);
    $ongoingSlicePath = $donutSlicePolygon($completedPercent, 100);

    $defaultHeroImage = asset('img/owl-dashboard.png');
    $coverFiles = glob(public_path('storage/profile/cover_' . $user->id . '.*')) ?: [];
    $coverFile = count($coverFiles) ? basename($coverFiles[0]) : null;
    $heroImage = $coverFile ? asset('storage/profile/' . $coverFile) : $defaultHeroImage;
@endphp

<div class="home-modern-hero compact-home-hero" style="--hero-bg-image: url('{{ $heroImage }}');">
    <div class="compact-hero-bg-preview" aria-hidden="true"></div>

    <div class="compact-hero-toolbar">
        <form action="{{ route('user.updateprofile') }}"
              method="POST"
              enctype="multipart/form-data"
              class="compact-hero-upload-form"
              id="homeHeroCoverForm">
            @csrf
            <input type="hidden" name="source" value="home">
            <label class="compact-hero-upload" for="homeHeroCoverInput">
                <i class="ti ti-photo-up"></i>
                <span>Change cover</span>
            </label>
            <input type="file"
                   name="cover"
                   id="homeHeroCoverInput"
                   class="d-none"
                   accept="image/png,image/jpeg,image/jpg,image/webp">
        </form>
    </div>

    <div class="row align-items-center g-3 compact-hero-content">
        <div class="col-lg-7">
            <div class="compact-hero-profile">
                <!-- BAGIAN INFORMASI TEKS & AKSI (SEJAJAR LURUS SEPENUHNYA) -->
                <div class="compact-hero-user-info">
                    <span class="home-modern-hero-label compact-hero-label">
                        <i class="ti ti-school"></i>
                        Learning Management System
                    </span>
                    <h1 class="home-modern-hero-greeting compact-hero-greeting">Welcome back,</h1>
                    <h2 class="home-modern-hero-name compact-hero-name">{{ $user->name ?? 'ESTIHONO HERMAWAN' }}</h2>

                    <p class="home-modern-hero-subtitle compact-hero-subtitle">
                        Continuing journey as <strong>{{ $user->team ?? 'Training Team' }}</strong>
                    </p>

                    <div class="home-modern-hero-actions">
                        <span class="home-modern-hero-stat-pill compact-hero-pill">
                            <i class="ti ti-flame me-1"></i>
                            <strong>Keep Learning</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mt-3 mt-lg-0">
            <div class="compact-summary-panel">
                <div class="home-modern-donut-outer compact-donut"
                     style="background: conic-gradient(#22c55e 0%, #22c55e {{ $donutBlendStart }}%, #38bdf8 {{ $donutBlendEnd }}%, #38bdf8 100%);">

                    <div class="home-modern-donut-hit home-modern-donut-hit-completed"
                         style="clip-path:{{ $completedSlicePath }}"
                         data-bs-toggle="tooltip"
                         data-bs-placement="top"
                         title="Completed {{ $completedCourseVal }} Learning">
                    </div>

                    <div class="home-modern-donut-hit home-modern-donut-hit-ongoing"
                         style="clip-path:{{ $ongoingSlicePath }}"
                         data-bs-toggle="tooltip"
                         data-bs-placement="top"
                         title="On Going {{ $ongoingCourseVal }} Learning">
                    </div>

                    <div class="home-modern-donut-inner compact-donut-inner">
                        <strong>{{ $learningHours }}</strong>
                        <small>Learning Hours</small>
                    </div>
                </div>

                <div class="compact-summary-detail">
                    <div class="home-modern-summary-title compact-summary-title">
                        Your Learning Summary
                    </div>

                    <div class="tabler-clean-list-container compact-summary-list">
                        <div class="tabler-clean-row compact-summary-row">
                            <div class="tabler-clean-meta">
                                <span class="home-modern-stat-dot total"></span>
                                <span class="clean-label">Total Course</span>
                            </div>
                            <div class="clean-value">{{ $totalCourseVal }}</div>
                        </div>

                        <div class="tabler-clean-row compact-summary-row">
                            <div class="tabler-clean-meta">
                                <span class="home-modern-stat-dot completed"></span>
                                <span class="clean-label">Completed</span>
                            </div>
                            <div class="clean-value">{{ $completedCourseVal }}</div>
                        </div>

                        <div class="tabler-clean-row compact-summary-row">
                            <div class="tabler-clean-meta">
                                <span class="home-modern-stat-dot ongoing"></span>
                                <span class="clean-label">On Going</span>
                            </div>
                            <div class="clean-value">{{ $ongoingCourseVal }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .compact-home-hero {
        isolation: isolate;
        min-height: 238px;
        padding: 28px 34px;
        border-radius: 22px;
        background-image:
            linear-gradient(90deg, rgba(7, 23, 57, .9) 0%, rgba(18, 72, 167, .82) 48%, rgba(42, 114, 202, .66) 100%),
            var(--hero-bg-image);
        background-size: cover;
        background-position: center;
        box-shadow: 0 18px 44px rgba(15, 23, 42, .16);
    }

    .compact-home-hero::before {
        width: 300px;
        height: 300px;
        top: -126px;
        right: -96px;
        background: rgba(255, 255, 255, .08);
        z-index: -1;
    }

    .compact-home-hero::after {
        display: none;
    }

    .compact-home-hero .row {
        min-height: 182px;
    }

    .compact-hero-bg-preview {
        position: absolute;
        inset: 0;
        z-index: -2;
        background-image: var(--hero-bg-image);
        background-size: cover;
        background-position: center;
        transform: scale(1.02);
    }

    .compact-hero-bg-preview::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 82% 20%, rgba(255, 255, 255, .16), transparent 32%),
            linear-gradient(90deg, rgba(6, 22, 52, .9), rgba(20, 72, 156, .76));
    }

    .compact-hero-toolbar {
        position: absolute;
        top: 14px;
        right: 16px;
        z-index: 3;
    }

    .compact-hero-upload {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        padding: 7px 11px;
        border: 1px solid rgba(255, 255, 255, .2);
        border-radius: 999px;
        background: rgba(15, 23, 42, .34);
        color: rgba(255, 255, 255, .9);
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        backdrop-filter: blur(10px);
        transition: background-color .2s, border-color .2s, transform .2s;
    }

    .compact-hero-upload:hover {
        color: #fff;
        background: rgba(37, 99, 235, .55);
        border-color: rgba(255, 255, 255, .38);
        transform: translateY(-1px);
    }

    .compact-hero-upload-form {
        margin: 0;
    }

    .compact-hero-content {
        position: relative;
        z-index: 2;
    }

    .compact-hero-profile {
        display: flex;
        align-items: flex-start;
        gap: 0;
        min-width: 0;
        width: 100%;
    }

    .compact-hero-user-info {
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
    }

    .compact-hero-label {
        gap: 6px;
        padding: 6px 11px;
        margin-bottom: 10px;
        font-size: 11px;
        background: rgba(255, 255, 255, .14);
        backdrop-filter: blur(8px);
        display: inline-flex;
    }

    .compact-hero-greeting {
        margin: 0 0 3px;
        font-size: 15px;
        color: rgba(255, 255, 255, .85);
    }

    .compact-hero-name {
        margin: 0;
        font-size: clamp(26px, 3.2vw, 38px);
        line-height: 1.04;
        letter-spacing: 0;
        color: #ffffff;
        overflow-wrap: anywhere;
    }

    .compact-hero-subtitle {
        max-width: 560px;
        margin: 12px 0 14px;
        font-size: 14px;
        line-height: 1.55;
        color: rgba(255, 255, 255, .9);
    }

    .compact-hero-pill {
        gap: 4px;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 13px;
        background: rgba(255, 255, 255, .16);
        backdrop-filter: blur(8px);
        display: inline-flex;
    }

    .compact-summary-panel {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: max-content;
        max-width: 100%;
        min-height: 0;
        padding: 10px 12px;
        margin: 0 auto;
        border: 1px solid rgba(255, 255, 255, .14);
        border-radius: 18px;
        background: rgba(15, 23, 42, .2);
        backdrop-filter: blur(12px);
    }

    .compact-donut {
        width: 118px;
        height: 118px;
        flex: 0 0 118px;
        box-shadow: 0 14px 28px rgba(0, 0, 0, .18), inset 0 0 0 1px rgba(255, 255, 255, .08);
    }

    .compact-donut-inner {
        width: 86px;
        height: 86px;
        background: rgba(12, 37, 83, .92);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08);
    }

    .compact-donut-inner strong {
        font-size: 22px;
    }

    .compact-donut-inner small {
        width: 66px;
        margin-top: 4px;
        font-size: 7px;
        line-height: 1.2;
        text-align: center;
    }

    .compact-summary-detail {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 150px;
        max-width: 190px;
        align-self: stretch;
    }

    .compact-summary-title {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        margin: 0 0 6px;
        font-size: 13px;
        line-height: 1.25;
        letter-spacing: 0;
    }

    .compact-summary-list {
        gap: 6px;
        padding-top: 0;
    }

    .compact-summary-row {
        gap: 12px;
        min-height: 22px;
    }

    .compact-summary-row .clean-label {
        font-size: 12px;
    }

    .compact-summary-row .clean-value {
        font-size: 13px;
    }

    @media (max-width: 991.98px) {
        .compact-home-hero {
            min-height: auto;
            padding: 28px 24px;
            text-align: left;
        }

        .compact-home-hero .row {
            min-height: 0;
        }

        .compact-summary-panel {
            justify-content: flex-start;
            width: max-content;
            max-width: 100%;
        }
    }

    @media (max-width: 767.98px) {
        .compact-home-hero {
            padding: 54px 18px 20px;
            border-radius: 18px;
        }

        .compact-hero-toolbar {
            top: 12px;
            right: 12px;
        }

        .compact-hero-upload span {
            display: none;
        }

        .compact-hero-upload {
            width: 34px;
            height: 34px;
            justify-content: center;
            padding: 0;
        }

        .compact-hero-profile {
            gap: 0;
        }

        .compact-hero-name {
            font-size: 24px;
            overflow-wrap: anywhere;
        }

        .compact-hero-subtitle {
            font-size: 13px;
            margin: 12px 0 14px;
        }

        .compact-summary-panel {
            flex-direction: column;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px;
        }

        .compact-donut {
            width: 112px;
            height: 112px;
            flex-basis: 112px;
        }

        .compact-donut-inner {
            width: 82px;
            height: 82px;
        }

        .compact-summary-detail {
            width: 100%;
            max-width: 260px;
            min-width: 0;
        }

        .compact-summary-title {
            min-height: auto;
        }
    }

    [data-bs-theme="dark"] .compact-home-hero {
        background-image:
            linear-gradient(90deg, rgba(3, 10, 24, .94) 0%, rgba(11, 31, 68, .86) 54%, rgba(20, 74, 122, .7) 100%),
            var(--hero-bg-image);
        box-shadow: 0 18px 42px rgba(0, 0, 0, .34);
    }

    [data-bs-theme="dark"] .compact-hero-bg-preview::after {
        background:
            radial-gradient(circle at 82% 20%, rgba(96, 165, 250, .13), transparent 32%),
            linear-gradient(90deg, rgba(2, 8, 20, .94), rgba(10, 30, 64, .84));
    }

    [data-bs-theme="dark"] .compact-summary-panel {
        background: rgba(2, 8, 20, .38);
        border-color: rgba(255, 255, 255, .1);
    }

    [data-bs-theme="dark"] .compact-donut-inner {
        background: rgba(5, 18, 39, .94);
    }
</style>

<script>
    $(function () {
        $('#homeHeroCoverInput').on('change', function () {
            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            const previewUrl = URL.createObjectURL(file);
            $('.compact-home-hero').css('--hero-bg-image', `url("${previewUrl}")`);
            $('#homeHeroCoverForm').trigger('submit');
        });
    });
</script>
