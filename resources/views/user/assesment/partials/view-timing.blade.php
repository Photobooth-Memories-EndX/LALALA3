@php
    $hasPeriod = $assesment->hasAccessPeriod() && !empty($assesment->fromdate) && !empty($assesment->todate);
    $hasTimer = $assesment->hasTimeLimit();
    $hasDeadline = $hasPeriod || $hasTimer;

    $subHeadingDisplay = 'Assessment';
    $typeBadgeDisplay  = 'Content Assessment';

    if (($assesment->type ?? '') === 'global') {
        $subHeadingDisplay = 'Global Assessment';
        $typeBadgeDisplay  = 'Global Assessment';
    } elseif (($assesment->type ?? '') === 'content') {
        $contentFor = $assesment->assesment_content_for ?? ($assesment->assessment_content_for ?? 'content');
        if ($contentFor === 'playlist') {
            $subHeadingDisplay = 'Assessment (Playlist)';
            $typeBadgeDisplay  = 'Content Assessment (Playlist)';
        } else {
            $subHeadingDisplay = 'Assessment (All Content)';
            $typeBadgeDisplay  = 'Content Assessment (All Content)';
        }
    } elseif (!empty($assesment->type)) {
        $subHeadingDisplay = ucwords(str_replace('_', ' ', $assesment->type)) . ' Assessment';
        $typeBadgeDisplay  = $subHeadingDisplay;
    }

    $isGlobal = ($assesment->type ?? '') === 'global';
    $calcCurrentAttempt = $currentAttempt ?? 1;
    $calcMaxAttempts = $maxAttempts ?? (1 + (int) ($assesment->total_remedials ?? 0));
@endphp

<div class="row g-3 align-items-start mb-3">
    <div class="{{ $hasDeadline ? 'col-lg-9' : 'col-12' }}">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="flex-fill">
                        <div class="text-muted small mb-1">{{ $subHeadingDisplay }}</div>
                        <h2 class="mb-1">{{ $assesment->title ?? '-' }}</h2>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-blue-lt text-blue">
                                Passing Grade: {{ $assesment->passing_grade ?? '-' }}
                            </span>

                            <span class="badge bg-cyan-lt text-cyan">
                                Type: {{ $typeBadgeDisplay }}
                            </span>

                            @if ($isGlobal)
                                <span class="badge bg-purple-lt text-purple">
                                    Batch: {{ $assesment->batch ?? '-' }}
                                </span>
                            @else
                                <span class="badge bg-indigo-lt text-indigo">
                                    Attempt: {{ $calcCurrentAttempt }} / {{ $calcMaxAttempts }}
                                </span>
                            @endif

                            @if ($hasPeriod)
                                <span class="badge bg-orange-lt text-orange">
                                    Assessment Period: {{ date('d M H:i', strtotime($assesment->fromdate)) }} - {{ date('d M H:i', strtotime($assesment->todate)) }}
                                </span>
                            @endif

                            @if ($hasTimer)
                                <span class="badge bg-blue-lt text-blue">
                                    Time Limit: {{ $assesment->time_limit_minutes }} mins
                                </span>
                            @endif

                            @if (!$hasDeadline)
                                <span class="badge bg-green-lt text-green">
                                    No Deadline
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($hasDeadline && (!empty($countDownDate) || !empty($expiresAtIso)))
        <div class="col-lg-3" id="timing-card-container">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" id="timing-card" style="top: 1rem; z-index: 10;">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1" id="timer-label">Time Remaining</div>
                    <div id="timer" class="text-danger fw-bold">
                        <div class="small text-uppercase text-muted fw-semibold mb-1" id="timer-days">-- DAYS</div>
                        <div class="h2 mb-0 font-monospace text-danger fw-bold" id="timer-clock">--:--:--</div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            @media (max-width: 991.98px) {
                #timing-card.mobile-sticky-timer {
                    position: fixed !important;
                    top: var(--mobile-timer-top, 0px) !important;
                    left: 0 !important;
                    right: 0 !important;
                    width: 100% !important;
                    max-width: 100vw !important;
                    z-index: 1040 !important;
                    border-radius: 0 0 0.75rem 0.75rem !important;
                    margin: 0 !important;
                    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15) !important;
                    border: none !important;
                    transition: background-color 0.2s ease, box-shadow 0.2s ease;
                }

                #timing-card.mobile-sticky-timer .card-body {
                    padding: 0.5rem 1rem !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    gap: 0.75rem !important;
                    text-align: center !important;
                }

                #timing-card.mobile-sticky-timer #timer-label {
                    margin-bottom: 0 !important;
                    font-size: 0.85rem !important;
                    font-weight: 600 !important;
                    white-space: nowrap !important;
                }

                #timing-card.mobile-sticky-timer #timer {
                    display: inline-flex !important;
                    align-items: center !important;
                    gap: 0.5rem !important;
                }

                #timing-card.mobile-sticky-timer #timer .small,
                #timing-card.mobile-sticky-timer #timer-days {
                    margin-bottom: 0 !important;
                    font-size: 0.75rem !important;
                    white-space: nowrap !important;
                }

                #timing-card.mobile-sticky-timer #timer .h1,
                #timing-card.mobile-sticky-timer #timer .h2,
                #timing-card.mobile-sticky-timer #timer-clock {
                    font-size: 1.15rem !important;
                    margin-bottom: 0 !important;
                    line-height: 1 !important;
                }
            }
        </style>

        <script>
            (function() {
                function initMobileStickyTimer() {
                    var timingCard = document.getElementById('timing-card');
                    var timingContainer = document.getElementById('timing-card-container');
                    if (!timingCard || !timingContainer) return;

                    var spacer = document.getElementById('timing-card-spacer');
                    if (!spacer) {
                        spacer = document.createElement('div');
                        spacer.id = 'timing-card-spacer';
                        spacer.style.display = 'none';
                        timingContainer.insertBefore(spacer, timingCard);
                    }

                    function checkSticky() {
                        var isMobile = window.innerWidth < 992;
                        if (!isMobile) {
                            if (timingCard.classList.contains('mobile-sticky-timer')) {
                                timingCard.classList.remove('mobile-sticky-timer');
                                timingCard.style.removeProperty('--mobile-timer-top');
                                spacer.style.display = 'none';
                            }
                            return;
                        }

                        var topOffset = 0;
                        var fixedHeader = document.querySelector('.navbar.fixed-top, header.fixed-top, .app-topbar.fixed-top');
                        if (fixedHeader) {
                            topOffset = fixedHeader.offsetHeight;
                        }

                        var containerRect = timingContainer.getBoundingClientRect();

                        if (containerRect.top <= topOffset) {
                            if (!timingCard.classList.contains('mobile-sticky-timer')) {
                                spacer.style.height = timingCard.offsetHeight + 'px';
                                spacer.style.display = 'block';
                                timingCard.classList.add('mobile-sticky-timer');
                            }
                            timingCard.style.setProperty('--mobile-timer-top', topOffset + 'px');
                        } else {
                            if (timingCard.classList.contains('mobile-sticky-timer')) {
                                timingCard.classList.remove('mobile-sticky-timer');
                                timingCard.style.removeProperty('--mobile-timer-top');
                                spacer.style.display = 'none';
                            }
                        }
                    }

                    window.addEventListener('scroll', checkSticky, { passive: true });
                    window.addEventListener('resize', checkSticky, { passive: true });
                    checkSticky();
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initMobileStickyTimer);
                } else {
                    initMobileStickyTimer();
                }
            })();
        </script>
    @endif
</div>
