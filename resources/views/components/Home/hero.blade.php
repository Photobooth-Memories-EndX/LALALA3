@php
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

            $points[] = round($x,2).'%' . ' ' . round($y,2).'%';
        }

        return 'polygon('.implode(', ', $points).')';
    };

    $completedSlicePath = $donutSlicePolygon(0,$completedPercent);
    $ongoingSlicePath = $donutSlicePolygon($completedPercent,100);
@endphp

<div class="home-modern-hero">
    <div class="row align-items-center">

        {{-- ===================================================== --}}
        {{-- LEFT SIDE --}}
        {{-- ===================================================== --}}
        <div class="col-lg-8">
            <span class="home-modern-hero-label">
                <i class="ti ti-school me-1"></i>
                Learning Management System
            </span>

            <h1 class="home-modern-hero-greeting">Welcome back,</h1>
            <h1 class="home-modern-hero-name">{{ auth()->user()->name ?? 'ESTIHONO HERMAWAN' }}</h1>

            <p class="home-modern-hero-subtitle">
                Continuing journey as <strong>{{ auth()->user()->team ?? 'Training Team' }}</strong>
            </p>

            <div class="home-modern-hero-actions">
                <span class="home-modern-hero-stat-pill home-modern-hero-stat-green">
                    <strong>Keep Learning 🔥</strong>
                </span>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- RIGHT SIDE (SUMMARY & DONUT) --}}
        {{-- ===================================================== --}}
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="home-modern-donut-wrap">

                {{-- Judul Ringkasan --}}
                <div class="home-modern-summary-title">
                    Your Learning Summary
                </div>

                {{-- Lingkaran Donut Chart (Learning Hours) --}}
                <div class="home-modern-donut-outer"
                     style="background: conic-gradient(#22c55e 0%, #22c55e {{ $donutBlendStart }}%, #38bdf8 {{ $donutBlendEnd }}%, #38bdf8 100%);">

                    <div class="home-modern-donut-hit home-modern-donut-hit-completed"
                         style="clip-path:{{ $completedSlicePath }}"
                         data-bs-toggle="tooltip" data-bs-placement="top"
                         title="Completed {{ $completedCourseVal }} Learning">
                    </div>

                    <div class="home-modern-donut-hit home-modern-donut-hit-ongoing"
                         style="clip-path:{{ $ongoingSlicePath }}"
                         data-bs-toggle="tooltip" data-bs-placement="top"
                         title="On Going {{ $ongoingCourseVal }} Learning">
                    </div>

                    <div class="home-modern-donut-inner">
                        <strong>{{ $learningHours }}</strong>
                        <small>Learning Hours</small>
                    </div>
                </div>

                {{-- Tampilan List Bersih & Terbuka (Tanpa Table / Tanpa Box) --}}
                <div class="tabler-clean-list-container">

                    <!-- Row Total Course -->
                    <div class="tabler-clean-row">
                        <div class="tabler-clean-meta">
                            <span class="home-modern-stat-dot total"></span>
                            <span class="clean-label">Total Course</span>
                        </div>
                        <div class="clean-value">{{ $totalCourseVal }}</div>
                    </div>

                    <!-- Row Completed -->
                    <div class="tabler-clean-row">
                        <div class="tabler-clean-meta">
                            <span class="home-modern-stat-dot completed"></span>
                            <span class="clean-label">Completed</span>
                        </div>
                        <div class="clean-value">{{ $completedCourseVal }}</div>
                    </div>

                    <!-- Row On Going -->
                    <div class="tabler-clean-row">
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
