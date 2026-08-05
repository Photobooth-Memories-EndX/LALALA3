@php
    $defaultLessonThumbnail = asset('default-video.jpg');
    $requiredAssessmentProgress = \App\Models\ContentUser::ASSESSMENT_REQUIRED_PROGRESS;

    $totalVideos = ($content_all ?? collect())->count();
    $completedVideos = 0;
    if (isset($content_all) && isset($progressByContentId)) {
        foreach ($content_all as $item) {
            if (($progressByContentId[$item->content_id] ?? 0) >= 100) {
                $completedVideos++;
            }
        }
    }
    $playlistProgress = number_format((float) ($playlistUiState['learning_progress'] ?? $percentageProgressPlaylist ?? 0));
@endphp

<div class="card border-0 shadow-sm rounded-4 sticky-top view-playlist-card" style="top: 1rem;">
    <div class="card-body p-3">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0 fs-4 fw-bold">Content List</h3>
            <div class="d-flex align-items-center gap-2">
                <button type="button"
                        class="panel-header-toggle"
                        id="playlistSearchToggleBtn"
                        onclick="togglePlaylistSearch()"
                        title="Search in playlist">
                    <i class="ti ti-search" aria-hidden="true"></i>
                </button>
                <button type="button"
                        class="panel-header-toggle"
                        onclick="toggleLearningPanel('left')"
                        title="Tutup Content List">
                    <i class="ti ti-list" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between text-muted small mb-1">
                <span class="fw-semibold text-body">{{ $playlistProgress }}% Progress</span>
                <span class="text-muted">({{ $completedVideos }}/{{ $totalVideos }} videos)</span>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $playlistProgress }}%" aria-valuenow="{{ $playlistProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        <div class="mb-3 d-none" id="playlistSearchBox">
            <div class="input-icon">
                <span class="input-icon-addon">
                    <i class="ti ti-search" aria-hidden="true"></i>
                </span>
                <input type="text"
                       class="form-control form-control-sm rounded-3"
                       placeholder="Search in playlist..."
                       id="playlistSearchInput">
            </div>
        </div>

        <div class="playlist-scroll-area" id="playlistLessonList">
            @foreach ($content_all as $index => $list)
                @php
                    $lesson = $list->content;
                    $isActive = optional($lesson)->slug === $content->slug;
                    $lessonAssessment = $assessmentsByContent->get($list->content_id);

                    $learningProgress = $progressByContentId[$list->content_id] ?? 0;
                    $isAssessmentUnlocked = $learningProgress >= $requiredAssessmentProgress;
                    $isDocumentLesson = in_array(
                        $lesson->type,
                        ['Document', 'PDF', 'SOP/Regulation'],
                        true
                    );

                    if ($learningProgress >= 100) {
                        $lessonStatusIcon = 'ti ti-circle-check-filled';
                        $lessonStatusClass = 'lesson-done-icon';
                        $lessonStatusTone = 'lesson-status--completed';
                        $lessonStatusLabel = 'Completed';
                    } elseif ($learningProgress > 0) {
                        $lessonStatusIcon = 'ti ti-progress';
                        $lessonStatusClass = 'lesson-play-icon';
                        $lessonStatusTone = 'lesson-status--in-progress';
                        $lessonStatusLabel = 'In Progress';
                    } else {
                        $lessonStatusIcon = 'ti ti-clock';
                        $lessonStatusClass = 'lesson-play-icon';
                        $lessonStatusTone = 'lesson-status--not-started';
                        $lessonStatusLabel = 'Not Started';
                    }
                @endphp

                @if (optional($lesson)->slug)
                    <div class="playlist-lesson-item {{ $isActive ? 'is-active' : '' }}"
                         data-title="{{ strtolower($lesson->title) }}">
                        <a href="{{ route('playlist.user.view', [$playlist->slug, $lesson->slug]) }}"
                           class="playlist-lesson-main">
                            <div class="lesson-type-icon lesson-thumbnail"
                                 role="img"
                                 aria-label="{{ $isDocumentLesson ? 'Document content' : 'Video content' }}">
                                @if ($isDocumentLesson)
                                    <span class="lesson-thumbnail-fallback" aria-hidden="true">
                                        <i class="ti ti-file-description" aria-hidden="true"></i>
                                    </span>
                                @else
                                    <img src="{{ $defaultLessonThumbnail }}"
                                         alt=""
                                         class="lesson-thumbnail-image"
                                         onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                    <span class="lesson-thumbnail-fallback d-none" aria-hidden="true">
                                        <i class="ti ti-video" aria-hidden="true"></i>
                                    </span>
                                @endif
                            </div>

                            <div class="lesson-info">
                                <div class="lesson-heading">
                                    <div class="lesson-title">
                                        Ch. {{ $index + 1 }}: {{ $lesson->title }}
                                    </div>
                                    <span class="lesson-status-icon {{ $lessonStatusClass }} {{ $lessonStatusTone }}"
                                          role="img"
                                          aria-label="{{ $lessonStatusLabel }}"
                                          title="{{ $lessonStatusLabel }}">
                                        <i class="{{ $lessonStatusIcon }}" aria-hidden="true"></i>
                                    </span>
                                </div>

                                <div class="lesson-meta">
                                    {{ $learningProgress }}%
                                </div>

                                <div class="progress lesson-progress">
                                    <div class="progress-bar" style="width: {{ $learningProgress }}%"></div>
                                </div>
                            </div>
                        </a>

                        @if ($lessonAssessment)
                            <div class="lesson-assessment-action">
                                <a @if ($isAssessmentUnlocked) href="{{ route('assesment.user.view', $lessonAssessment->slug) }}" @endif
                                   data-assessment-url="{{ route('assesment.user.view', $lessonAssessment->slug) }}"
                                   data-required-progress="{{ $requiredAssessmentProgress }}"
                                   data-assessment-locked-title="Complete this content to unlock the assessment."
                                   class="btn btn-outline-primary lesson-assessment-button {{ $isAssessmentUnlocked ? '' : 'disabled' }}"
                                   data-assessment-action
                                   aria-disabled="{{ $isAssessmentUnlocked ? 'false' : 'true' }}"
                                   @unless ($isAssessmentUnlocked)
                                       tabindex="-1"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       title="Complete this content to unlock the assessment."
                                   @endunless>
                                    <i class="ti ti-clipboard-check me-1" aria-hidden="true"></i>
                                    Assessment Content
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>

    </div>
</div>

<script>
    function togglePlaylistSearch() {
        const searchBox = document.getElementById('playlistSearchBox');
        const searchInput = document.getElementById('playlistSearchInput');
        const searchBtn = document.getElementById('playlistSearchToggleBtn');

        if (!searchBox || !searchInput) return;

        const isHidden = searchBox.classList.contains('d-none');
        if (isHidden) {
            searchBox.classList.remove('d-none');
            if (searchBtn) searchBtn.classList.add('active');
            searchInput.focus();
        } else {
            if (searchInput.value) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('keyup'));
            }
            searchBox.classList.add('d-none');
            if (searchBtn) searchBtn.classList.remove('active');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('playlistSearchInput');
        const searchBox = document.getElementById('playlistSearchBox');
        const btn = document.getElementById('playlistSearchToggleBtn');

        if (input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    this.dispatchEvent(new Event('keyup'));
                    searchBox?.classList.add('d-none');
                    btn?.classList.remove('active');
                    this.blur();
                }
            });
        }
    });
</script>
