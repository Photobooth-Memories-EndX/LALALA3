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
    $learningHoursDisplay = ($learningHoursNum == (int) $learningHoursNum)
        ? (int) $learningHoursNum
        : $learningHoursNum;
    $learningHoursText = $learningHoursDisplay . ' ' . ($learningHoursNum == 1 ? 'Hour' : 'Hours');

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
                        <div class="text-muted small mb-1">
                            <i class="ti ti-book-2 me-1"></i>
                            <span class="pages-read-text">
                                {{ $readPages }}/{{ $totalPages }} pages read
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Badge Status dengan Hijau Tua saat Completed --}}
                <div class="ms-2 flex-shrink-0">
                    <span class="badge rounded-pill {{ $learningStatusLabel === 'Completed' ? 'bg-success-lt text-success-dark fw-bold' : 'bg-primary-lt text-primary' }} px-3 py-2 border-0 d-inline-flex align-items-center"
                          style="{{ $learningStatusLabel === 'Completed' ? 'color: #0f5132 !important; background-color: #d1e7dd !important;' : '' }}">
                        <i class="ti ti-check me-1" style="{{ $learningStatusLabel === 'Completed' ? 'color: #0f5132 !important;' : '' }}"></i>
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
                    {{ \App\Support\DurationFormatter::format($content->duration, 'Self-paced') }}
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

    @php
        $assessmentObj = $assessment ?? $assesment ?? null;
    @endphp

    @if ($assessmentObj)
        @php
            $attempts = $assessmentObj->attempts_count ?? $assessmentCount ?? 0;
            $maxAttempts = $assessmentObj->max_attempts ?? (isset($assessmentObj->total_remedials) ? (1 + (int)$assessmentObj->total_remedials) : 3);
            $latestScore = $assessmentObj->latest_score ?? $assessmentLatestScores ?? 0;
            $passingGrade = $assessmentObj->passing_grade ?? 80;
            $isPassed = $latestScore >= $passingGrade;
            $meetsProgressRequirement = isset($content_user) && method_exists($content_user, 'meetsAssessmentProgressRequirement')
                ? $content_user->meetsAssessmentProgressRequirement()
                : true;
            $assessmentSlug = $assessmentObj->slug ?? '#';
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
                    <button type="button" class="btn btn-success disabled w-100 rounded-pill" disabled>
                        <i class="ti ti-check me-1"></i> Passed
                    </button>
                @elseif ($attempts == 0)
                    <a href="{{ route('assesment.user.view', $assessmentSlug) }}"
                       class="btn btn-primary w-100 rounded-pill {{ !$meetsProgressRequirement ? 'disabled' : '' }}">
                        Start Assessment
                    </a>
                @elseif ($attempts < $maxAttempts)
                    <a href="{{ route('assesment.user.view', $assessmentSlug) }}"
                       class="btn w-100 rounded-pill shadow-sm {{ !$meetsProgressRequirement ? 'disabled' : '' }}"
                       style="background-color: #ff7a00; color: white; border: none;">
                        <i class="ti ti-refresh me-1"></i> Remedial
                    </a>
                @else
                    <button type="button" class="btn btn-secondary disabled w-100 rounded-pill" disabled>
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

            <div class="row g-2">
                <div class="col-6">
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Views</div>
                        <div class="fw-bold fs-3">{{ number_format($content->views ?? 0) }}</div>
                    </div>
                </div>

                <div class="col-6">
                    <button type="button" class="sidebar-stat-card w-100 text-start border-0"
                            data-bs-toggle="modal" data-bs-target="#contentCommentModal">
                        <div class="text-muted small">Comments</div>
                        <div class="fw-bold fs-3">{{ number_format($discussionCount) }}</div>
                    </button>
                </div>

                <div class="col-6">
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Favorite</div>
                        <div class="fw-bold fs-3">{{ number_format($favoritesCount) }}</div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Type</div>
                        <div class="fw-bold fs-3 text-truncate">{{ \App\Support\ContentTypeHelper::label($content->type) }}</div>
                    </div>
                </div>
            </div>

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