<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Training Modules</h2>
    </div>

    {{-- Tab Nav --}}
    <div class="px-4 pb-0 border-bottom">
        <ul class="nav home-modern-nav-tabs" id="trainingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-video-btn"
                        data-bs-toggle="tab" data-bs-target="#pane-video"
                        type="button" role="tab" aria-selected="true">
                    <i class="ti ti-video me-1"></i>Video
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-document-btn"
                        data-bs-toggle="tab" data-bs-target="#pane-document"
                        type="button" role="tab" aria-selected="false">
                    <i class="ti ti-file-text me-1"></i>Document
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-playlist-btn"
                        data-bs-toggle="tab" data-bs-target="#pane-playlist"
                        type="button" role="tab" aria-selected="false">
                    <i class="ti ti-playlist me-1"></i>Playlist
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-mooc-btn"
                        data-bs-toggle="tab" data-bs-target="#pane-mooc"
                        type="button" role="tab" aria-selected="false">
                    <i class="ti ti-world me-1"></i>MOOC
                </button>
            </li>
        </ul>
    </div>

    <div class="home-modern-card-body">

        <div class="tab-content" id="trainingTabContent">

            {{-- ── VIDEO TAB ── --}}
            <div class="tab-pane fade show active" id="pane-video" role="tabpanel">

                <div class="d-flex justify-content-end mb-2">
                    <a href="{{ url('/content') }}?s=&classification=&type=Video&month=&year=&author=&deadline_from_date=&deadline_to_date=&created_from_date=&created_to_date=&deadline_status=all&sort=newest_course&view_order=&duration=all"
                       class="home-modern-view-all view-all-link text-primary dark:text-white font-medium">
                        View All <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>

                @if($content_video && $content_video->count())
                <div class="home-modern-slide-carousel py-2" data-autoplay="4500">
                    <div class="home-modern-slide-viewport">
                        <div class="home-modern-slide-track">
                    @foreach($content_video->take(6) as $item)
                    <div class="home-modern-slide-item">
                        <a href="{{ route('content.user.view', $item->slug) }}" class="home-modern-video-card" title="{{ $item->title }}">

                            <div class="home-modern-video-thumb">
                                @if($item->type === 'YouTube')
                                    <img src="https://img.youtube.com/vi/{{ $item->link }}/mqdefault.jpg"
                                         alt="{{ $item->title }}"
                                         onerror="this.src='{{ asset('default-video.jpg') }}'">
                                @else
                                    @php
                                        $tmSrc = 'storage/files/content/' . $item->id . '/thumb.jpg';
                                        $tmUrl = (\File::exists(public_path($tmSrc)) && !is_dir(public_path($tmSrc)))
                                            ? asset($tmSrc) : asset('default-video.jpg');
                                    @endphp
                                    <img src="{{ $tmUrl }}"
                                         alt="{{ $item->title }}"
                                         onerror="this.src='{{ asset('default-video.jpg') }}'">
                                @endif
                                <div class="home-modern-video-play">
                                    <i class="ti ti-player-play-filled"></i>
                                </div>
                                @if($item->duration)
                                <span class="home-modern-duration-badge">{{ \App\Support\DurationFormatter::format($item->duration) }}</span>
                                @endif
                            </div>

                            <div class="home-modern-video-body">
                                <h4 class="home-modern-video-title" title="{{ $item->title }}">{{ Str::limit($item->title, 55) }}</h4>
                            </div>

                        </a>
                    </div>
                    @endforeach
                        </div>
                    </div>
                    <button class="home-modern-slide-nav home-modern-slide-nav-prev" type="button" aria-label="Previous videos">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <button class="home-modern-slide-nav home-modern-slide-nav-next" type="button" aria-label="Next videos">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
                @else
                <div class="home-modern-empty">
                    <i class="ti ti-video-off"></i>
                    <p>No videos available</p>
                </div>
                @endif

            </div>

            {{-- ── DOCUMENT TAB ── --}}
            <div class="tab-pane fade" id="pane-document" role="tabpanel">

                <div class="d-flex justify-content-end mb-2">
                    <a href="{{ url('/content') }}?s=&classification=&type=Document&month=&year=&author=&deadline_from_date=&deadline_to_date=&created_from_date=&created_to_date=&deadline_status=all&sort=newest_course&view_order=&duration=all"
                       class="home-modern-view-all view-all-link text-primary dark:text-white font-medium">
                        View All <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>

                @if($content_pdf && $content_pdf->count())
                <div class="home-modern-slide-carousel py-2" data-autoplay="4500">
                    <div class="home-modern-slide-viewport">
                        <div class="home-modern-slide-track">
                    @foreach($content_pdf->take(6) as $pdf)
                    <div class="home-modern-slide-item">
                        <a href="{{ route('content.user.view', $pdf->slug) }}" class="home-modern-video-card" title="{{ $pdf->title }}">

                            <div class="home-modern-video-thumb home-modern-document-thumb">
                                @php
                                    $docSrc = 'storage/files/content/' . $pdf->id . '/thumb.jpg';
                                    $docUrl = (\File::exists(public_path($docSrc)) && !is_dir(public_path($docSrc)))
                                        ? asset($docSrc) : asset('pdf.png');
                                @endphp
                                <img src="{{ $docUrl }}"
                                     alt="{{ $pdf->title }}"
                                     onerror="this.src='{{ asset('pdf.png') }}'"
                                     class="home-modern-document-img">
                            </div>

                            <div class="home-modern-video-body">
                                <span class="home-modern-category-badge home-modern-category-green">Document</span>
                                <h4 class="home-modern-video-title" title="{{ $pdf->title }}">{{ Str::limit($pdf->title, 55) }}</h4>
                            </div>

                        </a>
                    </div>
                    @endforeach
                        </div>
                    </div>
                    <button class="home-modern-slide-nav home-modern-slide-nav-prev" type="button" aria-label="Previous documents">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <button class="home-modern-slide-nav home-modern-slide-nav-next" type="button" aria-label="Next documents">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
                @else
                <div class="home-modern-empty">
                    <i class="ti ti-file-off"></i>
                    <p>No documents available</p>
                </div>
                @endif

            </div>

            {{-- ── PLAYLIST TAB ── --}}
            <div class="tab-pane fade" id="pane-playlist" role="tabpanel">

                <div class="d-flex justify-content-end mb-2">
                    <a href="{{ route('playlist.user.index') }}"
                       class="home-modern-view-all view-all-link text-primary dark:text-white font-medium">
                        View All <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>

                @php
                    $playlistCards = $playlist
                        ->filter(fn ($p) => optional($p->trainingplandetail->first())->content)
                        ->take(6);
                @endphp

                @if($playlistCards->count())
                <div class="home-modern-slide-carousel py-2" data-autoplay="4500">
                    <div class="home-modern-slide-viewport">
                        <div class="home-modern-slide-track">
                    @foreach($playlistCards as $item)
                        @php
                            $firstVideo = $item->trainingplandetail->first();
                            // null when thumbnail is missing, external, or not on disk
                            $plThumb = $item->thumbnail_url;
                        @endphp
                        <div class="home-modern-slide-item">
                            <a href="{{ route('playlist.user.view', ['slug' => $item->slug, 'vidslug' => $firstVideo->content->slug]) }}"
                               class="home-modern-video-card home-modern-playlist-hcard" title="{{ $item->title }}">

                                <div class="home-modern-video-thumb">
                                    @if($plThumb)
                                        <img src="{{ $plThumb }}"
                                             alt="{{ $item->title }}"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="home-modern-playlist-hthumb-fallback" style="display:none">
                                            <i class="ti ti-playlist"></i>
                                        </div>
                                    @else
                                        <div class="home-modern-playlist-hthumb-fallback">
                                            <i class="ti ti-playlist"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="home-modern-video-body">
                                    <span class="home-modern-category-badge home-modern-category-purple">Playlist</span>
                                    <h4 class="home-modern-video-title home-modern-playlist-htitle" title="{{ $item->title }}">{{ Str::limit($item->title, 55) }}</h4>
                                    <div class="home-modern-video-meta">
                                        <i class="ti ti-playlist me-1"></i>
                                        {{ $item->trainingplandetail->count() }} modules
                                    </div>
                                </div>

                            </a>
                        </div>
                    @endforeach
                        </div>
                    </div>
                    <button class="home-modern-slide-nav home-modern-slide-nav-prev" type="button" aria-label="Previous playlists">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <button class="home-modern-slide-nav home-modern-slide-nav-next" type="button" aria-label="Next playlists">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
                @else
                <div class="home-modern-empty">
                    <i class="ti ti-playlist-x"></i>
                    <p>No playlists available</p>
                </div>
                @endif

            </div>

            {{-- ── MOOC TAB ── --}}
            <div class="tab-pane fade" id="pane-mooc" role="tabpanel">

                <div class="d-flex justify-content-end mb-2">
                    <a href="{{ url('/content') }}?s=&classification=&type=YouTube&month=&year=&author=&deadline_from_date=&deadline_to_date=&created_from_date=&created_to_date=&deadline_status=all&sort=newest_course&view_order=&duration=all"
                       class="home-modern-view-all view-all-link text-primary dark:text-white font-medium">
                        View All <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>

                @if(isset($userMoocProgress) && $userMoocProgress->count())
                <div class="home-modern-slide-carousel py-2" data-autoplay="4500">
                    <div class="home-modern-slide-viewport">
                        <div class="home-modern-slide-track">
                    @foreach($userMoocProgress as $cu)
                        @php
                            $item = $cu->content;
                            if (!$item) continue;
                            $percent = (int) ($cu->progress ?? 0);
                            $isCompleted = (int) $cu->finish === 1 || $percent >= 100;
                        @endphp
                        <div class="home-modern-slide-item">
                            <a href="{{ route('content.user.view', $item->slug) }}" class="home-modern-video-card" title="{{ $item->title }}">

                                <div class="home-modern-video-thumb">
                                    @if($item->link)
                                        <img src="https://img.youtube.com/vi/{{ $item->link }}/mqdefault.jpg"
                                             alt="{{ $item->title }}"
                                             onerror="this.src='{{ asset('default-video.jpg') }}'">
                                    @else
                                        @php
                                            $tmSrc = 'storage/files/content/' . $item->id . '/thumb.jpg';
                                            $tmUrl = (\File::exists(public_path($tmSrc)) && !is_dir(public_path($tmSrc)))
                                                ? asset($tmSrc) : asset('default-video.jpg');
                                        @endphp
                                        <img src="{{ $tmUrl }}"
                                             alt="{{ $item->title }}"
                                             onerror="this.src='{{ asset('default-video.jpg') }}'">
                                    @endif
                                    <div class="home-modern-video-play">
                                        <i class="ti ti-player-play-filled"></i>
                                    </div>
                                    @if($item->duration)
                                    <span class="home-modern-duration-badge">{{ \App\Support\DurationFormatter::format($item->duration) }}</span>
                                    @endif
                                </div>

                                <div class="home-modern-video-body">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="home-modern-category-badge home-modern-category-indigo">MOOC</span>
                                        @if($isCompleted)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px; font-weight: 600; padding: 2px 6px;">Completed</span>
                                        @else
                                            <span class="text-muted fw-semibold" style="font-size: 11px;">{{ $percent }}%</span>
                                        @endif
                                    </div>
                                    <h4 class="home-modern-video-title" title="{{ $item->title }}">{{ Str::limit($item->title, 55) }}</h4>
                                    <div class="home-modern-video-meta mt-auto">
                                        @if($isCompleted)
                                            <span class="text-success small fw-medium"><i class="ti ti-circle-check me-1"></i>Completed</span>
                                        @else
                                            <div class="progress w-100" style="height: 4px; background-color: rgba(148, 163, 184, 0.2); border-radius: 2px; margin-top: 4px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, $percent) }}%; border-radius: 2px;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                            </a>
                        </div>
                    @endforeach
                        </div>
                    </div>
                    <button class="home-modern-slide-nav home-modern-slide-nav-prev" type="button" aria-label="Previous MOOC courses">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <button class="home-modern-slide-nav home-modern-slide-nav-next" type="button" aria-label="Next MOOC courses">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
                @else
                <div class="home-modern-empty text-center py-4">
                    <i class="ti ti-world-off text-muted mb-2" style="font-size: 40px; display: block;"></i>
                    <p class="fw-semibold text-secondary mb-1" style="font-size: 15px;">You don't have any MOOC progress yet.</p>
                    <p class="text-muted small mb-3">Start a MOOC course to track your learning progress here.</p>
                    <a href="{{ url('/content') }}?s=&classification=&type=YouTube&month=&year=&author=&deadline_from_date=&deadline_to_date=&created_from_date=&created_to_date=&deadline_status=all&sort=newest_course&view_order=&duration=all"
                       class="home-modern-btn-outline" style="display: inline-flex; align-items: center; gap: 6px;">
                        <i class="ti ti-compass"></i> Explore MOOC
                    </a>
                </div>
                @endif

            </div>

        </div>

    </div>

</div>
