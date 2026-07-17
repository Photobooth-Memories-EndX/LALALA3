<div class="card border-0 shadow-sm rounded-4 sticky-top view-playlist-card" style="top: 1rem;">
    <div class="card-body p-3">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0 fs-4 fw-bold">Content List</h3>
            <div class="d-flex align-items-center gap-2">
                <button type="button"
                        class="panel-header-toggle"
                        onclick="toggleLearningPanel('left')"
                        title="Tutup Content List">
                    <i class="fas fa-list-ul"></i>
                </button>
            </div>
        </div>

        <div class="mb-3">
            <div class="input-icon">
                <span class="input-icon-addon">
                    <i class="fas fa-search"></i>
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

                    if (optional($lesson)->type == 'YouTube') {
                        $thumb = 'http://img.youtube.com/vi/' . $lesson->link . '/mqdefault.jpg';
                    } else {
                        $src = 'storage/files/content/' . optional($lesson)->id . '/thumb.jpg';
                        $thumb = \File::exists(public_path($src)) && !is_dir(public_path($src))
                            ? asset($src)
                            : asset('default-video.jpg');
                    }
                @endphp

                @if (optional($lesson)->slug)
                    <a href="{{ route('playlist.user.view', [$playlist->slug, $lesson->slug]) }}"
                       class="playlist-lesson-item {{ $isActive ? 'is-active' : '' }}"
                       data-title="{{ strtolower($lesson->title) }}">

                        <div class="lesson-thumb" style="background-image: url('{{ $thumb }}')">
                            @if ($learningProgress >= 100)
                                <div class="lesson-done-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                            @else
                                <div class="lesson-play-icon">
                                    <i class="fas fa-play"></i>
                                </div>
                            @endif
                        </div>

                        <div class="lesson-info">
                            <div class="lesson-title">
                                Ch. {{ $index + 1 }}: {{ $lesson->title }}
                            </div>

                            <div class="lesson-meta">
                                {{ $learningProgress }}%
                            </div>

                            <div class="progress lesson-progress">
                                <div class="progress-bar" style="width: {{ $learningProgress }}%"></div>
                            </div>

                            @if ($isActive && !empty($assessmentContent))
                                <div class="mt-2">
                                    <span class="btn btn-xs btn-outline-primary disabled px-2 py-1">
                                        <i class="fas fa-clipboard-check me-1"></i>
                                        Assessment
                                    </span>
                                </div>
                            @endif
                        </div>

                    </a>
                @endif
            @endforeach
        </div>

    </div>
</div>
