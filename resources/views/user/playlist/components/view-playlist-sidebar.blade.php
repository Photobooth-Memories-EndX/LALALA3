@php
    $defaultLessonThumbnail = asset('default-video.jpg');
    $requiredAssessmentProgress = \App\Models\ContentUser::ASSESSMENT_REQUIRED_PROGRESS;

    $totalVideos = $totalContent ?? ($content_all ?? collect())->count();
    $completedVideos = $completedContent ?? 0;
    if (!isset($completedContent) && isset($content_all) && isset($progressByContentId)) {
        foreach ($content_all as $item) {
            if (($progressByContentId[$item->content_id] ?? 0) >= 100) {
                $completedVideos++;
            }
        }
    }
    $playlistProgress = number_format((float) ($playlistUiState['learning_progress'] ?? $percentageProgressPlaylist ?? 0));
@endphp

<div class="card border-0 shadow-sm rounded-4 sticky-top view-playlist-card" style="top: 1rem;">
    <div class="card-body p-3 d-flex flex-column">

        <div class="d-flex align-items-center justify-content-between mb-3 flex-shrink-0">
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

        <div class="mb-3 flex-shrink-0">
            <div class="d-flex align-items-center justify-content-between text-muted small mb-1">
                <span class="fw-semibold text-body">{{ $playlistProgress }}% Progress</span>
                <span class="text-muted">({{ $completedVideos }} of {{ $totalVideos }} finished)</span>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $playlistProgress }}%" aria-valuenow="{{ $playlistProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        @if (!empty($continueLearningContent) && $continueLearningContent->id !== $content->id)
            <div class="mb-3 flex-shrink-0">
                <a href="{{ route('playlist.user.view', [$playlist->slug, $continueLearningContent->slug]) }}"
                   class="btn btn-primary w-100 rounded-pill shadow-sm d-flex align-items-center justify-content-center gap-2 py-2"
                   style="background-color: #0c5b9e !important; border-color: #0c5b9e !important; color: #ffffff !important;">
                    <i class="ti ti-player-play-filled"></i>
                    <span class="fw-semibold">Continue Learning</span>
                    <i class="ti ti-arrow-right ms-auto"></i>
                </a>
            </div>
        @endif

        <div class="mb-3 d-none flex-shrink-0" id="playlistSearchBox">
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

        <div class="playlist-scroll-area flex-grow-1" id="playlistLessonList">
            @foreach ($content_all as $index => $list)
                @php
                    $lesson = $list->content;
                    if (!$lesson || !$lesson->id) continue;

                    $isContentInactive = ($lesson->active !== 'Active');
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

                @if ($isContentInactive)
                    <!-- Item Inactive: Tampil Utuh, Non-clickable -->
                    <div class="playlist-lesson-item is-inactive bg-light opacity-60 pe-none border-0 mb-2 rounded-3 p-2 position-relative"
                         data-title="{{ strtolower($lesson->title) }}"
                         style="cursor: not-allowed; opacity: 0.6;">
                        <div class="playlist-lesson-main d-flex align-items-start gap-2">
                            <div class="lesson-type-icon lesson-thumbnail flex-shrink-0"
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

                            <div class="lesson-info flex-grow-1 min-w-0">
                                <div class="lesson-heading">
                                    <div class="lesson-title">
                                        <span class="fw-bold text-muted d-inline me-1">#{{ $loop->iteration }}</span>
                                        <span class="content-title-two-lines text-secondary fw-semibold small" title="{{ $lesson->title }}">
                                            {{ $lesson->title }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-1">
                                    <span class="badge rounded-pill text-white fw-semibold px-2 py-0.5" style="background-color: #f59e0b !important; font-size: 0.68rem;">Inactive</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif (optional($lesson)->slug)
                    <!-- Item Active: Link Clickable biasa -->
                    <div class="playlist-lesson-item {{ $isActive ? 'is-active' : '' }}"
                         data-title="{{ strtolower($lesson->title) }}">
                        <a href="{{ route('playlist.user.view', [$playlist->slug, $lesson->slug]) }}"
                           class="playlist-lesson-main">
                            <div class="lesson-type-icon lesson-thumbnail flex-shrink-0"
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

                            <div class="lesson-info flex-grow-1 min-w-0">
                                <div class="lesson-heading">
                                    <div class="lesson-title">
                                        <span class="fw-bold d-inline me-1">#{{ $loop->iteration }}</span>
                                        <span class="content-title-two-lines text-dark fw-semibold small" title="{{ $lesson->title }}">
                                            {{ $lesson->title }}
                                        </span>
                                    </div>
                                    <span class="lesson-status-icon {{ $lessonStatusClass }} {{ $lessonStatusTone }}"
                                          role="img"
                                          aria-label="{{ $lessonStatusLabel }}"
                                          title="{{ $lessonStatusLabel }}">
                                        <i class="{{ $lessonStatusIcon }}" aria-hidden="true"></i>
                                    </span>
                                </div>

                                <div class="lesson-meta mt-1">
                                    {{ $learningProgress }}%
                                </div>

                                <div class="progress lesson-progress">
                                    <div class="progress-bar" style="width: {{ $learningProgress }}%"></div>
                                </div>
                            </div>
                        </a>

                        @php
                            $cState = $contentAssessmentStates[$list->content_id] ?? null;
                        @endphp
                        @if ($cState)
                            <div class="lesson-assessment-action">
                                @if ($cState['state'] === 'passed')
                                    <button type="button"
                                            class="btn btn-success lesson-assessment-button w-100"
                                            style="background-color: #2fb344 !important; border-color: #2fb344 !important; color: #ffffff !important; cursor: default !important;"
                                            disabled
                                            aria-disabled="true"
                                            tabindex="-1">
                                        <i class="ti ti-check me-1" aria-hidden="true"></i>
                                        Passed
                                    </button>
                                @elseif ($cState['state'] === 'expired')
                                    <button type="button"
                                            class="btn btn-secondary lesson-assessment-button w-100"
                                            style="background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important; cursor: not-allowed !important;"
                                            disabled
                                            aria-disabled="true"
                                            tabindex="-1">
                                        <i class="ti ti-calendar-x me-1" aria-hidden="true"></i>
                                        Assessment Content Expired
                                    </button>
                                @elseif ($cState['state'] === 'inactive')
                                    <button type="button"
                                            class="btn btn-secondary lesson-assessment-button w-100"
                                            style="background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important; cursor: not-allowed !important;"
                                            disabled
                                            aria-disabled="true"
                                            tabindex="-1">
                                        <i class="ti ti-alert-circle me-1" aria-hidden="true"></i>
                                        Assessment Content Inactive
                                    </button>
                                @elseif ($cState['state'] === 'before_start')
                                    <button type="button"
                                            class="btn btn-secondary lesson-assessment-button w-100"
                                            style="background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important; cursor: not-allowed !important;"
                                            disabled
                                            aria-disabled="true"
                                            tabindex="-1">
                                        <i class="ti ti-clock me-1" aria-hidden="true"></i>
                                        Not Available Yet
                                    </button>
                                @elseif ($cState['state'] === 'max_attempts')
                                    <button type="button"
                                            class="btn btn-secondary lesson-assessment-button w-100"
                                            style="background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important; cursor: not-allowed !important;"
                                            disabled
                                            aria-disabled="true"
                                            tabindex="-1">
                                        <i class="ti ti-alert-triangle me-1" aria-hidden="true"></i>
                                        Max Attempt Reached
                                    </button>
                                @elseif ($isAssessmentUnlocked)
                                    <button type="button"
                                            class="btn btn-outline-primary lesson-assessment-button w-100"
                                            data-assessment-trigger="true"
                                            data-assessment-id="{{ $cState['assessmentId'] }}"
                                            data-assessment-action-type="{{ $cState['actionType'] }}"
                                            data-assessment-title="{{ $cState['title'] }}"
                                            data-assessment-type="ASSESSMENT CONTENT"
                                            data-assessment-type-label="Playlist Content"
                                            data-assessment-batch="{{ $cState['batchDisplay'] }}"
                                            data-assessment-from-date="{{ $cState['fromDateVal'] }}"
                                            data-assessment-to-date="{{ $cState['toDateVal'] }}"
                                            data-assessment-passing-grade="{{ $cState['passingGrade'] }}"
                                            data-assessment-duration="{{ $cState['duration'] }}"
                                            data-assessment-attempts="{{ $cState['attemptsDisplay'] }}"
                                            data-assessment-user-attempts="{{ $cState['finalAttempts'] }}"
                                            data-assessment-max-attempts="{{ $cState['maxAttempts'] }}"
                                            data-assessment-answered-count="{{ $cState['answeredCount'] }}"
                                            data-assessment-total-count="{{ $cState['totalQuestions'] }}"
                                            data-assessment-is-expired="false"
                                            data-assessment-is-inactive="false"
                                            data-target-url="{{ route('assesment.user.view', $cState['slug']) }}"
                                            data-assessment-url="{{ route('assesment.user.view', $cState['slug']) }}"
                                            data-required-progress="{{ $requiredAssessmentProgress }}"
                                            data-assessment-action
                                            aria-disabled="false">
                                        <i class="ti ti-clipboard-check me-1" aria-hidden="true"></i>
                                        Assessment Content
                                    </button>
                                @else
                                    <button type="button"
                                            class="btn btn-outline-primary lesson-assessment-button disabled w-100"
                                            disabled
                                            tabindex="-1"
                                            title="Complete this content to unlock the assessment."
                                            aria-disabled="true">
                                        <i class="ti ti-clipboard-check me-1" aria-hidden="true"></i>
                                        Assessment Content
                                    </button>
                                @endif
                            </div>
                        @elseif ($lessonAssessment && ($lessonAssessment->type ?? '') === 'content' && ($lessonAssessment->assasment_content_for ?? '') === 'playlist' && (int)($lessonAssessment->trainingplan_id ?? 0) === (int)($playlist->id ?? 0))
                            @php
                                $contentBatchDisplay = !empty($lessonAssessment->batch) && $lessonAssessment->batch !== '-'
                                    ? (str_contains(strtolower((string)$lessonAssessment->batch), 'batch') ? $lessonAssessment->batch : 'Batch ' . $lessonAssessment->batch)
                                    : '-';
                                $contentFromDateVal = !empty($lessonAssessment->fromdate) ? date('d M Y, H:i', strtotime($lessonAssessment->fromdate)) : '-';
                                $contentToDateVal = !empty($lessonAssessment->todate) ? date('d M Y, H:i', strtotime($lessonAssessment->todate)) : '-';
                                $contentPassingGradeVal = $lessonAssessment->passing_grade !== null && $lessonAssessment->passing_grade !== '' ? $lessonAssessment->passing_grade : '-';
                                $contentAttemptsVal = (($assessmentAttemptCounts[$lessonAssessment->id] ?? 0)) . ' / ' . (1 + (int)($lessonAssessment->total_remedials ?? 0)) . ' Attempts';
                            @endphp
                            <div class="lesson-assessment-action">
                                <button type="button"
                                        class="btn btn-outline-primary lesson-assessment-button {{ $isAssessmentUnlocked ? '' : 'disabled' }}"
                                        @if ($isAssessmentUnlocked)
                                            data-assessment-trigger="true"
                                            data-assessment-id="{{ $lessonAssessment->id }}"
                                            data-assessment-title="{{ $lessonAssessment->title }}"
                                            data-assessment-type="ASSESSMENT CONTENT"
                                            data-assessment-batch="{{ $contentBatchDisplay }}"
                                            data-assessment-from-date="{{ $contentFromDateVal }}"
                                            data-assessment-to-date="{{ $contentToDateVal }}"
                                            data-assessment-passing-grade="{{ $contentPassingGradeVal }}"
                                            data-assessment-attempts="{{ $contentAttemptsVal }}"
                                            data-target-url="{{ route('assesment.user.view', $lessonAssessment->slug) }}"
                                        @else
                                            disabled
                                            tabindex="-1"
                                            title="Complete this content to unlock the assessment."
                                        @endif
                                        data-assessment-url="{{ route('assesment.user.view', $lessonAssessment->slug) }}"
                                        data-required-progress="{{ $requiredAssessmentProgress }}"
                                        data-assessment-action
                                        aria-disabled="{{ $isAssessmentUnlocked ? 'false' : 'true' }}">
                                    <i class="ti ti-clipboard-check me-1" aria-hidden="true"></i>
                                    Assessment Content
                                </button>
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
