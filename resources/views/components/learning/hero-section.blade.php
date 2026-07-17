@props([
    'user' => null,
    'learningHours' => 0,
    'totalLearning' => 0,
    'completedLearning' => 0,
    'ongoingLearning' => 0,
    'passedAssessment' => 0,
])

@php
    $user = $user ?? auth()->user();

    $ongoingCourseVal = (int) ($ongoingLearning ?? 0);
    $completedCourseVal = (int) ($completedLearning ?? 0);
    $totalCourseVal = (int) ($totalLearning ?? ($ongoingCourseVal + $completedCourseVal));
    $learningHours = $learningHours ?? 0;

    $totalDonut = max($totalCourseVal, 1);
    $completedPercent = round(($completedCourseVal / $totalDonut) * 100, 2);

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
    $heroImage = filled($user?->hero_background)
        ? asset('storage/' . $user->hero_background)
        : $defaultHeroImage;
@endphp

<div class="home-modern-hero compact-home-hero learning-home-hero mb-4"
     data-hero-background
     data-background-url="{{ $heroImage }}"
     style="--hero-bg-image: url('{{ $heroImage }}');">
    <div class="compact-hero-bg-preview" aria-hidden="true"></div>

    <div class="compact-hero-toolbar">
        <form action="{{ route('home.user.hero-background.update') }}"
              method="POST"
              enctype="multipart/form-data"
              class="compact-hero-upload-form js-hero-background-form"
              id="learningHeroCoverForm">
            @csrf
            <input type="hidden" name="source" value="learning">
            <label class="compact-hero-upload js-hero-background-trigger"
                   for="learningHeroCoverInput"
                   title="Change Background"
                   aria-label="Change Background"
                   data-hero-tooltip>
                <i class="ti ti-camera" aria-hidden="true"></i>
                <span class="sr-only visually-hidden">Change Background</span>
            </label>
            <input type="file"
                   name="hero_background"
                   id="learningHeroCoverInput"
                   class="d-none js-hero-background-input"
                   accept="image/png,image/jpeg,image/jpg,image/webp">
            <button type="submit"
                    class="compact-hero-save d-none js-hero-background-save"
                    title="Saving..."
                    aria-label="Saving..."
                    data-hero-tooltip>
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                <span class="sr-only visually-hidden">Saving...</span>
            </button>
        </form>
    </div>

    <div class="row align-items-center g-3 compact-hero-content">
        <div class="col-lg-7">
            <div class="compact-hero-profile">
                <div class="compact-hero-user-info">
                    <span class="home-modern-hero-label compact-hero-label">
                        <i class="ti ti-school"></i>
                        My Learning
                    </span>
                    <h1 class="home-modern-hero-greeting compact-hero-greeting">Learning Dashboard</h1>
                    <h2 class="home-modern-hero-name compact-hero-name">{{ $user->name ?? 'Learner' }}</h2>

                    <p class="home-modern-hero-subtitle compact-hero-subtitle">
                        Track your ongoing learning, review records, assessments, and certificates in one place.
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

@include('components.shared.hero-crop-modal')

<style>
    .learning-home-hero {
        isolation: isolate;
        min-height: 238px;
        padding: 28px 34px;
        border-radius: 22px;
        background-image:
            linear-gradient(90deg, rgba(8, 22, 52, .94) 0%, rgba(15, 52, 110, .88) 52%, rgba(24, 74, 138, .78) 100%),
            var(--hero-bg-image);
        background-size: cover;
        background-position: center;
        box-shadow: 0 18px 44px rgba(15, 23, 42, .16);
        position: relative;
        overflow: hidden;
    }

    .learning-home-hero::before {
        content: "";
        position: absolute;
        width: 300px;
        height: 300px;
        top: -126px;
        right: -96px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .08);
        z-index: -1;
        pointer-events: none;
    }

    .learning-home-hero::after {
        display: none;
    }

    .learning-home-hero .row {
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
            radial-gradient(circle at 82% 20%, rgba(255, 255, 255, .12), transparent 32%),
            linear-gradient(90deg, rgba(8, 22, 52, .9), rgba(18, 58, 118, .74));
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
        border-radius: 999px;
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        line-height: 1;
        background: rgba(255, 255, 255, .14);
        backdrop-filter: blur(8px);
        display: inline-flex;
        align-items: center;
        width: fit-content;
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
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        background: rgba(255, 255, 255, .16);
        backdrop-filter: blur(8px);
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
        border-radius: 50%;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 28px rgba(0, 0, 0, .18), inset 0 0 0 1px rgba(255, 255, 255, .08);
        transition: .25s;
    }

    .compact-donut:hover {
        transform: scale(1.02);
    }

    .home-modern-donut-hit {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        cursor: pointer;
        background: transparent;
        transition: .2s;
        z-index: 1;
    }

    .home-modern-donut-hit-completed:hover {
        background: rgba(34, 197, 94, .12);
    }

    .home-modern-donut-hit-ongoing:hover {
        background: rgba(56, 189, 248, .12);
    }

    .compact-donut-inner {
        width: 86px;
        height: 86px;
        border-radius: 50%;
        background: rgba(12, 37, 83, .92);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        position: relative;
        z-index: 2;
    }

    .compact-donut-inner strong {
        color: #fff;
        font-size: 22px;
        font-weight: 700;
        line-height: 1;
    }

    .compact-donut-inner small {
        width: 66px;
        margin-top: 4px;
        color: rgba(255, 255, 255, .65);
        font-size: 7px;
        font-weight: 600;
        line-height: 1.2;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: .8px;
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
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding-top: 0;
    }

    .compact-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 12px;
        min-height: 22px;
    }

    .tabler-clean-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .compact-summary-row .clean-label {
        color: rgba(255, 255, 255, .85);
        font-size: 12px;
        font-weight: 500;
    }

    .compact-summary-row .clean-value {
        color: #fff;
        font-size: 13px;
        font-weight: 700;
    }

    .home-modern-stat-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex: 0 0 8px;
    }

    .home-modern-stat-dot.total {
        background: #e2e8f0;
        box-shadow: 0 0 6px rgba(255, 255, 255, .4);
    }

    .home-modern-stat-dot.completed {
        background: #22c55e;
        box-shadow: 0 0 8px rgba(34, 197, 94, .5);
    }

    .home-modern-stat-dot.ongoing {
        background: #38bdf8;
        box-shadow: 0 0 8px rgba(56, 189, 248, .5);
    }

    @media (max-width: 991.98px) {
        .learning-home-hero {
            min-height: auto;
            padding: 28px 24px;
            text-align: left;
        }

        .learning-home-hero .row {
            min-height: 0;
        }

        .compact-summary-panel {
            justify-content: flex-start;
            width: max-content;
            max-width: 100%;
        }
    }

    @media (max-width: 767.98px) {
        .learning-home-hero {
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

    [data-bs-theme="dark"] .learning-home-hero,
    body.dark-mode .learning-home-hero {
        background-image:
            linear-gradient(90deg, rgba(3, 10, 24, .96) 0%, rgba(11, 31, 68, .9) 54%, rgba(20, 61, 105, .8) 100%),
            var(--hero-bg-image);
        box-shadow: 0 18px 42px rgba(0, 0, 0, .34);
    }

    [data-bs-theme="dark"] .learning-home-hero .compact-hero-bg-preview::after,
    body.dark-mode .learning-home-hero .compact-hero-bg-preview::after {
        background:
            radial-gradient(circle at 82% 20%, rgba(96, 165, 250, .13), transparent 32%),
            linear-gradient(90deg, rgba(2, 8, 20, .96), rgba(10, 30, 64, .88));
    }

    [data-bs-theme="dark"] .learning-home-hero .compact-summary-panel,
    body.dark-mode .learning-home-hero .compact-summary-panel {
        background: rgba(2, 8, 20, .38);
        border-color: rgba(255, 255, 255, .1);
    }

    [data-bs-theme="dark"] .learning-home-hero .compact-donut-inner,
    body.dark-mode .learning-home-hero .compact-donut-inner {
        background: rgba(5, 18, 39, .94);
    }
</style>
