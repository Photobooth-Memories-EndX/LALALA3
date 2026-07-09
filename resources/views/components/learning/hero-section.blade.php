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

        <div class="row align-items-center">

            {{-- ================= PROFILE ================= --}}
            <div class="col-lg-7 pe-lg-4">

                <div class="d-flex align-items-center">

                    {{-- Avatar --}}
                    <div
                        class="avatar rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center me-4"
                        style="width:95px;height:95px;font-size:42px;flex-shrink:0;">
                        {{ $initial }}
                    </div>

                    {{-- Profile --}}
                    <div class="flex-grow-1">

                        <h2 class="text-white fw-bold mb-2"
                            style="font-size:1.9rem;line-height:1.2;">
                            {{ $name }}
                        </h2>

                        <div class="text-white-75 mb-1" style="font-size:14px;">
                            {{ $user->id_emp ?? '-' }}

                            @if(!empty($user->team))
                                • {{ $user->team }}
                            @endif
                        </div>

                        <div class="text-white-75 mb-1" style="font-size:14px;">
                            {{ $user->department ?? '-' }}

                            @if(!empty($position))
                                • {{ $position }}
                            @endif
                        </div>

                        <div class="text-white-75 mb-3"
                             style="font-size:14px;">
                            {{ $user->email ?? '-' }}
                        </div>

                        <span class="badge bg-white text-primary rounded-pill px-3 py-2 shadow-sm"
                              style="font-size:.82rem;font-weight:600;">
                            <i class="ti ti-clock me-1"></i>
                            {{ $learningHours }} Learning Hours
                        </span>

                    </div>

                </div>

            </div>

            {{-- ================= STATISTICS ================= --}}
            <div class="col-lg-5">

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
.learning-mini-stat{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.15);
    backdrop-filter:blur(12px);
    border-radius:16px;
    padding:16px 18px;
    min-height:88px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    transition:.25s ease;
}

.learning-mini-stat:hover{
    transform:translateY(-2px);
    background:rgba(255,255,255,.18);
}

.learning-mini-stat-label{
    color:rgba(255,255,255,.78);
    font-size:.68rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.6px;
    margin-bottom:6px;
}

.learning-mini-stat-value{
    color:#fff;
    font-size:2rem;
    font-weight:700;
    line-height:1;
}

@media (max-width:991px){

    .learning-mini-stat{
        min-height:80px;
        margin-top:10px;
    }

    .learning-mini-stat-value{
        font-size:1.7rem;
    }

    .learning-hero .avatar{
        width:80px !important;
        height:80px !important;
        font-size:36px !important;
    }

}
</style>