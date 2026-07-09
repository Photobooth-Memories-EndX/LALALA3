@php
    $progress = (int) ($content_user->progress ?? 0);
    $bookmarkCount = $bookmarkCount ?? 0;
    $discussionCount = $discussionCount ?? 0;
    $isBookmarked = $isBookmarked ?? false;

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

        <div class="d-flex justify-content-between align-items-start mb-4">

            <div>
                <div class="text-muted small fw-semibold mb-1">
                    Your Progress
                </div>

                <h1 id="contentProgressValue" class="fw-bold text-primary mb-2" style="font-size:2.4rem; line-height:1;">
                    {{ $progress }}%
                </h1>

                <small class="text-muted d-block" style="max-width:170px;">
                    Keep going! You're making great progress.
                </small>
            </div>

            <span class="badge rounded-pill bg-primary-lt text-primary px-3 py-2 mt-1">
                <i class="ti ti-check me-1"></i>
                <span id="contentProgressStatus">{{ $learningStatusLabel }}</span>
            </span>

        </div>

        <div class="progress mb-4" style="height:8px;">
            <div
                id="contentProgressBar"
                class="progress-bar bg-primary rounded-pill"
                style="width: {{ $progress }}%">
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">

            <span class="text-muted small">
                Learning Duration
            </span>

            <span class="fw-semibold text-dark">
                <i class="ti ti-clock me-1 text-muted"></i>
                {{ $content->duration ?? 'Self-paced' }}
            </span>

        </div>

    </div>
</div>

    <div class="card border-0 shadow-sm rounded-4 text-center glass-surface content-sidebar-card">
        <div class="card-body p-4">

            <div class="avatar avatar-xl mx-auto mb-3 rounded-circle bg-primary-lt text-primary shadow-sm">
                <i class="ti ti-user-circle fs-1"></i>
            </div>

            <div class="d-flex justify-content-center mb-2">
                <span class="badge rounded-pill bg-success-lt text-success">Instructor</span>
            </div>

            <h3 class="mb-1">
                {{ $content->author ?? 'Training Team' }}
            </h3>

            <div class="text-muted small mb-3">
                Content Author / Instructor
            </div>

            <div class="d-flex flex-wrap justify-content-center gap-2">
                <span class="badge rounded-pill bg-primary-lt text-primary px-3 py-2">
                    <i class="ti ti-badge me-1"></i>
                    Instructor
                </span>

                <span class="badge rounded-pill bg-secondary-lt text-secondary px-3 py-2">
                    <i class="ti ti-clock me-1"></i>
                    {{ $content->duration ?? 'Flexible' }}
                </span>
            </div>

        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card">
        <div class="card-body p-4">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="card-title mb-0">
                    <i class="ti ti-chart-bar text-primary me-1"></i>
                    Content Stats
                </h3>

                @if($isBookmarked)
                    <span class="badge rounded-pill bg-primary-lt text-primary">Saved</span>
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
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Comments</div>
                        <div class="fw-bold fs-3">{{ number_format($discussionCount) }}</div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Saved</div>
                        <div class="fw-bold fs-3">{{ number_format($bookmarkCount) }}</div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Type</div>
                        <div class="fw-bold fs-3 text-truncate">{{ $content->type }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @if ($assesment)
        <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card">
            <div class="card-body p-4">

                <h3 class="card-title mb-3">
                    <i class="ti ti-clipboard-check text-primary me-1"></i>
                    Assessment
                </h3>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Passing Grade</span>
                    <strong>{{ $assesment->passing_grade }}</strong>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Latest Score</span>
                    <strong>{{ $assessmentLatestScores ?? 0 }}</strong>
                </div>

                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Attempt</span>
                    <strong>{{ $assessmentCount ?? 0 }}/{{ 1 + $assesment->total_remedials }}</strong>
                </div>

                <a href="{{ route('assesment.user.view', $assesment->slug) }}"
                   class="btn btn-primary w-100 {{ $content_user->progress < 100 || ($assessmentCount >= 1 + $assesment->total_remedials && $assessmentLatestScores < $assesment->passing_grade) || $assessmentLatestScores >= $assesment->passing_grade ? 'disabled' : '' }}">
                    Start Assessment
                </a>

            </div>
        </div>
    @endif

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
                    Ringkas materi ini
                </button>

                <button type="button" class="btn w-100 text-start ai-suggestion-btn">
                    <i class="ti ti-list-details me-2"></i>
                    Apa poin pentingnya?
                </button>

                <button type="button" class="btn w-100 text-start ai-suggestion-btn">
                    <i class="ti ti-sparkles me-2"></i>
                    Buatkan quiz dari materi ini
                </button>

                <button type="button" class="btn w-100 text-start ai-suggestion-btn">
                    <i class="ti ti-help-circle me-2"></i>
                    Jelaskan bagian yang sulit
                </button>
            </div>

            <form id="aiChatForm" class="d-flex gap-2">
                <input type="text"
                    class="form-control ai-chat-input"
                    id="aiChatInput"
                    placeholder="Tanya sesuatu tentang materi"
                    autocomplete="off">

                <button type="submit" class="btn btn-primary btn-icon flex-shrink-0" aria-label="Send message">
                    <i class="ti ti-send"></i>
                </button>
            </form>

        </div>
    </div>

    {{-- <div class="card border-0 shadow-sm rounded-4 text-white tips-card">
        <div class="card-body p-4">
            <h3 class="text-white mb-2">
                <i class="ti ti-bulb me-1"></i>
                Learning Tips
            </h3>
            <p class="mb-0">
                Selesaikan materi terlebih dahulu sebelum membuka assessment.
            </p>
        </div>
    </div> --}}

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('aiChatForm');
        var input = document.getElementById('aiChatInput');
        var messages = document.getElementById('aiChatMessages');

        if (!form || !input || !messages) return;

        // Endpoint AI belum tersedia. Saat backend siap, isi URL di bawah
        // (contoh: '/api/ai/content-chat') dan sendToAI otomatis memakainya.
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