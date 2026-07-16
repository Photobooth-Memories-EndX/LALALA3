@props([
    'user' => null,
    'learningHours' => 0,
    'totalLearning' => 0,
    'completedLearning' => 0,
    'ongoingLearning' => 0,
    'passedAssessment' => 0,
])

@php
    $totalLearning = (int) ($totalLearning ?? 0);
    $completedLearning = (int) ($completedLearning ?? 0);
    $ongoingLearning = (int) ($ongoingLearning ?? 0);
    $passedAssessment = (int) ($passedAssessment ?? 0);
@endphp

<div class="card border-0 shadow-sm overflow-hidden learning-hero mb-4">
    <div class="card-body">
        <div class="row align-items-center g-4 learning-hero-row">
            <div class="col-lg-6">
                <div class="learning-hero-copy">
                    <span class="learning-hero-label">
                        <i class="ti ti-school"></i>
                        My Learning
                    </span>

                    <h2 class="learning-hero-name">Learning Dashboard</h2>

                    <p class="learning-hero-description">
                        Track your ongoing learning, review records, assessments, and certificates in one place.
                    </p>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="learning-hour-card">
                    <strong>{{ $learningHours }}</strong>
                    <span>Learning Hours</span>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="learning-hero-stats">
                    <div class="learning-mini-stat learning-stat-total">
                        <div class="learning-mini-stat-label">Total Learning</div>
                        <div class="learning-mini-stat-value">{{ $totalLearning }}</div>
                    </div>

                    <div class="learning-mini-stat learning-stat-completed">
                        <div class="learning-mini-stat-label">Completed</div>
                        <div class="learning-mini-stat-value">{{ $completedLearning }}</div>
                    </div>

                    <div class="learning-mini-stat learning-stat-ongoing">
                        <div class="learning-mini-stat-label">On Going</div>
                        <div class="learning-mini-stat-value">{{ $ongoingLearning }}</div>
                    </div>

                    <div class="learning-mini-stat learning-stat-assessment">
                        <div class="learning-mini-stat-label">Assessment Pass</div>
                        <div class="learning-mini-stat-value">{{ $passedAssessment }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* =====================================================
   LEARNING HERO CARD
===================================================== */

.learning-hero {
    position: relative;
    isolation: isolate;
    border-radius: 22px;
    background:
        radial-gradient(circle at 82% 20%, rgba(255, 255, 255, .16), transparent 31%),
        linear-gradient(135deg, #0041b9 0%, #1248a7 45%, #2a72ca 100%);
    color: #fff;
    box-shadow: 0 18px 44px rgba(15, 23, 42, .16) !important;
}

.learning-hero .card-body {
    min-height: 238px;
    padding: 28px 34px;
    background: transparent !important;
    display: flex;
    align-items: center;
}

.learning-hero::before {
    content: "";
    position: absolute;
    width: 300px;
    height: 300px;
    top: -126px;
    right: -96px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .08);
    pointer-events: none;
    z-index: -1;
}

.learning-hero-row {
    min-height: 182px;
    width: 100%;
}

.learning-hero-copy {
    min-width: 0;
}

.learning-hero-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 11px;
    margin-bottom: 8px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    line-height: 1;
    backdrop-filter: blur(8px);
}

.learning-hero-name {
    margin: 0 0 12px;
    color: #fff;
    font-size: clamp(30px, 3.4vw, 44px);
    font-weight: 700;
    line-height: 1.04;
    letter-spacing: 0;
    overflow-wrap: anywhere;
}

.learning-hero-description {
    max-width: 560px;
    margin: 0;
    color: rgba(255, 255, 255, .78);
    font-size: 15px;
    line-height: 1.65;
}

.learning-hour-card {
    width: 158px;
    margin: 0 auto;
    min-height: 118px;
    padding: 18px 14px;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 18px;
    background: rgba(255, 255, 255, .08);
    box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    transition: transform .2s ease, box-shadow .2s ease;
}

.learning-hour-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 28px rgba(15, 23, 42, .16);
}

.learning-hour-card strong {
    color: #fff;
    font-size: 42px;
    font-weight: 700;
    line-height: 1;
}

.learning-hour-card span {
    margin-top: 6px;
    color: rgba(255, 255, 255, .68);
    font-size: 11px;
    font-weight: 700;
    line-height: 1.2;
    text-transform: uppercase;
    letter-spacing: .8px;
}

.learning-hero-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 7px;
}

.learning-mini-stat {
    min-height: 58px;
    padding: 8px 9px;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border: 1px solid rgba(255, 255, 255, .18);
    box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
    transition: transform .2s ease, box-shadow .2s ease;
}

.learning-mini-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 28px rgba(15, 23, 42, .18);
}

.learning-stat-total {
    background: linear-gradient(135deg, rgba(59, 130, 246, .96), rgba(37, 99, 235, .86));
}

.learning-stat-completed {
    background: linear-gradient(135deg, rgba(34, 197, 94, .96), rgba(22, 163, 74, .86));
}

.learning-stat-ongoing {
    background: linear-gradient(135deg, rgba(14, 165, 233, .96), rgba(2, 132, 199, .86));
}

.learning-stat-assessment {
    background: linear-gradient(135deg, rgba(245, 158, 11, .98), rgba(217, 119, 6, .88));
}

.learning-mini-stat-label {
    min-height: 18px;
    color: rgba(255, 255, 255, .82);
    font-size: 8px;
    font-weight: 700;
    line-height: 1.25;
    text-transform: uppercase;
}

.learning-mini-stat-value {
    color: #fff;
    font-size: 20px;
    font-weight: 700;
    line-height: 1;
}

[data-bs-theme="dark"] .learning-hero {
    background:
        radial-gradient(circle at 82% 20%, rgba(255, 255, 255, .08), transparent 31%),
        linear-gradient(135deg, #0c1e38 0%, #132e6a 100%);
    box-shadow: 0 18px 42px rgba(0, 0, 0, .34) !important;
}

[data-bs-theme="dark"] .learning-hour-card {
    background: rgba(255, 255, 255, .06);
    border-color: rgba(255, 255, 255, .12);
}

@media (max-width: 991.98px) {
    .learning-hero .card-body {
        min-height: auto;
        padding: 28px 24px;
    }

    .learning-hero-row {
        min-height: 0;
    }

    .learning-hour-card {
        margin-left: 0;
    }
}

@media (max-width: 767.98px) {
    .learning-hero {
        border-radius: 18px;
    }

    .learning-hero .card-body {
        padding: 22px 18px;
    }

    .learning-hero-name {
        font-size: 26px;
    }

    .learning-hour-card {
        width: 142px;
        min-height: 104px;
    }

    .learning-hour-card strong {
        font-size: 34px;
    }
}
</style>
