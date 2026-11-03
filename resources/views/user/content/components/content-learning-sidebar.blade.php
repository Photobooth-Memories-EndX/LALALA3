@php
    $progress = (int) ($content_user->progress ?? 0);
    $readPages = (int) ($content_user->last_page ?? 0);
    $totalPages = (int) ($content_user->total_pages ?? 0);
    $fromDate = $content->from_date ? \Carbon\Carbon::parse($content->from_date)->format('d M Y') : null;
    $toDate = $content->to_date ? \Carbon\Carbon::parse($content->to_date)->format('d M Y') : null;
    $deadlineRange = $fromDate || $toDate ? ($fromDate ?? '-') . ' - ' . ($toDate ?? '-') : '-';
    $bookmarkCount = $bookmarkCount ?? 0;
    $discussionCount = $discussionCount ?? 0;
    $isBookmarked = $isBookmarked ?? false;

    $favoritesCount = $favoritesCount ?? $content->favorites_count ?? $bookmarkCount;
    $isFavorite = $isFavorite ?? $content->is_favorite ?? $isBookmarked;

    $learningHoursVal = $content->learning_hours ?? null;
    if ($learningHoursVal === null || $learningHoursVal === '') {
        $durationSeconds = \App\Support\DurationFormatter::toSeconds($content->duration);
        $learningHoursVal = $durationSeconds > 0 ? round($durationSeconds / 3600, 1) : 0.5;
    }
    $learningHoursNum = (float) $learningHoursVal;
    $learningHoursDisplay = number_format($learningHoursNum, 2);
    $learningHoursText = $learningHoursDisplay . ' Hours';

    $learningStatusLabels = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'paused' => 'Paused',
        'completed' => 'Completed',
    ];
    $learningStatusLabel = $learningStatusLabels[$content_user->learning_status ?? ''] ?? 'In Progress';
@endphp

<div class="d-flex flex-column gap-3 content-sidebar-stack">

    <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card">
        <div class="card-body p-4">

            {{-- Header Progress & Badge Status --}}
            <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted small fw-semibold mb-1">
                        Your Progress
                    </div>

                    <h2 id="contentProgressValue" class="fw-bold text-primary mb-2 fs-2" style="line-height:1;">
                        {{ $progress }}%
                    </h2>

                    @if (in_array($content->type, ['Document', 'SOP/Regulation']))
                        <div class="text-nowrap small text-muted d-inline-block mb-1">
                            <i class="ti ti-book-2 me-1"></i><span class="pages-read-text">{{ $readPages }}/{{ $totalPages }} pages read</span>
                        </div>
                    @endif
                </div>

                {{-- Badge Status dengan Hijau Tua saat Completed --}}
                <div class="ms-2 flex-shrink-0">
                    <span class="badge rounded-pill {{ $learningStatusLabel === 'Completed' ? 'badge-learning-completed bg-success-lt fw-bold' : 'bg-primary-lt text-primary' }} px-3 py-2 border-0 d-inline-flex align-items-center">
                        <i class="ti ti-check me-1"></i>
                        <span id="contentProgressStatus">{{ $learningStatusLabel }}</span>
                    </span>
                </div>
            </div>

            {{-- Teks Dikeluarkan dari Flex Kolom agar Bisa Membentang Penuh Rata Kanan Kiri --}}
            <div class="text-muted small w-100 mb-3 text-justify" style="text-align: justify; text-justify: inter-word;">
                Keep going! You're making great progress.
            </div>

            <div class="progress mb-4" style="height:8px;">
                <div
                    id="contentProgressBar"
                    class="progress-bar bg-primary rounded-pill"
                    style="width: {{ $progress }}%">
                </div>
            </div>

            <div class="text-start mb-2">
                <span class="text-muted small d-block">
                    Learning Duration
                </span>

                <span class="fw-semibold text-dark d-block">
                    <i class="ti ti-clock me-1 text-muted"></i>
                    {{ ($content->type === 'Document' && (int) ($content->page_duration ?? 30) === 0) ? 'Self-paced' : \App\Support\DurationFormatter::format($content->duration, 'Self-paced') }}
                </span>
            </div>

            <div class="text-start mb-2">
                <span class="text-muted small d-block">
                    Learning Hours
                </span>

                <span class="fw-semibold text-dark d-block">
                    <i class="ti ti-hourglass me-1 text-muted"></i>
                    {{ $learningHoursText }}
                </span>
            </div>

            <div class="text-start">
                <span class="text-muted small d-block">
                    Period
                </span>

                <span class="fw-semibold text-dark d-block">
                    <i class="ti ti-calendar-due me-1 text-muted"></i>
                    {{ $deadlineRange }}
                </span>
            </div>

        </div>
    </div>

    {{-- TASK CARD (jika content memiliki task) --}}
    @php
        $sidebarContentTasks = \App\Models\Task::where('parent_type', 'content')
            ->where('content_id', $content->id)
            ->published()
            ->get();
        $sidebarTaskCount = $sidebarContentTasks->count();
        $isContentCompleted = $progress >= 100 || ($content_user->learning_status ?? '') === 'completed';
    @endphp

    @if ($sidebarTaskCount > 0)
        <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card content-task-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-purple-lt text-purple px-2 py-1 rounded-pill small fw-bold">
                        <i class="ti ti-list-check me-1"></i> TASK
                    </span>
                    <span class="small text-muted">{{ $sidebarTaskCount }} {{ \Illuminate\Support\Str::plural('Task', $sidebarTaskCount) }}</span>
                </div>
                <div class="text-muted small mb-3">
                    @if ($isContentCompleted)
                        Content completed. Start your task now.
                    @else
                        Complete 100% of the content to unlock Task.
                    @endif
                </div>

                @if ($isContentCompleted)
                    <a href="{{ route('user.learning.dashboard', ['learning_tab' => 'my-task', 'my_task_parent' => 'content:' . $content->id]) }}"
                       class="btn btn-primary w-100 rounded-pill shadow-sm">
                        <i class="ti ti-list-check me-1"></i> Open Task
                    </a>
                @else
                    <button type="button"
                            class="btn btn-secondary disabled w-100 rounded-pill opacity-75"
                            disabled
                            style="cursor: not-allowed;"
                            title="Complete 100% of the content to unlock Task.">
                        <i class="ti ti-lock me-1"></i> Task Locked
                    </button>
                @endif
            </div>
        </div>
    @endif

    @php
        $assessmentObj = $assessment ?? $assesment ?? null;
    @endphp

    @if ($assessmentObj)
        @php
            $attempts = $assessmentObj->attempts_count ?? $assessmentCount ?? 0;
            $maxAttempts = $maxAttempts ?? ($assessmentObj->max_attempts ?? (isset($assessmentObj->total_remedials) ? (1 + (int)$assessmentObj->total_remedials) : 3));
            $latestScore = $assessmentObj->latest_score ?? $assessmentLatestScores ?? 0;
            $passingGrade = $assessmentObj->passing_grade ?? 80;
            $isPassed = $isPassed ?? ($latestScore >= $passingGrade);
            $meetsProgressRequirement = isset($content_user) && method_exists($content_user, 'meetsAssessmentProgressRequirement')
                ? $content_user->meetsAssessmentProgressRequirement()
                : true;
            $assessmentSlug = $assessmentObj->slug ?? '#';

            $fromDateVal = !empty($assessmentObj->fromdate) ? date('d M Y, H:i', strtotime($assessmentObj->fromdate)) : '-';
            $toDateVal = !empty($assessmentObj->todate) ? date('d M Y, H:i', strtotime($assessmentObj->todate)) : '-';
            $batchDisplay = !empty($assessmentObj->batch) && $assessmentObj->batch !== '-'
                ? (str_contains(strtolower((string)$assessmentObj->batch), 'batch') ? $assessmentObj->batch : 'Batch ' . $assessmentObj->batch)
                : 'Batch 1';
            $assessmentDurationVal = $assessmentObj->duration ?? ($assessmentObj->time_limit_minutes ?? 60);

            $isInactive = $isInactive ?? (strtoupper(trim((string)($assessmentObj->status ?? ''))) === 'NOT');
            $isExpired = $isExpired ?? false;
            $isBeforeStart = $isBeforeStart ?? false;
            $actionType = $actionType ?? ($attempts == 0 ? 'start' : ($attempts < $maxAttempts ? 'remedial' : 'max_attempts'));
        @endphp

        <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card">
            <div class="card-body p-4">

                <h3 class="card-title mb-3">
                    <i class="ti ti-clipboard-check text-primary me-1"></i>
                    Assessment
                </h3>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Passing Grade</span>
                    <strong>{{ $passingGrade }}</strong>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Latest Score</span>
                    <strong>{{ $latestScore }}</strong>
                </div>

                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Attempt</span>
                    <strong>{{ $attempts }}/{{ $maxAttempts }}</strong>
                </div>

                @if ($isPassed)
                    <button type="button" class="btn btn-success disabled w-100 rounded-pill" disabled style="cursor: not-allowed;">
                        <i class="ti ti-check me-1"></i> Passed
                    </button>
                @elseif ($isInactive)
                    <button type="button"
                            class="btn btn-secondary disabled w-100 rounded-pill"
                            disabled
                            aria-disabled="true"
                            tabindex="-1"
                            style="cursor: not-allowed; pointer-events: none; background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important;">
                        <i class="ti ti-alert-triangle me-1"></i> Assessment Inactive
                    </button>
                @elseif ($isExpired)
                    <button type="button"
                            class="btn btn-secondary disabled w-100 rounded-pill"
                            disabled
                            aria-disabled="true"
                            tabindex="-1"
                            style="cursor: not-allowed; pointer-events: none; background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important;">
                        <i class="ti ti-clock-off me-1"></i> Assessment Expired
                    </button>
                @elseif ($isBeforeStart)
                    <button type="button"
                            class="btn btn-secondary disabled w-100 rounded-pill"
                            disabled
                            aria-disabled="true"
                            tabindex="-1"
                            style="cursor: not-allowed; pointer-events: none; background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important;">
                        <i class="ti ti-calendar-time me-1"></i> Not Available Yet
                    </button>
                @elseif (!$meetsProgressRequirement)
                    <button type="button"
                            class="btn btn-secondary disabled w-100 rounded-pill"
                            disabled
                            aria-disabled="true"
                            tabindex="-1"
                            style="cursor: not-allowed;"
                            title="Complete this content to unlock the assessment.">
                        {{ $actionType === 'remedial' ? 'Remedial' : ($actionType === 'continue' ? 'Continue Assessment' : 'Start Assessment') }}
                    </button>
                @elseif ($actionType === 'continue' && !empty($activeDraft))
                    <a href="{{ route('assesment.user.view', $assessmentSlug) }}"
                       class="btn btn-primary w-100 rounded-pill shadow-sm btn-continue-test btn-assessment-continue"
                       style="background-color: #0c5b9e !important; border-color: #0c5b9e !important; color: #ffffff !important;"
                       data-assessment-trigger="true"
                       data-assessment-id="{{ $assessmentObj->id }}"
                       data-assessment-title="{{ $assessmentObj->title }}"
                       data-assessment-type="{{ $assessmentObj->type ?? 'content' }}"
                       data-assessment-type-label="Content"
                       data-assessment-batch="{{ $batchDisplay }}"
                       data-assessment-from-date="{{ $fromDateVal }}"
                       data-assessment-to-date="{{ $toDateVal }}"
                       data-assessment-passing-grade="{{ $passingGrade }}"
                       data-assessment-duration="{{ $assessmentDurationVal }}"
                       data-assessment-user-attempts="{{ $attempts }}"
                       data-assessment-max-attempts="{{ $maxAttempts }}"
                       data-assessment-answered-count="{{ $answeredCount ?? 0 }}"
                       data-assessment-total-count="{{ $totalCount ?? 10 }}"
                       data-assessment-action-type="continue"
                       data-assessment-is-expired="false"
                       data-assessment-is-inactive="false">
                        <i class="ti ti-player-play me-1"></i> Continue Assessment
                    </a>
                @elseif ($attempts == 0)
                    <a href="{{ route('assesment.user.view', $assessmentSlug) }}"
                       class="btn btn-primary w-100 rounded-pill btn-start-test btn-assessment-start"
                       data-assessment-trigger="true"
                       data-assessment-id="{{ $assessmentObj->id }}"
                       data-assessment-title="{{ $assessmentObj->title }}"
                       data-assessment-type="{{ $assessmentObj->type ?? 'content' }}"
                       data-assessment-type-label="Content"
                       data-assessment-batch="{{ $batchDisplay }}"
                       data-assessment-from-date="{{ $fromDateVal }}"
                       data-assessment-to-date="{{ $toDateVal }}"
                       data-assessment-passing-grade="{{ $passingGrade }}"
                       data-assessment-duration="{{ $assessmentDurationVal }}"
                       data-assessment-user-attempts="{{ $attempts }}"
                       data-assessment-max-attempts="{{ $maxAttempts }}"
                       data-assessment-action-type="start"
                       data-assessment-is-expired="false"
                       data-assessment-is-inactive="false">
                        Start Assessment
                    </a>
                @elseif ($attempts < $maxAttempts)
                    <a href="{{ route('assesment.user.view', $assessmentSlug) }}"
                       class="btn w-100 rounded-pill shadow-sm btn-remedial btn-assessment-remedial"
                       style="background-color: #ff7a00 !important; border-color: #ff7a00 !important; color: white !important;"
                       data-assessment-trigger="true"
                       data-assessment-id="{{ $assessmentObj->id }}"
                       data-assessment-title="{{ $assessmentObj->title }}"
                       data-assessment-type="{{ $assessmentObj->type ?? 'content' }}"
                       data-assessment-type-label="Content"
                       data-assessment-batch="{{ $batchDisplay }}"
                       data-assessment-from-date="{{ $fromDateVal }}"
                       data-assessment-to-date="{{ $toDateVal }}"
                       data-assessment-passing-grade="{{ $passingGrade }}"
                       data-assessment-duration="{{ $assessmentDurationVal }}"
                       data-assessment-user-attempts="{{ $attempts }}"
                       data-assessment-max-attempts="{{ $maxAttempts }}"
                       data-assessment-action-type="remedial"
                       data-assessment-is-expired="false"
                       data-assessment-is-inactive="false">
                        <i class="ti ti-refresh me-1"></i> Remedial
                    </a>
                @else
                    <button type="button" class="btn btn-secondary disabled w-100 rounded-pill" disabled style="cursor: not-allowed;">
                        Max Attempt Reached
                    </button>
                @endif

            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card">
        <div class="card-body p-4">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="card-title mb-0">
                    <i class="ti ti-chart-bar text-primary me-1"></i>
                    Content Stats
                </h3>

                @if($isFavorite)
                    <span class="badge rounded-pill bg-primary-lt text-primary">Favorite</span>
                @endif
            </div>

            @include('user.content.components.content-stats')

        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card ai-assistant-card">
        <div class="card-body p-4">

            <div class="d-flex align-items-center justify-content-between mb-1">
                <h3 class="card-title mb-0">
                    <i class="ti ti-robot text-primary me-1"></i>
                    AI Assistant
                </h3>

                <span class="badge rounded-pill bg-success-lt text-success px-3 py-2">
                    <span class="ai-online-dot me-1"></span>
                    Online
                </span>
            </div>

            <div class="text-muted small mb-3">
                <i class="ti ti-sparkles me-1"></i>
                Powered by AI
            </div>

            <div class="ai-chat-messages d-flex flex-column gap-2 mb-3" id="aiChatMessages">
                <div class="ai-chat-bubble ai-chat-bubble-assistant">
                    Hi! I'm your learning assistant. Ask me anything about this lesson.
                </div>
            </div>

            <div class="d-flex flex-column gap-2 mb-3" id="aiSuggestions">
                <button type="button" class="btn w-100 text-start ai-suggestion-btn">
                    <i class="ti ti-file-text me-2"></i>
                    Summarize this material
                </button>

                <button type="button" class="btn w-100 text-start ai-suggestion-btn">
                    <i class="ti ti-list-details me-2"></i>
                    What are the key points?
                </button>

                <button type="button" class="btn w-100 text-start ai-suggestion-btn">
                    <i class="ti ti-sparkles me-2"></i>
                    Create a quiz from this material
                </button>

                <button type="button" class="btn w-100 text-start ai-suggestion-btn">
                    <i class="ti ti-help-circle me-2"></i>
                    Explain the difficult parts
                </button>
            </div>

            <form id="aiChatForm" class="d-flex gap-2">
                <input type="text"
                    class="form-control ai-chat-input"
                    id="aiChatInput"
                    placeholder="Type your question..."
                    autocomplete="off">

                <button type="submit" class="btn btn-primary btn-icon flex-shrink-0" aria-label="Send message">
                    <i class="ti ti-send"></i>
                </button>
            </form>

        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('aiChatForm');
        var input = document.getElementById('aiChatInput');
        var messages = document.getElementById('aiChatMessages');

        if (!form || !input || !messages) return;

        var AI_ENDPOINT = null;

        function appendBubble(text, role) {
            var bubble = document.createElement('div');
            bubble.className = 'ai-chat-bubble ai-chat-bubble-' + role;
            bubble.textContent = text;
            messages.appendChild(bubble);
            messages.scrollTop = messages.scrollHeight;
        }

        function sendToAI(message) {
            if (!AI_ENDPOINT) {
                console.log('[AI Assistant] message:', message);
                appendBubble('AI belum terhubung. Fitur ini akan segera aktif.', 'assistant');
                return;
            }

            fetch(AI_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    message: message,
                    content_id: {{ (int) $content->id }}
                })
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    appendBubble(data.reply || 'Maaf, tidak ada jawaban.', 'assistant');
                })
                .catch(function () {
                    appendBubble('Terjadi kesalahan. Coba lagi nanti.', 'assistant');
                });
        }

        document.querySelectorAll('.ai-suggestion-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                input.value = button.textContent.trim();
                input.focus();
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var message = input.value.trim();
            if (!message) return;

            appendBubble(message, 'user');
            input.value = '';
            sendToAI(message);
        });
    });
</script>