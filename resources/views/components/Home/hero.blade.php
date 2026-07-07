@php
    $ongoingCourseVal = $ongoingCourse ?? 0;
    $completedCourseVal = $completedCourse ?? 0;
    $totalLearningDisplay = $ongoingCourseVal + $completedCourseVal;
    $totalLearning = max($totalLearningDisplay, 1);
    $completedPercent = round(($completedCourseVal / $totalLearning) * 100, 2);

    // Small blended transition zone so the two colors meet smoothly instead of a hard edge.
    $donutBlend = 1.2;
    $donutBlendStart = max($completedPercent - $donutBlend, 0);
    $donutBlendEnd = min($completedPercent + $donutBlend, 100);

    // Pie-slice clip-path for each segment, used purely as an accurate hover/tooltip hit area.
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
@endphp

<div class="home-modern-hero">
    <div class="row align-items-center">

        <div class="col-lg-8">

            <span class="home-modern-hero-label">
                <i class="ti ti-school me-1"></i>
                Learning Management System
            </span>

            <h1 class="home-modern-hero-greeting">Welcome back,</h1>

            <h1 class="home-modern-hero-name">{{ auth()->user()->name }}</h1>

            <p class="home-modern-hero-subtitle">
                Continuing journey as <strong>{{ auth()->user()->team }}</strong>
            </p>

            <div class="home-modern-hero-stats">
                {{-- <span class="home-modern-hero-stat-pill home-modern-hero-stat-blue">
                    <strong>{{ $ongoingCourse }}</strong> On Going
                </span> --}}
                <span class="home-modern-hero-stat-pill home-modern-hero-stat-green">
                    <strong>{{ $totalLearningDisplay }}</strong> Total Learning
                </span>
            </div>

            {{-- <div class="home-modern-hero-badges">
                <span class="home-modern-badge-streak">
                    <i class="ti ti-flame me-1"></i>Learning Streak
                </span>
                <span class="home-modern-badge-progress">
                    <i class="ti ti-check me-1"></i>{{ $progressPercent }}% Completed
                </span>
            </div> --}}

        </div>

        <div class="col-lg-4 text-center mt-4 mt-lg-0">

            <div class="home-modern-donut-wrap">

                <div class="home-modern-donut-outer"
                     style="background: conic-gradient(
                        #22c55e 0%,
                        #22c55e {{ $donutBlendStart }}%,
                        #38bdf8 {{ $donutBlendEnd }}%,
                        #38bdf8 100%
                     )">

                    <div class="home-modern-donut-hit home-modern-donut-hit-completed"
                         style="clip-path: {{ $completedSlicePath }}"
                         data-bs-toggle="tooltip" data-bs-placement="top"
                         title="Completed {{ $completedCourseVal }} Learning"></div>

                    <div class="home-modern-donut-hit home-modern-donut-hit-ongoing"
                         style="clip-path: {{ $ongoingSlicePath }}"
                         data-bs-toggle="tooltip" data-bs-placement="top"
                         title="On Going {{ $ongoingCourseVal }} Learning"></div>

                    <div class="home-modern-donut-inner">
                        <strong>{{ $totalLearningDisplay }}</strong>
                        <small>Total Learning</small>
                    </div>
                </div>

                <div class="home-modern-donut-legend">
                    <span class="home-modern-donut-legend-item completed">
                        <i class="home-modern-dot completed"></i> Completed {{ $completedCourseVal }}
                    </span>
                    <span class="home-modern-donut-legend-item ongoing">
                        <i class="home-modern-dot ongoing"></i> On Going {{ $ongoingCourseVal }}
                    </span>
                </div>

            </div>

        </div>

    </div>
</div>
