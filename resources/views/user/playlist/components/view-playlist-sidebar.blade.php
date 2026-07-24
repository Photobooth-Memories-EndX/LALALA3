@php
    $defaultLessonThumbnail = asset('default-video.jpg');
@endphp

<div class="card border-0 shadow-sm rounded-4 sticky-top view-playlist-card" style="top: 1rem;">
    <div class="card-body p-3">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0 fs-4 fw-bold">Content List</h3>
            <div class="d-flex align-items-center gap-2">
                <button type="button"
                        class="panel-header-toggle"
                        onclick="toggleLearningPanel('left')"
                        title="Tutup Content List">
                    <i class="ti ti-list" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="mb-3">
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

                    $learningProgress = $progressByContentId[$list->content_id] ?? 0;

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
                                 aria-label="Content thumbnail">
                                <img src="{{ $defaultLessonThumbnail }}"
                                     alt=""
                                     class="lesson-thumbnail-image"
                                     onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                <span class="lesson-thumbnail-fallback d-none" aria-hidden="true">
                                    <i class="ti ti-video" aria-hidden="true"></i>
                                </span>
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

                        @if ($isActive && !empty($assessmentContent))
                            <div class="lesson-assessment-action">
                                <a href="{{ route('assesment.user.view', $assessmentContent->slug) }}"
                                   class="btn btn-xs btn-outline-primary px-2 py-1 {{ $learningProgress >= 100 ? '' : 'disabled' }}"
                                   data-assessment-action
                                   aria-disabled="{{ $learningProgress >= 100 ? 'false' : 'true' }}"
                                   @if ($learningProgress < 100) tabindex="-1" @endif>
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
