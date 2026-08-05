@php
    $user = $user ?? auth()->user();
    $ongoingCourseVal = (int) ($ongoingCourseVal ?? 0);
    $completedCourseVal = (int) ($completedCourseVal ?? 0);
    $totalCourseVal = (int) ($totalCourseVal ?? ($ongoingCourseVal + $completedCourseVal));
    $learningHours = $learningHours ?? 0;

    $totalDonut = max($totalCourseVal, 1);
    $completedPercent = min(100, max(0, round(($completedCourseVal / $totalDonut) * 100, 2)));
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
            $points[] = round($x, 2) . '% ' . round($y, 2) . '%';
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

@include('components.shared.hero-style')

<div class="shared-learning-hero {{ !empty($compactSummary) ? 'shared-learning-hero--compact-summary' : '' }} mb-4"
     data-hero-background
     data-hero-width="1600"
     data-hero-height="600"
     data-background-url="{{ $heroImage }}"
     style="--hero-bg-image: url('{{ $heroImage }}');">
    <div class="shared-hero-toolbar">
        <form action="{{ route('home.user.hero-background.update') }}"
              method="POST"
              enctype="multipart/form-data"
              class="shared-hero-upload-form js-hero-background-form"
              id="heroBackgroundForm">
            @csrf
            <label class="shared-hero-upload js-hero-background-trigger"
                   for="heroBackgroundInput"
                   title="Change Background"
                   aria-label="Change Background"
                   data-hero-tooltip>
                <i class="ti ti-camera" aria-hidden="true"></i>
                <span class="visually-hidden">Change Background</span>
            </label>
            <input type="file"
                   name="hero_background"
                   id="heroBackgroundInput"
                   class="d-none js-hero-background-input"
                   accept="image/png,image/jpeg,image/jpg,image/webp">
            <button type="submit"
                    class="shared-hero-save d-none js-hero-background-save"
                    title="Saving..."
                    aria-label="Saving..."
                    data-hero-tooltip>
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                <span class="visually-hidden">Saving...</span>
            </button>
        </form>
    </div>

    <div class="row align-items-center g-3 shared-hero-content">
        <div class="col-lg-7">
            <div class="shared-hero-profile">
                <span class="shared-hero-label">
                    <i class="ti ti-school" aria-hidden="true"></i>
                    {{ $heroLabel }}
                </span>
                <h1 class="shared-hero-title">{{ $heroTitle }}</h1>
                <h2 class="shared-hero-name">{{ $heroName }}</h2>
                <p class="shared-hero-subtitle">{{ $heroSubtitle }}</p>

                <div class="shared-hero-actions">
                    <span class="shared-hero-pill">
                        <i class="ti ti-flame me-1" aria-hidden="true"></i>
                        <strong>Keep Learning</strong>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mt-3 mt-lg-0">
            <div class="shared-hero-summary">
                <div class="shared-hero-donut"
                     style="background: conic-gradient(rgb(var(--kp-hero-completed-rgb)) 0%, rgb(var(--kp-hero-completed-rgb)) {{ $donutBlendStart }}%, #00a8ff {{ $donutBlendEnd }}%, #00a8ff 100%);">
                    <div class="shared-hero-donut-hit shared-hero-donut-hit-completed"
                         role="link"
                         tabindex="0"
                         onclick="window.location.href='{{ route('user.learning.dashboard', ['learning_tab' => 'record']) }}'"
                         onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }"
                         style="clip-path: {{ $completedSlicePath }}"
                         data-bs-toggle="tooltip"
                         data-bs-placement="top"
                         title="Completed {{ $completedCourseVal }} Learning"></div>
                    <div class="shared-hero-donut-hit shared-hero-donut-hit-ongoing"
                         role="link"
                         tabindex="0"
                         onclick="window.location.href='{{ route('user.learning.dashboard') }}'"
                         onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }"
                         style="clip-path: {{ $ongoingSlicePath }}"
                         data-bs-toggle="tooltip"
                         data-bs-placement="top"
                         title="On Going {{ $ongoingCourseVal }} Learning"></div>
                    <div class="shared-hero-donut-inner">
                        <strong>{{ $learningHours }}</strong>
                        <small>Total Learning Hours</small>
                    </div>
                </div>

                <div class="shared-hero-summary-detail">
                    <div class="shared-hero-summary-title">Your Learning Summary</div>
                    <div class="shared-hero-summary-list">
                        <div class="shared-hero-summary-row">
                            <div class="shared-hero-summary-meta">
                                <span class="shared-hero-stat-dot total"></span>
                                <span class="shared-hero-summary-label">Total Course</span>
                            </div>
                            <div class="shared-hero-summary-value">{{ $totalCourseVal }}</div>
                        </div>
                        <div class="shared-hero-summary-row">
                            <div class="shared-hero-summary-meta">
                                <span class="shared-hero-stat-dot completed"></span>
                                <a href="{{ route('user.learning.dashboard', ['learning_tab' => 'record']) }}"
                                   class="shared-hero-summary-label">Completed</a>
                            </div>
                            <div class="shared-hero-summary-value">{{ $completedCourseVal }}</div>
                        </div>
                        <div class="shared-hero-summary-row">
                            <div class="shared-hero-summary-meta">
                                <span class="shared-hero-stat-dot ongoing"></span>
                                <a href="{{ route('user.learning.dashboard') }}"
                                   class="shared-hero-summary-label">On Going</a>
                            </div>
                            <div class="shared-hero-summary-value">{{ $ongoingCourseVal }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('components.shared.hero-crop-modal')
