<div class="card border-0 shadow-sm rounded-4 sticky-top view-ai-card ai-card" style="top: 1rem;">
    <div class="card-body p-0 d-flex flex-column" style="height: 100%;">

        {{-- Header --}}
        <div class="p-4 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <div class="flex-shrink-0 rounded-3 bg-primary-lt d-flex align-items-center justify-content-center"
                     style="width:40px;height:40px;font-size:20px;">
                    🤖
                </div>
                <div class="flex-fill min-w-0">
                    <div class="fw-bold lh-1 mb-1" style="font-size:14px;">AI Assistant</div>
                    <div class="text-muted lh-1" style="font-size:11px;">Powered by AI</div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <span class="badge bg-success rounded-pill" style="font-size:10px;">● Online</span>
                    <button type="button"
                            class="panel-header-toggle"
                            onclick="toggleLearningPanel('right')"
                            title="Tutup AI Assistant">
                        <i class="fas fa-robot"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-4 d-flex flex-column gap-3 flex-fill" style="overflow-y: auto;">

            {{-- Welcome message --}}
            <div class="ai-message-box">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="ti ti-sparkles text-primary" style="font-size:15px;"></i>
                    <span class="fw-semibold text-dark" style="font-size:12px;">Learning Assistant</span>
                </div>
                <span class="text-secondary" style="font-size:13px; line-height:1.5;">
                    Hi! I'm your learning assistant. Ask me anything about this lesson.
                </span>
            </div>

            {{-- Suggested prompts --}}
            <div>
                <div class="text-uppercase text-muted fw-semibold mb-2" style="font-size:10px; letter-spacing:.06em;">
                    Suggested Prompts
                </div>

                <div class="d-flex flex-column gap-2">
                    <button type="button" class="btn btn-light border ai-suggestion-btn text-start"
                            onclick="aiInsertPrompt(this)">
                        <i class="ti ti-file-text me-2 text-primary"></i>
                        Ringkas materi ini
                    </button>

                    <button type="button" class="btn btn-light border ai-suggestion-btn text-start"
                            onclick="aiInsertPrompt(this)">
                        <i class="ti ti-list-check me-2 text-primary"></i>
                        Apa poin pentingnya?
                    </button>

                    <button type="button" class="btn btn-light border ai-suggestion-btn text-start"
                            onclick="aiInsertPrompt(this)">
                        <i class="ti ti-help-circle me-2 text-primary"></i>
                        Buatkan quiz dari materi ini
                    </button>

                    <button type="button" class="btn btn-light border ai-suggestion-btn text-start"
                            onclick="aiInsertPrompt(this)">
                        <i class="ti ti-zoom-question me-2 text-primary"></i>
                        Jelaskan bagian yang sulit
                    </button>
                </div>
            </div>

        </div>

        {{-- Chat input --}}
        <div class="p-4 pt-3 border-top">
            <div class="input-group ai-input-group">
                <input type="text"
                       id="aiChatInput"
                       class="form-control"
                       placeholder="Tanya sesuatu tentang materi ini...">
                <button class="btn btn-primary" type="button" onclick="aiSendMessage()">
                    <i class="ti ti-send"></i>
                </button>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    function aiInsertPrompt(btn) {
        const text = btn.innerText.trim();
        const input = document.getElementById('aiChatInput');
        if (input) {
            input.value = text;
            input.focus();
        }
    }

    function aiSendMessage() {
        const input = document.getElementById('aiChatInput');
        if (!input || !input.value.trim()) return;
        // Placeholder — wire up to your AI endpoint here
        input.value = '';
        input.focus();
    }

    (function () {
        const input = document.getElementById('aiChatInput');
        if (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') aiSendMessage();
            });
        }
    })();
</script>
@endpush
