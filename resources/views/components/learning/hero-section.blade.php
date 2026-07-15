@props([
    'user' => null,
    'learningHours' => 0,
    'totalLearning' => 0,
    'completedLearning' => 0,
    'ongoingLearning' => 0,
    'passedAssessment' => 0,
])

@php
    $name = $user->name ?? 'User';
    $initial = strtoupper(substr($name, 0, 1));
    $position = $user->position ?? $user->job_title ?? null;
@endphp

<div class="card border-0 shadow rounded-4 overflow-hidden learning-hero mb-4">
    <div class="card-body p-4 p-xl-5">

        <div class="row align-items-center h-100">

            {{-- ================= AREA 1: PROFILE (55%) ================= --}}
            <div class="col-lg-6 col-md-12 mb-4 mb-lg-0">

                <div class="d-flex flex-column align-items-center align-lg-items-start text-center text-lg-start h-100 justify-content-center">

                    {{-- Avatar --}}
                    <div
                        class="avatar rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center mb-3 mb-lg-0 me-lg-4"
                        style="width:140px;height:140px;font-size:64px;flex-shrink:0;">
                        {{ $initial }}
                    </div>

                    {{-- Profile --}}
                    <div class="flex-grow-1">

                        <h2 class="text-white fw-bold mb-3"
                            style="font-size:1.9rem;line-height:1.2;">
                            {{ $name }}
                        </h2>

                        <div class="text-white-75 mb-2" style="font-size:15px;font-weight:600;letter-spacing:.3px;">
                            {{ $user->id_emp ?? '-' }}

                            @if(!empty($user->team))
                                • {{ $user->team }}
                            @endif
                        </div>

                        <div class="text-white-75 mb-2" style="font-size:15px;font-weight:500;">
                            {{ $user->department ?? '-' }}

                            @if(!empty($position))
                                • {{ $position }}
                            @endif
                        </div>

                        <div class="text-white-75"
                             style="font-size:14px;">
                            {{ $user->email ?? '-' }}
                        </div>

                    </div>

                </div>

            </div>

            {{-- ================= AREA 2: LEARNING HOUR (20%) ================= --}}
            <div class="col-lg-3 col-md-12 mb-4 mb-lg-0">

                <div class="learning-hour-card">

                    <div class="learning-hour-label">
                        Learning Hour
                    </div>

                    <div class="learning-hour-value">
                        {{ $learningHours }}
                    </div>

                    <div class="learning-hour-unit">
                        Hours
                    </div>

                </div>

            </div>

            {{-- ================= AREA 3: STATISTICS (25%) ================= --}}
            <div class="col-lg-3 col-md-12">

                <div class="row g-2">

                    <div class="col-6">
                        <div class="learning-mini-stat">

                            <div class="learning-mini-stat-label">
                                TOTAL LEARNING
                            </div>

                            <div class="learning-mini-stat-value">
                                {{ $totalLearning }}
                            </div>

                        </div>
                    </div>

                    <div class="col-6">
                        <div class="learning-mini-stat">

                            <div class="learning-mini-stat-label">
                                COMPLETED
                            </div>

                            <div class="learning-mini-stat-value">
                                {{ $completedLearning }}
                            </div>

                        </div>
                    </div>

                    <div class="col-6">
                        <div class="learning-mini-stat">

                            <div class="learning-mini-stat-label">
                                ON GOING
                            </div>

                            <div class="learning-mini-stat-value">
                                {{ $ongoingLearning }}
                            </div>

                        </div>
                    </div>

                    <div class="col-6">
                        <div class="learning-mini-stat">

                            <div class="learning-mini-stat-label">
                                ASSESSMENT PASS
                            </div>

                            <div class="learning-mini-stat-value">
                                {{ $passedAssessment }}
                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

<style>
/* =====================================================
   LEARNING HERO CARD — 3-Column Layout
===================================================== */

.learning-hero {
background:linear-gradient(
        135deg,
        #0041b9 0%,
        #1248a7 45%,
        #2a72ca 100%
    );
}

.learning-hero .card-body {
    min-height: 280px;
}

/* Area 1: Profile */
.learning-hero h2 {
    font-size: 1.9rem;
}

@media (min-width: 992px) {
    .learning-hero .row {
        display: flex;
        align-items: center;
    }

    .learning-hero .col-lg-6 .avatar {
        margin-right: 1.5rem;
        margin-bottom: 0;
    }

    .learning-hero .col-lg-6 {
        display: flex;
        align-items: center;
    }

    .learning-hero .col-lg-6 .d-flex {
        flex-direction: row !important;
        text-align: left !important;
    }
}

/* Area 2: Learning Hour Card */
.learning-hour-card {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 16px;
    padding: 45px 15px;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    backdrop-filter: blur(12px);
    transition: 0.25s ease;
}

.learning-hour-card:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.18);
}

.learning-hour-label {
    color: rgba(255, 255, 255, 0.78);
    font-size: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 8px;
}

.learning-hour-value {
    color: #fff;
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 4px;
}

.learning-hour-unit {
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.875rem;
    font-weight: 500;
}

/* Area 3: Statistics */
.learning-mini-stat{

    width:100%;

    height:96px;

    padding:16px 18px;

    border-radius:16px;

    background:rgba(255,255,255,.12);

    border:1px solid rgba(255,255,255,.16);

    display:flex;

    flex-direction:column;

    justify-content:space-between;

    transition:.25s;
}

.learning-mini-stat-label{

    min-height:30px;

    display:flex;

    align-items:flex-start;

    color:rgba(255,255,255,.75);

    font-size:11px;

    font-weight:700;

    line-height:1.2;

    text-transform:uppercase;
}

.learning-mini-stat-value{

    font-size:30px;

    font-weight:700;

    color:#fff;

    line-height:1;
}

/* Responsive */
@media (max-width: 991px) {
    .learning-hero .card-body {
        min-height: auto;
        padding: 2rem 1rem;
    }

    .learning-hero .row {
        margin: 0;
    }

    .learning-hero .col-lg-6,
    .learning-hero .col-lg-3 {
        margin-bottom: 2rem;
    }

    .learning-hero .col-lg-6 .d-flex {
        text-align: center !important;
        align-items: center !important;
        flex-direction: column !important;
    }

    .learning-hero .col-lg-6 .avatar {
        margin-right: 0 !important;
        margin-bottom: 1rem;
    }

    .learning-hour-card {
        min-height: 100px;
    }

    .learning-hour-value {
        font-size: 2.2rem;
    }

    .learning-hour-label {
        font-size: 0.8rem;
    }

    .learning-hour-unit {
        font-size: 0.8rem;
    }

    .learning-mini-stat {
        min-height: 80px;
        margin-top: 10px;
    }

    .learning-mini-stat-value {
        font-size: 1.7rem;
    }
}

@media (max-width: 767px) {
    .learning-hero .card-body {
        padding: 1.5rem 1rem;
    }

    .learning-hour-label {
        font-size: 12px;
    }

    .learning-hour-value {
        font-size: 2.2rem;
        margin-bottom: 4px;
    }

    .learning-hour-unit {
        font-size: 12px;
    }

    .learning-hero h2 {
        font-size: 1.5rem;
    }
}
</style>
